<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Facades\DB;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;

class ImmunizationRecordService
{
    /**
     * Transition a record to ADMINISTERED status.
     *
     * Enforces status-aware uniqueness: prevents duplicate ADMINISTERED records
     * for the same (patient_id, vaccine_id, dose_sequence) — but allows
     * DECLINED → ADMINISTERED transitions.
     *
     * @param  array<string, mixed>  $data
     */
    public function administer(ImmunizationRecord $record, array $data): ImmunizationRecord
    {
        $existingAdministered = ImmunizationRecord::query()
            ->where('patient_id', $record->patient_id)
            ->where('vaccine_id', $record->vaccine_id)
            ->where('dose_sequence', $record->dose_sequence)
            ->where('status', ImmunizationStatus::ADMINISTERED)
            ->where('id', '!=', $record->id)
            ->exists();

        if ($existingAdministered) {
            throw new \RuntimeException(
                "A dose of {$record->vaccine->name} (sequence {$record->dose_sequence}) has already been administered for this patient."
            );
        }

        return DB::transaction(function () use ($record, $data): ImmunizationRecord {
            $record->update([
                'status' => ImmunizationStatus::ADMINISTERED,
                'administered_date' => $data['administered_date'] ?? now()->toDateString(),
                'batch_lot' => $data['batch_lot'] ?? $record->batch_lot,
                'site' => $data['site'] ?? $record->site,
                'route' => $data['route'] ?? $record->route,
                'reason' => null,
            ]);

            return $record->fresh();
        });
    }

    /**
     * Transition a record to DECLINED status.
     *
     * @param  array<string, mixed>  $data
     */
    public function decline(ImmunizationRecord $record, array $data): ImmunizationRecord
    {
        return DB::transaction(function () use ($record, $data): ImmunizationRecord {
            $record->update([
                'status' => ImmunizationStatus::DECLINED,
                'reason' => $data['reason'] ?? null,
                'administered_date' => null,
                'batch_lot' => null,
            ]);

            return $record->fresh();
        });
    }
}
