<?php

namespace Modules\MCH\Filament\Clusters\Workspace\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\Core\Settings\FeatureSettings;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Classes\Services\AncReturnScheduler;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Classes\Services\MchWorkspaceService;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Filament\Clusters\MCH\Pages\VaccinationCard;
use Modules\MCH\Filament\Clusters\Workspace\MchWorkspaceCluster;
use Modules\MCH\Filament\Widgets\PatientChildVisitsWidget;
use Modules\MCH\Filament\Widgets\PatientGrowthMeasurementsWidget;
use Modules\MCH\Filament\Widgets\PatientImmunizationsWidget;
use Modules\MCH\Filament\Widgets\PatientMaternalVisitsWidget;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Classes\Services\PatientSearchService;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Models\Patient;
use Throwable;

class MchWorkspace extends Page
{
    use HasPageShield;

    protected static ?string $slug = '';

    protected static ?string $navigationLabel = 'MCH Workspace';

    protected static ?string $cluster = MchWorkspaceCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected string $view = 'mch::filament.clusters.workspace.pages.mch-workspace';

    #[Url]
    public ?string $patientId = null;

    public string $mode = 'home';

    public string $activeTab = 'overview';

    public string $searchTerm = '';

    /** @var list<array<string, mixed>> */
    public array $searchResults = [];

    /** @var list<string> */
    public array $recentPatientIds = [];

    public ?Patient $currentPatient = null;

    /** @var array{kind: string, pregnancy: mixed, childHealthRecord: mixed} */
    public array $context = ['kind' => 'unknown', 'pregnancy' => null, 'childHealthRecord' => null];

    public ?Encounter $currentEncounter = null;

    /** @var array<string, mixed> */
    public array $ancVisitData = [];

    /** @var array<string, mixed> */
    public array $cwcVisitData = [];

    public string $registerKind = 'mother';

    /** @var array<string, mixed> */
    public array $registerData = [];

    public string $administerBatchLot = '';

    public string $declineReason = '';

    public static function shouldRegisterNavigation(): bool
    {
        try {
            return app(FeatureSettings::class)->mch_workspace_enabled;
        } catch (Throwable) {
            return true;
        }
    }

    public function mount(): void
    {
        $this->recentPatientIds = session()->get('mch_workspace.recent', []);
        $this->ancVisitData = $this->defaultAncVisitData();
        $this->cwcVisitData = $this->defaultCwcVisitData();

        if ($this->patientId) {
            $this->selectPatient($this->patientId);
        }
    }

