<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Support\ModuleAvailability;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\Patient\Models\Patient;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EpiAppointmentScheduler
{
    /**
     * Book a single Appointment for a SCHEDULED immunization dose.
     *
     * Uses the Appointment module when enabled. Idempotency key:
     * `epi-dose:{immunization_record_id}`. Child doses book on DOB + schedule
     * item minimum_age_days (today when overdue); maternal doses are generated
     * only once due, so they book for today.
     */
    public function schedule(ImmunizationRecord $record): mixed
    {
        if (! ModuleAvailability::appointmentEnabled()) {
            return null;
        }

        if ($record->status !== ImmunizationStatus::SCHEDULED) {
            return null;
        }

        $item = $this->matchingScheduleItem($record);

        if ($item === null) {
            return null;
        }

        $appointmentClass = OptionalClass::resolve('Modules\\Appointment\\Models\\Appointment', 'Appointment');

        if ($appointmentClass === null) {
            return null;
        }

        $record->loadMissing('patient');

        $patient = $record->patient;

        // Child doses are anchored on the date of birth unless the record
        // already carries a due date; maternal doses never need a DOB.
        if ($patient === null || ($record->due_date === null && ! $item->schedule?->isMaternal() && $patient->date_of_birth === null)) {
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

            $startAt = $this->appointmentStartAt($record, $patient, $item, $startHour, $startMinute);

            $data = [
                'branch_id' => $record->branch_id,
                'patient_id' => $record->patient_id,
                'status' => 'booked',
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addMinutes($durationMinutes),
                'external_reference' => $externalReference,
                'created_by' => $record->recorded_by,
            ];

            // Book through the scheduling service so the conflict check and the
            // sync outbox apply exactly as for manually booked appointments.
            $schedulingService = OptionalClass::resolve('Modules\\Appointment\\Classes\\Services\\AppointmentSchedulingService', 'Appointment');

            if ($schedulingService === null) {
                return $appointmentClass::create($data);
            }

            try {
                return app($schedulingService)->schedule($data);
            } catch (HttpException $exception) {
                if ($exception->getStatusCode() !== 422) {
                    throw $exception;
                }

                Log::warning('EPI appointment skipped because of a scheduling conflict.', [
                    'immunization_record_id' => $record->id,
                    'message' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function matchingScheduleItem(ImmunizationRecord $record): ?ImmunizationScheduleItem
    {
        return ImmunizationScheduleItem::query()
            ->with('schedule')
            ->where('vaccine_id', $record->vaccine_id)
            ->where('dose_sequence', $record->dose_sequence)
            ->whereHas('schedule', fn ($query) => $query->where('is_active', true))
            ->first();
    }

    /**
     * Doses that are already overdue are booked for today rather than in the
     * past, so the visit shows up on today's list.
     */
    private function appointmentStartAt(
        ImmunizationRecord $record,
        Patient $patient,
        ImmunizationScheduleItem $item,
        int $startHour,
        int $startMinute,
    ): Carbon {
        $today = Carbon::today();

        if ($record->due_date !== null) {
            $dueDate = $record->due_date->copy()->startOfDay();
        } elseif ($item->schedule?->isMaternal()) {
            $dueDate = $today->copy();
        } else {
            $dob = $patient->date_of_birth instanceof Carbon
                ? $patient->date_of_birth->copy()->startOfDay()
                : Carbon::parse($patient->date_of_birth)->startOfDay();

            $dueDate = $dob->copy()->addDays((int) $item->minimum_age_days);
        }

        if ($dueDate->lt($today)) {
            $dueDate = $today->copy();
        }

        return $dueDate->setTime($startHour, $startMinute);
    }
}
