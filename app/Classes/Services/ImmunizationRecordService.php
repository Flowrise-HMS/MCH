<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Support\ModuleAvailability;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use RuntimeException;

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
        return DB::transaction(function () use ($record, $data): ImmunizationRecord {
            $locked = ImmunizationRecord::query()
                ->whereKey($record->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertMedicationAvailable($data['medication_id'] ?? $locked->medication_id);

            $existingAdministered = ImmunizationRecord::query()
                ->where('patient_id', $locked->patient_id)
                ->where('vaccine_id', $locked->vaccine_id)
                ->where('dose_sequence', $locked->dose_sequence)
                ->where('status', ImmunizationStatus::ADMINISTERED)
                ->where('id', '!=', $locked->id)
                ->lockForUpdate()
                ->exists();

            if ($existingAdministered) {
                $locked->loadMissing('vaccine');

                throw new RuntimeException(
                    "A dose of {$locked->vaccine->name} (sequence {$locked->dose_sequence}) has already been administered for this patient."
                );
            }

            $locked->update([
                'status' => ImmunizationStatus::ADMINISTERED,
                'administered_date' => $data['administered_date'] ?? now()->toDateString(),
                'batch_lot' => $data['batch_lot'] ?? $locked->batch_lot,
                'site' => $data['site'] ?? $locked->site,
                'route' => $data['route'] ?? $locked->route,
                'medication_id' => $data['medication_id'] ?? $locked->medication_id,
                'reason' => null,
            ]);

            return $locked->fresh();
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
            $locked = ImmunizationRecord::query()
                ->whereKey($record->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->update([
                'status' => ImmunizationStatus::DECLINED,
                'reason' => $data['reason'] ?? null,
                'administered_date' => null,
                'batch_lot' => null,
            ]);

            return $locked->fresh();
        });
    }

    private function assertMedicationAvailable(mixed $medicationId): void
    {
        if ($medicationId === null || $medicationId === '') {
            return;
        }

        if (! ModuleAvailability::pharmacyEnabled()) {
            throw new RuntimeException('Pharmacy module is disabled; medication_id cannot be set on immunization records.');
        }

        $medicationClass = 'Modules\\Pharmacy\\Models\\Medication';

        if (! class_exists($medicationClass)) {
            throw new RuntimeException('Pharmacy medication model is unavailable.');
        }

        if (! $medicationClass::query()->whereKey($medicationId)->exists()) {
            throw new RuntimeException('The selected pharmacy medication does not exist.');
        }
    }
}
