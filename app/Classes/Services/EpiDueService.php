<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\Patient\Models\Patient;

class EpiDueService
{
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

            $dueDate = $dob->copy()->addDays($item->minimum_age_days);

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

            $records->push($record->setRelation('vaccine', $item->vaccine));
            $existingKeys[] = $key;
        }

        return $records;
    }

    private function getDateOfBirth(Patient $child): Carbon
    {
        if ($child->date_of_birth instanceof Carbon) {
            return $child->date_of_birth->copy()->startOfDay();
        }

        return Carbon::parse($child->date_of_birth)->startOfDay();
    }
}
