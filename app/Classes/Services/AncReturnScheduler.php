<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Collection;
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

        $parent = $appointmentClass::query()
            ->where('branch_id', $assessment->branch_id)
            ->where('external_reference', 'anc-return:'.$assessment->id)
            ->firstOr(function () use ($appointmentClass, $assessment) {
                return $appointmentClass::create([
                    'branch_id' => $assessment->branch_id,
                    'patient_id' => $assessment->patient_id,
                    'status' => 'booked',
                    'start_at' => $assessment->return_date->copy()->startOfDay()->setTime(9, 0),
                    'end_at' => $assessment->return_date->copy()->startOfDay()->setTime(9, 30),
                    'external_reference' => 'anc-return:'.$assessment->id,
                    'created_by' => $assessment->recorded_by,
                ]);
            });

        $rule = $ruleClass::query()
            ->where('appointment_id', $parent->id)
            ->firstOr(function () use ($ruleClass, $parent, $assessment) {
                return $ruleClass::create([
                    'appointment_id' => $parent->id,
                    'branch_id' => $assessment->branch_id,
                    'frequency' => 'weekly',
                    'interval' => 4,
                    'occurrence_count' => 8,
                    'timezone' => 'Africa/Accra',
                ]);
            });

        return app($expander)->expand($rule);
    }
}
