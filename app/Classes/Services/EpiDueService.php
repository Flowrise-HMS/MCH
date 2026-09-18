<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\Patient\Models\Patient;

class EpiDueService
{
    public function __construct(
        private EpiAppointmentScheduler $appointmentScheduler,
    ) {}

    /**
     * Generate SCHEDULED immunization records for a patient based on a schedule.
     *
     * Child schedules anchor every dose on the date of birth. Maternal schedules
     * anchor dose 1 on `$anchorDate` (the ANC booking date) and each later dose on
     * the administered date of the previous dose, so a dose is only generated once
     * the one before it has been given.
     *
     * Only creates records for doses that are due and have no existing record
     * (of any status) for that vaccine+dose.
     *
     * @return Collection<int, ImmunizationRecord>
     */
    public function generateDueRecords(
        Patient $patient,
        ImmunizationSchedule $schedule,
        string $branchId,
        ?Carbon $anchorDate = null,
    ): Collection {
        $now = Carbon::now();
        $childDob = $schedule->isMaternal() ? null : $this->getDateOfBirth($patient);

        $items = $schedule->items()->with('vaccine')->get();

        $existing = ImmunizationRecord::query()
            ->where('patient_id', $patient->id)
            ->get(['vaccine_id', 'dose_sequence', 'status', 'administered_date']);

        $existingKeys = $existing
            ->map(fn (ImmunizationRecord $record): string => $this->doseKey($record->vaccine_id, $record->dose_sequence))
            ->all();

        $administeredDates = $existing
            ->filter(fn (ImmunizationRecord $record): bool => $record->status === ImmunizationStatus::ADMINISTERED && $record->administered_date !== null)
            ->mapWithKeys(fn (ImmunizationRecord $record): array => [
                $this->doseKey($record->vaccine_id, $record->dose_sequence) => $record->administered_date->copy()->startOfDay(),
            ]);

        $records = collect();

        foreach ($items as $item) {
            $key = $this->doseKey($item->vaccine_id, $item->dose_sequence);

            if (in_array($key, $existingKeys, true)) {
                continue;
            }

            $anchor = $schedule->isMaternal()
                ? $this->maternalAnchor($item, $administeredDates, $anchorDate)
                : $childDob;

            if ($anchor === null || $this->dueDateFor($anchor, $item)->isAfter($now)) {
                continue;
            }

            $record = ImmunizationRecord::create([
                'patient_id' => $patient->id,
                'branch_id' => $branchId,
                'vaccine_id' => $item->vaccine_id,
                'dose_sequence' => $item->dose_sequence,
                'status' => ImmunizationStatus::SCHEDULED,
            ]);

            $record->setRelation('vaccine', $item->vaccine);
            $this->appointmentScheduler->schedule($record);

            $records->push($record);
            $existingKeys[] = $key;
        }

        return $records;
    }

    /**
     * Classify a child schedule item for a child relative to today.
     *
     * @return 'not_yet_due'|'due'|'overdue'|'complete'
     */
    public function classifyDose(
        Patient $child,
        ImmunizationScheduleItem $item,
    ): string {
        $existing = ImmunizationRecord::query()
            ->where('patient_id', $child->id)
            ->where('vaccine_id', $item->vaccine_id)
            ->where('dose_sequence', $item->dose_sequence)
            ->where('status', ImmunizationStatus::ADMINISTERED)
            ->exists();

        if ($existing) {
            return 'complete';
        }

        return $this->classifyScheduledDose($this->getDateOfBirth($child), $item);
    }

    /**
     * Classify a dose that is known not to be administered, without querying.
     *
     * @return 'not_yet_due'|'due'|'overdue'
     */
    public function classifyScheduledDose(Carbon $anchorDate, ImmunizationScheduleItem $item): string
    {
        $dueDate = $this->dueDateFor($anchorDate, $item);
        $today = Carbon::today();

        if ($dueDate->isAfter($today)) {
            return 'not_yet_due';
        }

        if ($item->maximum_age_days !== null) {
            $windowEnd = $anchorDate->copy()->addDays((int) $item->maximum_age_days);

            if ($today->gt($windowEnd)) {
                return 'overdue';
            }
        }

        return 'due';
    }

    public function dueDateFor(Carbon $anchorDate, ImmunizationScheduleItem $item): Carbon
    {
        return $anchorDate->copy()->addDays((int) $item->minimum_age_days);
    }

    public function getDateOfBirth(Patient $child): Carbon
    {
        if ($child->date_of_birth instanceof Carbon) {
            return $child->date_of_birth->copy()->startOfDay();
        }

        return Carbon::parse($child->date_of_birth)->startOfDay();
    }

    /**
     * @param  Collection<string, Carbon>  $administeredDates
     */
    private function maternalAnchor(
        ImmunizationScheduleItem $item,
        Collection $administeredDates,
        ?Carbon $anchorDate,
    ): ?Carbon {
        if ((int) $item->dose_sequence <= 1) {
            return ($anchorDate ?? Carbon::today())->copy()->startOfDay();
        }

        return $administeredDates->get($this->doseKey($item->vaccine_id, (int) $item->dose_sequence - 1));
    }

    private function doseKey(string $vaccineId, int|string $doseSequence): string
    {
        return $vaccineId.'|'.$doseSequence;
    }
}
