<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Clinical\Classes\Services\EncounterService;
use Modules\Clinical\Enums\EncounterStatus;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Support\ModuleAvailability;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;

class MchWorkspaceService
{
    public function __construct(
        private EpiDueService $epiDueService,
        private EncounterService $encounterService,
    ) {}

    /**
     * @return array{kind: 'mother'|'child'|'unknown', pregnancy: ?PregnancyEpisode, childHealthRecord: ?ChildHealthRecord}
     */
    public function resolveContext(Patient $patient): array
    {
        $pregnancy = PregnancyEpisode::query()
            ->where('patient_id', $patient->id)
            ->where('outcome', PregnancyOutcome::ACTIVE)
            ->latest('created_at')
            ->first();

        if ($pregnancy !== null) {
            return [
                'kind' => 'mother',
                'pregnancy' => $pregnancy,
                'childHealthRecord' => null,
            ];
        }

        $childRecord = ChildHealthRecord::query()
            ->where('patient_id', $patient->id)
            ->where('status', ChildHealthRecordStatus::ACTIVE)
            ->latest('created_at')
            ->first();

        if ($childRecord !== null) {
            return [
                'kind' => 'child',
                'pregnancy' => null,
                'childHealthRecord' => $childRecord,
            ];
        }

        return [
            'kind' => 'unknown',
            'pregnancy' => null,
            'childHealthRecord' => null,
        ];
    }

    /**
     * @return Collection<int, Patient>
     */
    public function ancTodayPatients(string $branchId): Collection
    {
        $today = Carbon::today()->toDateString();
        $patientIds = collect();

        $patientIds = $patientIds->merge(
            MaternalVisitAssessment::query()
                ->where('branch_id', $branchId)
                ->whereDate('return_date', $today)
                ->pluck('patient_id')
        );

        $patientIds = $patientIds->merge(
            Encounter::query()
                ->where('branch_id', $branchId)
                ->where('type', EncounterType::ANTENATAL)
                ->whereNotIn('status', [EncounterStatus::FINISHED, EncounterStatus::CANCELLED])
                ->whereNotNull('patient_id')
                ->pluck('patient_id')
        );

        if (ModuleAvailability::appointmentEnabled()) {
            $appointmentClass = OptionalClass::resolve('Modules\\Appointment\\Models\\Appointment', 'Appointment');

            if ($appointmentClass !== null) {
                $patientIds = $patientIds->merge(
                    $appointmentClass::query()
                        ->where('branch_id', $branchId)
                        ->whereDate('start_at', $today)
                        ->where('external_reference', 'like', 'anc-return:%')
                        ->pluck('patient_id')
                );
            }
        }

        return $this->patientsByIds($patientIds->unique()->filter()->values()->all(), $branchId);
    }

    /**
     * @return Collection<int, Patient>
     */
    public function cwcTodayPatients(string $branchId): Collection
    {
        $patientIds = Encounter::query()
            ->where('branch_id', $branchId)
            ->where('type', EncounterType::CHILD_WELFARE)
            ->whereNotIn('status', [EncounterStatus::FINISHED, EncounterStatus::CANCELLED])
            ->whereNotNull('patient_id')
            ->pluck('patient_id');

        return $this->patientsByIds($patientIds->unique()->filter()->values()->all(), $branchId);
    }