    public function updatedSearchTerm(): void
    {
        $term = trim($this->searchTerm);

        if (strlen($term) < 2) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = app(PatientSearchService::class)
            ->search($term, 10)
            ->map(fn (Patient $patient): array => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'mrn' => $patient->mrn,
                'phone' => $patient->phone,
            ])
            ->all();
    }

    public function selectPatient(string $id): void
    {
        $patient = Patient::query()->find($id);

        if ($patient === null) {
            Notification::make()->title('Patient not found')->danger()->send();

            return;
        }

        $this->currentPatient = $patient;
        $this->patientId = $patient->id;
        $this->mode = 'patient';
        $this->context = app(MchWorkspaceService::class)->resolveContext($patient);
        $this->activeTab = 'overview';
        $this->currentEncounter = $this->openEncounterForContext();
        $this->ancVisitData = $this->defaultAncVisitData();
        $this->cwcVisitData = $this->defaultCwcVisitData();
        $this->searchTerm = '';
        $this->searchResults = [];
        $this->pushRecent($patient->id);
    }

    public function clearPatient(): void
    {
        $this->currentPatient = null;
        $this->patientId = null;
        $this->mode = 'home';
        $this->activeTab = 'overview';
        $this->context = ['kind' => 'unknown', 'pregnancy' => null, 'childHealthRecord' => null];
        $this->currentEncounter = null;
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, $this->availableTabs(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function startRegistration(string $kind): void
    {
        $this->registerKind = in_array($kind, ['mother', 'child'], true) ? $kind : 'mother';
        $this->mode = 'register';
        $this->registerData = [
            'first_name' => '',
            'last_name' => '',
            'date_of_birth' => null,
            'phone' => '',
        ];
    }

    public function cancelRegistration(): void
    {
        $this->mode = 'home';
        $this->registerData = [];
    }

    public function submitRegistration(): void
    {
        $branchId = $this->currentBranchId();

        if ($branchId === null) {
            Notification::make()->title('Branch required')->danger()->send();

            return;
        }

        $first = trim((string) ($this->registerData['first_name'] ?? ''));
        $last = trim((string) ($this->registerData['last_name'] ?? ''));

        if ($first === '' || $last === '') {
            Notification::make()->title('Name required')->danger()->send();

            return;
        }

        $factory = $this->registerKind === 'mother'
            ? Patient::factory()->female()
            : Patient::factory()->child();

        $patient = $factory->create([
            'branch_id' => $branchId,
            'first_name' => $first,
            'last_name' => $last,
            'date_of_birth' => $this->registerData['date_of_birth']
                ?? now()->subYears($this->registerKind === 'child' ? 1 : 25)->toDateString(),
            'phone' => $this->registerData['phone'] ?: null,
            'gender' => $this->registerKind === 'mother' ? Gender::FEMALE : Gender::MALE,
        ]);

        if ($this->registerKind === 'mother') {
            PregnancyEpisode::create([
                'patient_id' => $patient->id,
                'branch_id' => $branchId,
                'lmp' => now()->subMonths(4)->toDateString(),
                'edd' => now()->addMonths(5)->toDateString(),
                'booking_date' => now()->toDateString(),
                'recorded_by' => Auth::id(),
            ]);
        } else {
            ChildHealthRecord::create([
                'patient_id' => $patient->id,
                'branch_id' => $branchId,
                'date_of_birth' => $patient->date_of_birth?->toDateString(),
                'recorded_by' => Auth::id(),
            ]);
        }

        Notification::make()->title('Registered')->success()->send();
        $this->selectPatient($patient->id);
    }

    public function startVisit(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] === 'unknown') {
            Notification::make()->title('Register pregnancy or CWC first')->warning()->send();

            return;
        }

        $type = $this->context['kind'] === 'mother'
            ? EncounterType::ANTENATAL
            : EncounterType::CHILD_WELFARE;

        try {
            $this->currentEncounter = app(MchWorkspaceService::class)
                ->ensureEncounter($this->currentPatient, $type);

            Notification::make()->title('Encounter ready')->success()->send();
            $this->activeTab = $this->context['kind'] === 'mother' ? 'anc-visit' : 'cwc-visit';
        } catch (Throwable $e) {
            Notification::make()->title('Could not start visit')->body($e->getMessage())->danger()->send();
        }
    }

    public function saveAncVisit(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] !== 'mother') {
            return;
        }

        try {
            $encounter = $this->currentEncounter
                ?? app(MchWorkspaceService::class)->ensureEncounter($this->currentPatient, EncounterType::ANTENATAL);
            $this->currentEncounter = $encounter;

            $assessment = app(MaternalVisitAssessmentService::class)->record($encounter, [
                ...$this->ancVisitData,
                'pregnancy_episode_id' => $this->context['pregnancy']?->id,
                'recorded_by' => Auth::id(),
            ]);

            if ($assessment->return_date !== null) {
                app(AncReturnScheduler::class)->schedule($assessment);
            }

            Notification::make()->title('ANC visit saved')->success()->send();
            $this->ancVisitData = $this->defaultAncVisitData();
            $this->activeTab = 'overview';
        } catch (Throwable $e) {
            Notification::make()->title('ANC visit failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function saveCwcVisit(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] !== 'child') {
            return;
        }

        try {
            $encounter = $this->currentEncounter
                ?? app(MchWorkspaceService::class)->ensureEncounter($this->currentPatient, EncounterType::CHILD_WELFARE);
            $this->currentEncounter = $encounter;

            $measurements = [];

            if (! empty($this->cwcVisitData['weight'])) {
                $measurements[] = [
                    'type' => GrowthMeasurementType::WEIGHT,
                    'value' => $this->cwcVisitData['weight'],
                    'unit' => 'kg',
                ];
            }

            if (! empty($this->cwcVisitData['length'])) {
                $measurements[] = [
                    'type' => GrowthMeasurementType::LENGTH_HEIGHT,
                    'value' => $this->cwcVisitData['length'],
                    'unit' => 'cm',
                ];
            }

            if (! empty($this->cwcVisitData['muac'])) {
                $measurements[] = [
                    'type' => GrowthMeasurementType::MUAC,
                    'value' => $this->cwcVisitData['muac'],
                    'unit' => 'cm',
                ];
            }

            app(ChildVisitAssessmentService::class)->record($encounter, [
                'child_health_record_id' => $this->context['childHealthRecord']?->id,
                'notes' => $this->cwcVisitData['notes'] ?? null,
                'vitamin_a_given' => (bool) ($this->cwcVisitData['vitamin_a_given'] ?? false),
                'dewormed' => (bool) ($this->cwcVisitData['dewormed'] ?? false),
                'measurements' => $measurements,
                'recorded_by' => Auth::id(),
            ]);

            Notification::make()->title('CWC visit saved')->success()->send();
            $this->cwcVisitData = $this->defaultCwcVisitData();
            $this->activeTab = 'overview';
        } catch (Throwable $e) {
            Notification::make()->title('CWC visit failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function generateEpiDues(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] !== 'child') {
            return;
        }

        $schedule = ImmunizationSchedule::query()
            ->where('is_active', true)
            ->where('target_population', 'child')
            ->first();

        if ($schedule === null) {
            Notification::make()->title('No active child EPI schedule')->warning()->send();

            return;
        }

        $created = app(EpiDueService::class)->generateDueRecords(
            $this->currentPatient,
            $schedule,
            $this->currentPatient->branch_id,
        );

        Notification::make()
            ->title('EPI dues generated')
            ->body($created->count().' scheduled dose(s).')
            ->success()
            ->send();
    }

    public function administerDose(string $recordId): void
    {
        $record = ImmunizationRecord::query()->findOrFail($recordId);

        try {
            app(ImmunizationRecordService::class)->administer($record, [
                'administered_date' => now()->toDateString(),
                'batch_lot' => $this->administerBatchLot !== '' ? $this->administerBatchLot : null,
            ]);
            $this->administerBatchLot = '';
            Notification::make()->title('Dose administered')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Administer failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function declineDose(string $recordId): void
    {
        $record = ImmunizationRecord::query()->findOrFail($recordId);

        if (trim($this->declineReason) === '') {
            Notification::make()->title('Reason required')->danger()->send();

            return;
        }

        try {
            app(ImmunizationRecordService::class)->decline($record, ['reason' => $this->declineReason]);
            $this->declineReason = '';
            Notification::make()->title('Dose declined')->warning()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Decline failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function issueBook(string $unit): void
    {
        if ($this->currentPatient === null) {
            return;
        }

        $owner = $this->context['kind'] === 'mother'
            ? $this->context['pregnancy']
            : $this->context['childHealthRecord'];

        if ($owner === null) {
            Notification::make()->title('No registry owner for book')->warning()->send();

            return;
        }

        $branch = Branch::query()->find($this->currentPatient->branch_id);

        if ($branch === null) {
            return;
        }

        try {
            app(MchBookIssuanceService::class)->issue(
                $owner,
                $branch,
                $unit,
                ['data_consented' => true, 'consented_by' => Auth::id()],
                Auth::user(),
            );
            Notification::make()->title('Book issued')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Book issue failed')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * @return list<string>
     */
    public function availableTabs(): array
    {
        return match ($this->context['kind']) {
            'mother' => ['overview', 'encounter', 'anc-visit', 'vitals', 'books', 'history'],
            'child' => ['overview', 'encounter', 'cwc-visit', 'immunizations', 'growth', 'vitals', 'books', 'history'],
            default => ['overview'],
        };
    }

    public function ancToday()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->ancTodayPatients($branchId) : collect();
    }

    public function cwcToday()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->cwcTodayPatients($branchId) : collect();
    }

    public function epiDue()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->epiDuePatients($branchId) : collect();
    }

    public function highRisk()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->highRiskPregnancies($branchId) : collect();
    }

    public function booksToday()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->booksIssuedToday($branchId) : collect();
    }

    public function recentPatients()
    {
        $branchId = $this->currentBranchId();

        return $branchId
            ? app(MchWorkspaceService::class)->recentPatients($this->recentPatientIds, $branchId)
            : collect();
    }

    public function immunizationRecords()
    {
        if ($this->currentPatient === null) {
            return collect();
        }

        return ImmunizationRecord::query()
            ->with('vaccine')
            ->where('patient_id', $this->currentPatient->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function growthMeasurements()
    {
        if ($this->currentPatient === null) {
            return collect();
        }

        return GrowthMeasurement::query()
            ->where('patient_id', $this->currentPatient->id)
            ->latest('date')
            ->limit(20)
            ->get();
    }

    public function maternalHistory()
    {
        if ($this->currentPatient === null) {
            return collect();
        }

        return MaternalVisitAssessment::query()
            ->where('patient_id', $this->currentPatient->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function vaccinationCardUrl(): ?string
    {
        if ($this->currentPatient === null) {
            return null;
        }

        return VaccinationCard::getUrl(['patientId' => $this->currentPatient->id]);
    }

    /**
     * Clinical vitals overview widget class when Clinical is available.
     *
     * @return class-string|null
     */
    public function vitalsOverviewWidgetClass(): ?string
    {
        return OptionalClass::resolve(
            'Modules\\Clinical\\Filament\\Widgets\\PatientVitalsOverviewWidget',
            'Clinical',
        );
    }

    /**
     * @return array<int, WidgetConfiguration|class-string>
     */
    protected function getFooterWidgets(): array
    {
        if ($this->mode !== 'patient' || empty($this->currentPatient?->id)) {
            return [];
        }

        $patientId = $this->currentPatient->id;
        $widgets = [];

        OptionalClass::when(
            'Modules\\Clinical\\Filament\\Widgets\\PatientVitalsHistoryWidget',
            function (string $widget) use (&$widgets, $patientId): void {
                $widgets[] = $widget::make(['patientId' => $patientId]);
            },
            'Clinical',
        );

        return [
            ...$widgets,
            ...match ($this->context['kind']) {
                'mother' => [
                    PatientMaternalVisitsWidget::make(['patientId' => $patientId]),
                    PatientImmunizationsWidget::make(['patientId' => $patientId]),
                ],
                'child' => [
                    PatientChildVisitsWidget::make(['patientId' => $patientId]),
                    PatientImmunizationsWidget::make(['patientId' => $patientId]),
                    PatientGrowthMeasurementsWidget::make(['patientId' => $patientId]),
                ],
                default => [],
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultAncVisitData(): array
    {
        return [
            'ga_weeks' => null,
            'fetal_heart_rate' => null,
            'fundal_height' => null,
            'return_date' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultCwcVisitData(): array
    {
        return [
            'weight' => null,
            'length' => null,
            'muac' => null,
            'notes' => null,
            'vitamin_a_given' => false,
            'dewormed' => false,
        ];
    }

    private function openEncounterForContext(): ?Encounter
    {
        if ($this->currentPatient === null || $this->context['kind'] === 'unknown') {
            return null;
        }

        $type = $this->context['kind'] === 'mother'
            ? EncounterType::ANTENATAL
            : EncounterType::CHILD_WELFARE;

        return Encounter::query()
            ->where('patient_id', $this->currentPatient->id)
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->whereNotIn('status', ['finished', 'cancelled'])
            ->latest('created_at')
            ->first();
    }

    private function pushRecent(string $patientId): void
    {
        $this->recentPatientIds = collect([$patientId, ...$this->recentPatientIds])
            ->unique()
            ->take(10)
            ->values()
            ->all();

        session()->put('mch_workspace.recent', $this->recentPatientIds);
    }

    private function currentBranchId(): ?string
    {
        return $this->currentPatient?->branch_id
            ?? Auth::user()?->branch_id
            ?? Branch::query()->value('id');
    }
}
