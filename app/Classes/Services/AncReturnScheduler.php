<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\ModuleAvailability;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Models\MaternalVisitAssessment;

class AncReturnScheduler
{
    /**
     * @return Collection<int, mixed>|null
     */
    public function schedule(MaternalVisitAssessment $assessment): ?Collection
    {
        if (! ModuleAvailability::appointmentEnabled() || $assessment->return_date === null) {
            return null;
        }

        $appointmentClass = OptionalClass::resolve('Modules\\Appointment\\Models\\Appointment', 'Appointment');
        $ruleClass = OptionalClass::resolve('Modules\\Appointment\\Models\\AppointmentRecurrenceRule', 'Appointment');
        $expander = OptionalClass::resolve('Modules\\Appointment\\Classes\\Services\\RecurrenceExpansionService', 'Appointment');

        if ($appointmentClass === null || $ruleClass === null || $expander === null) {
            return null;
        }

        $config = config('mch.anc_returns');

        return DB::transaction(function () use ($appointmentClass, $ruleClass, $expander, $assessment, $config): Collection {
            $startHour = $config['start_hour'] ?? 9;
            $startMinute = $config['start_minute'] ?? 0;
            $durationMinutes = $config['duration_minutes'] ?? 30;

            $parent = $appointmentClass::query()
                ->where('branch_id', $assessment->branch_id)
                ->where('external_reference', 'anc-return:'.$assessment->id)
                ->firstOr(function () use ($appointmentClass, $assessment, $startHour, $startMinute, $durationMinutes) {
                    $startAt = $assessment->return_date->copy()->startOfDay()->setTime($startHour, $startMinute);

                    return $appointmentClass::create([
                        'branch_id' => $assessment->branch_id,
                        'patient_id' => $assessment->patient_id,
                        'status' => 'booked',
                        'start_at' => $startAt,
                        'end_at' => $startAt->copy()->addMinutes($durationMinutes),
                        'external_reference' => 'anc-return:'.$assessment->id,
                        'created_by' => $assessment->recorded_by,
                    ]);
                });

            $rule = $ruleClass::query()
                ->where('appointment_id', $parent->id)
                ->firstOr(function () use ($ruleClass, $parent, $assessment, $config) {
                    return $ruleClass::create([
                        'appointment_id' => $parent->id,
                        'branch_id' => $assessment->branch_id,
                        'frequency' => $config['frequency'] ?? 'weekly',
                        'interval' => $config['interval'] ?? 4,
                        'occurrence_count' => $config['occurrence_count'] ?? 8,
                        'timezone' => $config['timezone'] ?? config('app.timezone'),
                    ]);
                });

            return app($expander)->expand($rule);
        });
    }
}
