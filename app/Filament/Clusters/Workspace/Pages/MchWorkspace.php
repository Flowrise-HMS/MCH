<?php

namespace Modules\MCH\Filament\Clusters\Workspace\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Carbon;
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
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Filament\Clusters\MCH\Pages\VaccinationCard;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Schemas\ChildVisitAssessmentForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Schemas\MaternalVisitAssessmentForm;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Schemas\PregnancyEpisodeForm;
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
use Modules\Patient\Classes\Services\PatientService;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\PatientRelationshipType;
use Modules\Patient\Models\Patient;
use Modules\Patient\Models\PatientRelationship;
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
        $this->registerForm->fill();
        $this->ancVisitForm->fill();
        $this->cwcVisitForm->fill();

        if ($this->patientId) {
            $this->selectPatient($this->patientId);
        }
    }

    public function registerForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Demographics')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->required()->maxLength(100),
                        TextInput::make('last_name')->required()->maxLength(100),
                        DatePicker::make('date_of_birth')->required()->maxDate(now()),
                        Select::make('gender')
                            ->options(Gender::class)
                            ->required()
                            ->visible(fn (): bool => $this->registerKind === 'child'),
                        TextInput::make('phone')->tel()->maxLength(32),
                        Select::make('mother_patient_id')
                            ->label('Mother (optional)')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => $this->motherOptions($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => $value ? Patient::query()->find($value)?->full_name : null)
                            ->visible(fn (): bool => $this->registerKind === 'child'),
                    ]),
                Section::make('Booking')
                    ->columns(2)
                    ->visible(fn (): bool => $this->registerKind === 'mother')
                    ->schema(PregnancyEpisodeForm::obstetricElements()),
                Section::make('Risk')
                    ->visible(fn (): bool => $this->registerKind === 'mother')
                    ->schema(PregnancyEpisodeForm::riskElements()),
            ])
            ->statePath('registerData');
    }

    public function ancVisitForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    ...MaternalVisitAssessmentForm::vitalsElements(),
                    ...MaternalVisitAssessmentForm::quickElements(),
                ]),
            ])
            ->statePath('ancVisitData');
    }

    public function cwcVisitForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    ...ChildVisitAssessmentForm::anthropometryElements(),
                    ...ChildVisitAssessmentForm::quickElements(),
                ]),
            ])
            ->statePath('cwcVisitData');
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
        $this->ancVisitForm->fill();
        $this->cwcVisitForm->fill();
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
        $this->registerForm->fill([
            'gender' => $this->registerKind === 'mother' ? Gender::FEMALE->value : null,
            'booking_date' => now()->toDateString(),
        ]);
    }

    public function cancelRegistration(): void
    {
        $this->mode = 'home';
        $this->registerForm->fill();
    }

    public function submitRegistration(): void
    {
        $branchId = $this->currentBranchId();

        if ($branchId === null) {
            Notification::make()->title('Branch required')->danger()->send();

            return;
        }

        $data = $this->registerForm->getState();

        try {
            $patient = app(PatientService::class)->create([
                'branch_id' => $branchId,
                'first_name' => trim((string) $data['first_name']),
                'last_name' => trim((string) $data['last_name']),
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $this->registerKind === 'mother' ? Gender::FEMALE->value : $data['gender'],
                'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
            ]);

            if ($this->registerKind === 'mother') {
                PregnancyEpisode::create([
                    ...collect($data)->only([
                        'gravida', 'parity', 'lmp', 'edd', 'edd_source', 'multiple_gestation',
                        'booking_date', 'risk_factors', 'risk_override', 'risk_level',
                    ])->filter(fn ($value): bool => $value !== null && $value !== '')->all(),
                    'patient_id' => $patient->id,
                    'branch_id' => $branchId,
                    'recorded_by' => Auth::id(),
                ]);
            } else {
                $this->registerChild($patient, $branchId, $data['mother_patient_id'] ?? null);
            }
        } catch (Throwable $e) {
            Notification::make()->title('Registration failed')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->registerForm->fill();
        Notification::make()->title('Registered')->body("{$patient->full_name} ({$patient->mrn})")->success()->send();
        $this->selectPatient($patient->id);
    }

    public function startVisit(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] === 'unknown') {
            Notification::make()->title('Register pregnancy or CWC first')->warning()->send();

            return;
        }

        try {
            $this->currentEncounter = app(MchWorkspaceService::class)
                ->ensureEncounter($this->currentPatient, $this->encounterTypeForContext());

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

        $data = $this->ancVisitForm->getState();

        try {
            $encounter = $this->currentEncounter
                ?? app(MchWorkspaceService::class)->ensureEncounter($this->currentPatient, EncounterType::ANTENATAL);
            $this->currentEncounter = $encounter;

            $assessment = app(MaternalVisitAssessmentService::class)->record($encounter, [
                ...$data,
                'fundal_height_unit' => 'cm',
                'pregnancy_episode_id' => $this->context['pregnancy']?->id,
                'recorded_by' => Auth::id(),
            ]);

            if ($assessment->return_date !== null) {
                app(AncReturnScheduler::class)->schedule($assessment);
            }

            Notification::make()->title('ANC visit saved')->success()->send();
            $this->ancVisitForm->fill();
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

        $data = $this->cwcVisitForm->getState();

        try {
            $encounter = $this->currentEncounter
                ?? app(MchWorkspaceService::class)->ensureEncounter($this->currentPatient, EncounterType::CHILD_WELFARE);
            $this->currentEncounter = $encounter;

            $anthropometry = [
                'weight' => [GrowthMeasurementType::WEIGHT, 'kg'],
                'length' => [GrowthMeasurementType::LENGTH_HEIGHT, 'cm'],
                'muac' => [GrowthMeasurementType::MUAC, 'cm'],
                'head_circumference' => [GrowthMeasurementType::HEAD_CIRCUMFERENCE, 'cm'],
            ];

            $measurements = [];

            foreach ($anthropometry as $key => [$type, $unit]) {
                if (filled($data[$key] ?? null)) {
                    $measurements[] = ['type' => $type, 'value' => $data[$key], 'unit' => $unit];
                }
            }

            app(ChildVisitAssessmentService::class)->record($encounter, [
                ...collect($data)->except(array_keys($anthropometry))->all(),
                'child_health_record_id' => $this->context['childHealthRecord']?->id,
                'measurements' => $measurements,
                'recorded_by' => Auth::id(),
            ]);

            Notification::make()->title('CWC visit saved')->success()->send();
            $this->cwcVisitForm->fill();
            $this->activeTab = 'overview';
        } catch (Throwable $e) {
            Notification::make()->title('CWC visit failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function generateEpiDues(): void
    {
        if ($this->currentPatient === null || $this->context['kind'] === 'unknown') {
            return;
        }

        $isMother = $this->context['kind'] === 'mother';
        $schedule = ImmunizationSchedule::activeFor(
            $isMother ? ImmunizationSchedule::TARGET_MATERNAL : ImmunizationSchedule::TARGET_CHILD,
        );

        if ($schedule === null) {
            Notification::make()
                ->title($isMother ? 'No active maternal TT schedule' : 'No active child EPI schedule')
                ->warning()
                ->send();

            return;
        }

        $created = app(EpiDueService::class)->generateDueRecords(
            $this->currentPatient,
            $schedule,
            $this->currentPatient->branch_id,
            $isMother ? ($this->context['pregnancy']?->booking_date ?? Carbon::today()) : null,
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

    public function recordOutcomeAction(): Action
    {
        $options = collect(PregnancyOutcome::cases())
            ->reject(fn (PregnancyOutcome $outcome): bool => $outcome === PregnancyOutcome::ACTIVE)
            ->mapWithKeys(fn (PregnancyOutcome $outcome): array => [$outcome->value => $outcome->getLabel()])
            ->all();

        return Action::make('recordOutcome')
            ->label('Record pregnancy outcome')
            ->color('warning')
            ->visible(fn (): bool => $this->context['kind'] === 'mother')
            ->schema([
                Select::make('outcome')
                    ->options($options)
                    ->required()
                    ->helperText('Closes the active pregnancy episode. Delivery details are recorded separately.'),
            ])
            ->action(fn (array $data) => $this->recordOutcome((string) $data['outcome']));
    }

    public function recordOutcome(string $outcome): void
    {
        $episode = $this->context['pregnancy'] ?? null;

        if ($this->currentPatient === null || ! $episode instanceof PregnancyEpisode) {
            return;
        }

        $episode->update(['outcome' => PregnancyOutcome::from($outcome)]);

        Notification::make()->title('Pregnancy outcome recorded')->success()->send();
        $this->selectPatient($this->currentPatient->id);
    }

    public function issueBookAction(): Action
    {
        $unit = $this->bookUnitForContext();

        return Action::make('issueBook')
            ->label($unit ? "Issue {$unit} book" : 'Issue book')
            ->visible(fn (): bool => $unit !== null)
            ->schema([
                Checkbox::make('data_consented')
                    ->label('Client consents to their MCH data being used for follow-up and reporting')
                    ->default(false),
            ])
            ->action(fn (array $data) => $this->issueBook($data['data_consented'] ?? false));
    }

    public function issueBook(bool $dataConsented = false): void
    {
        $unit = $this->bookUnitForContext();

        if ($this->currentPatient === null || $unit === null) {
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
                ['data_consented' => $dataConsented, 'consented_by' => Auth::id()],
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
            'mother' => ['overview', 'encounter', 'anc-visit', 'immunizations', 'vitals', 'books', 'history'],
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

    public function eddDueSoon()
    {
        $branchId = $this->currentBranchId();

        return $branchId ? app(MchWorkspaceService::class)->eddDueSoon($branchId) : collect();
    }

    /**
     * Short GA label ("32w 4d") for a board row's active pregnancy.
     */
    public function gestationalAgeLabel(?PregnancyEpisode $episode): ?string
    {
        $ga = $episode?->gestationalAgeAt(Carbon::today());

        return $ga === null ? null : "{$ga['weeks']}w {$ga['days']}d";
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

    /**
     * Gestational age today for the active pregnancy, for the patient banner.
     */
    public function gestationalAgeToday(): ?string
    {
        $pregnancy = $this->context['pregnancy'] ?? null;

        return $pregnancy instanceof PregnancyEpisode ? $this->gestationalAgeLabel($pregnancy) : null;
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
     * @return array<string, string>
     */
    private function motherOptions(string $search): array
    {
        return app(PatientSearchService::class)
            ->search($search, 10)
            ->filter(fn (Patient $patient): bool => $patient->gender === Gender::FEMALE)
            ->mapWithKeys(fn (Patient $patient): array => [$patient->id => $patient->full_name.' ('.($patient->mrn ?? '—').')'])
            ->all();
    }

    private function registerChild(Patient $child, string $branchId, ?string $motherPatientId): void
    {
        $mother = filled($motherPatientId) ? Patient::query()->find($motherPatientId) : null;

        $episodeId = $mother === null ? null : PregnancyEpisode::query()
            ->where('patient_id', $mother->id)
            ->where('outcome', '!=', PregnancyOutcome::ACTIVE)
            ->latest('edd')
            ->value('id');

        ChildHealthRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branchId,
            'pregnancy_episode_id' => $episodeId,
            'date_of_birth' => $child->date_of_birth?->toDateString(),
            'recorded_by' => Auth::id(),
        ]);

        if ($mother !== null) {
            PatientRelationship::query()->firstOrCreate([
                'subject_type' => $child->getMorphClass(),
                'subject_id' => $child->id,
                'object_type' => $mother->getMorphClass(),
                'object_id' => $mother->id,
                'type' => PatientRelationshipType::MOTHER,
            ], ['created_by' => Auth::id()]);
        }
    }

    private function encounterTypeForContext(): EncounterType
    {
        return $this->context['kind'] === 'mother'
            ? EncounterType::ANTENATAL
            : EncounterType::CHILD_WELFARE;
    }

    private function bookUnitForContext(): ?string
    {
        return match ($this->context['kind']) {
            'mother' => 'ANC',
            'child' => 'CWC',
            default => null,
        };
    }

    private function openEncounterForContext(): ?Encounter
    {
        if ($this->currentPatient === null || $this->context['kind'] === 'unknown') {
            return null;
        }

        return app(MchWorkspaceService::class)
            ->findOpenEncounter($this->currentPatient, $this->encounterTypeForContext());
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
