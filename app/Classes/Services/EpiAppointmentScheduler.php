<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\ModuleAvailability;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\Patient\Models\Patient;

class EpiAppointmentScheduler
{
    /**
     * Book a single Appointment for a SCHEDULED immunization dose.
     *
     * Uses the Appointment module when enabled. Idempotency key:
     * `epi-dose:{immunization_record_id}`. Overdue doses book for today;
     * future doses book on DOB + schedule item minimum_age_days.
     */
    public function schedule(ImmunizationRecord $record): mixed
    {
        if (! ModuleAvailability::appointmentEnabled()) {
            return null;
        }

        if ($record->status !== ImmunizationStatus::SCHEDULED) {
            return null;
        }

        $item = $this->matchingChildScheduleItem($record);

        if ($item === null) {
            return null;
        }

        $appointmentClass = OptionalClass::resolve('Modules\\Appointment\\Models\\Appointment', 'Appointment');

        if ($appointmentClass === null) {
            return null;
        }

        $record->loadMissing('patient');

        $patient = $record->patient;

        if ($patient === null || $patient->date_of_birth === null) {
            return null;
        }

        $config = config('mch.epi_appointments');
        $startHour = $config['start_hour'] ?? 9;
        $startMinute = $config['start_minute'] ?? 0;
        $durationMinutes = $config['duration_minutes'] ?? 30;
        $externalReference = 'epi-dose:'.$record->id;

        return DB::transaction(function () use (
            $appointmentClass,
            $record,
            $patient,
            $item,
            $startHour,
            $startMinute,
            $durationMinutes,
            $externalReference,
        ) {
            $existing = $appointmentClass::query()
                ->where('branch_id', $record->branch_id)
                ->where('external_reference', $externalReference)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $startAt = $this->appointmentStartAt($patient, $item, $startHour, $startMinute);

            return $appointmentClass::create([
                'branch_id' => $record->branch_id,
                'patient_id' => $record->patient_id,
                'status' => 'booked',
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addMinutes($durationMinutes),
                'external_reference' => $externalReference,
                'created_by' => $record->recorded_by,
            ]);
        });
    }

    private function matchingChildScheduleItem(ImmunizationRecord $record): ?ImmunizationScheduleItem
    {
        return ImmunizationScheduleItem::query()
            ->where('vaccine_id', $record->vaccine_id)
            ->where('dose_sequence', $record->dose_sequence)
            ->whereHas('schedule', function ($query): void {
                $query->where('is_active', true)
                    ->where('target_population', 'child');
            })
            ->first();
    }

    private function appointmentStartAt(
        Patient $patient,
        ImmunizationScheduleItem $item,
        int $startHour,
        int $startMinute,
    ): Carbon {
        $dob = $patient->date_of_birth instanceof Carbon
            ? $patient->date_of_birth->copy()->startOfDay()
            : Carbon::parse($patient->date_of_birth)->startOfDay();

        $dueDate = $dob->copy()->addDays((int) $item->minimum_age_days);
        $today = Carbon::today();

        if ($dueDate->lt($today)) {
            $dueDate = $today->copy();
        }

        return $dueDate->setTime($startHour, $startMinute);
    }
}
