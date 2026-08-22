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
     * Generate SCHEDULED immunization records for a child based on a schedule.
     *
     * Only creates records for doses where the child has reached the minimum age
     * and no existing record (of any status) covers that vaccine+dose.
     *
     * @return Collection<int, ImmunizationRecord>
     */
    public function generateDueRecords(
        Patient $child,
        ImmunizationSchedule $schedule,
        string $branchId,
    ): Collection {
        $dob = $this->getDateOfBirth($child);
        $now = Carbon::now();

        $items = $schedule->items()->with('vaccine')->get();

        $existingKeys = ImmunizationRecord::query()
            ->where('patient_id', $child->id)
            ->get(['vaccine_id', 'dose_sequence'])
            ->map(fn (ImmunizationRecord $record): string => $record->vaccine_id.'|'.$record->dose_sequence)
            ->all();

        $records = collect();

        foreach ($items as $item) {
            $key = $item->vaccine_id.'|'.$item->dose_sequence;

            if (in_array($key, $existingKeys, true)) {
                continue;
            }

            $dueDate = $this->dueDateFor($dob, $item);

            if ($dueDate->isAfter($now)) {
                continue;
            }

            $record = ImmunizationRecord::create([
                'patient_id' => $child->id,
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
     * Classify a schedule item for a child relative to today.
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

        $dob = $this->getDateOfBirth($child);
        $dueDate = $this->dueDateFor($dob, $item);
        $today = Carbon::today();

        if ($dueDate->isAfter($today)) {
            return 'not_yet_due';
        }

        if ($item->maximum_age_days !== null) {
            $windowEnd = $dob->copy()->addDays((int) $item->maximum_age_days);

            if ($today->gt($windowEnd)) {
                return 'overdue';
            }
        }

        return 'due';
    }

    private function dueDateFor(Carbon $dob, ImmunizationScheduleItem $item): Carbon
    {
        return $dob->copy()->addDays((int) $item->minimum_age_days);
    }

    private function getDateOfBirth(Patient $child): Carbon
    {
        if ($child->date_of_birth instanceof Carbon) {
            return $child->date_of_birth->copy()->startOfDay();
        }

        return Carbon::parse($child->date_of_birth)->startOfDay();
    }
}