    /**
     * @return Collection<int, array{patient: Patient, overdue: bool, scheduled_count: int}>
     */
    public function epiDuePatients(string $branchId): Collection
    {
        $schedules = ImmunizationSchedule::query()
            ->where('is_active', true)
            ->with('items')
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        /** @var array<string, array{item: ImmunizationScheduleItem, maternal: bool}> $itemsByKey */
        $itemsByKey = [];

        foreach ($schedules as $schedule) {
            foreach ($schedule->items as $item) {
                $itemsByKey[$item->vaccine_id.'|'.$item->dose_sequence] = [
                    'item' => $item,
                    'maternal' => $schedule->isMaternal(),
                ];
            }
        }

        $records = ImmunizationRecord::query()
            ->with(['patient', 'vaccine'])
            ->where('branch_id', $branchId)
            ->where('status', ImmunizationStatus::SCHEDULED)
            ->get();

        $grouped = [];

        foreach ($records as $record) {
            $entry = $itemsByKey[$record->vaccine_id.'|'.$record->dose_sequence] ?? null;

            if ($entry === null || $record->patient === null || $record->patient->date_of_birth === null) {
                continue;
            }

            // Maternal doses are only generated once due, so a SCHEDULED row is due by construction.
            $classification = $entry['maternal']
                ? 'due'
                : $this->epiDueService->classifyScheduledDose(
                    $this->epiDueService->getDateOfBirth($record->patient),
                    $entry['item'],
                );

            if (! in_array($classification, ['due', 'overdue'], true)) {
                continue;
            }

            $patientId = $record->patient_id;

            if (! isset($grouped[$patientId])) {
                $grouped[$patientId] = [
                    'patient' => $record->patient,
                    'overdue' => false,
                    'scheduled_count' => 0,
                ];
            }

            $grouped[$patientId]['scheduled_count']++;

            if ($classification === 'overdue') {
                $grouped[$patientId]['overdue'] = true;
            }
        }

        return collect(array_values($grouped));
    }

    /**
     * @return Collection<int, PregnancyEpisode>
     */
    public function highRiskPregnancies(string $branchId): Collection
    {
        return PregnancyEpisode::query()
            ->with('patient')
            ->where('branch_id', $branchId)
            ->where('outcome', PregnancyOutcome::ACTIVE)
            ->where('risk_level', RiskLevel::HIGH)
            ->latest('updated_at')
            ->limit(25)
            ->get();
    }

    /**
     * Active pregnancies expected to deliver within the next `$days` days.
     *
     * @return Collection<int, PregnancyEpisode>
     */
    public function eddDueSoon(string $branchId, int $days = 14): Collection
    {
        return PregnancyEpisode::query()
            ->with('patient')
            ->where('branch_id', $branchId)
            ->where('outcome', PregnancyOutcome::ACTIVE)
            ->whereBetween('edd', [Carbon::today(), Carbon::today()->addDays($days)])
            ->orderBy('edd')
            ->limit(25)
            ->get();
    }

    /**
     * @return Collection<int, MchRecord>
     */
    public function booksIssuedToday(string $branchId): Collection
    {
        return MchRecord::query()
            ->where('branch_id', $branchId)
            ->where('status', MchRecordStatus::ACTIVE)
            ->whereDate('issue_date', Carbon::today())
            ->latest('issue_date')
            ->limit(25)
            ->get();
    }

    /**
     * Today's open encounter of the given type for the patient, if any.
     */
    public function findOpenEncounter(Patient $patient, EncounterType $type, ?string $branchId = null): ?Encounter
    {
        return Encounter::query()
            ->where('patient_id', $patient->id)
            ->where('branch_id', $branchId ?? $patient->branch_id)
            ->where('type', $type)
            ->whereDate('created_at', Carbon::today())
            ->whereNotIn('status', [EncounterStatus::FINISHED, EncounterStatus::CANCELLED])
            ->latest('created_at')
            ->first();
    }

    public function ensureEncounter(Patient $patient, EncounterType $type, ?string $branchId = null): Encounter
    {
        $existing = $this->findOpenEncounter($patient, $type, $branchId);

        if ($existing !== null) {
            return $existing;
        }

        $encounter = $this->encounterService->createForPatient($patient, $type);

        if ($encounter->status === EncounterStatus::PLANNED && $encounter->canTransitionTo(EncounterStatus::ARRIVED)) {
            return $this->encounterService->admitPatient($encounter);
        }

        return $encounter;
    }

    /**
     * @param  list<string>  $ids
     * @return Collection<int, Patient>
     */
    public function recentPatients(array $ids, string $branchId): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $patients = Patient::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (string $id) => $patients->get($id))
            ->filter()
            ->values();
    }

    /**
     * @param  list<string>  $patientIds
     * @return Collection<int, Patient>
     */
    private function patientsByIds(array $patientIds, string $branchId): Collection
    {
        if ($patientIds === []) {
            return collect();
        }

        return Patient::query()
            ->with('activePregnancyEpisode')
            ->where('branch_id', $branchId)
            ->whereIn('id', $patientIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
