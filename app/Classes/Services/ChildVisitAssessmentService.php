<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Facades\DB;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\MCH\Models\GrowthMeasurement;

class ChildVisitAssessmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Encounter $encounter, array $data): ChildVisitAssessment
    {
        if ($encounter->type !== EncounterType::CHILD_WELFARE) {
            throw new \InvalidArgumentException('Child visit assessment requires a CHILD_WELFARE encounter.');
        }

        if (isset($data['patient_id']) && $data['patient_id'] !== $encounter->patient_id) {
            throw new \InvalidArgumentException('patient_id must match the encounter patient.');
        }

        return DB::transaction(function () use ($encounter, $data): ChildVisitAssessment {
            $assessment = ChildVisitAssessment::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'branch_id' => $encounter->branch_id,
                'child_health_record_id' => $data['child_health_record_id'] ?? null,
                'feeding' => $data['feeding'] ?? null,
                'vitamin_a_given' => $data['vitamin_a_given'] ?? false,
                'dewormed' => $data['dewormed'] ?? false,
                'developmental_screen' => $data['developmental_screen'] ?? null,
                'referral_required' => $data['referral_required'] ?? false,
                'referral_destination' => $data['referral_destination'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null,
            ]);

            foreach ($data['measurements'] ?? [] as $measurement) {
                $type = $measurement['type'] ?? '';
                $type = $type instanceof GrowthMeasurementType
                    ? $type
                    : (GrowthMeasurementType::tryFrom($type) ?? $type);

                GrowthMeasurement::create([
                    'patient_id' => $assessment->patient_id,
                    'encounter_id' => $encounter->id,
                    'branch_id' => $encounter->branch_id,
                    'type' => $type,
                    'value' => $measurement['value'],
                    'unit' => $measurement['unit'] ?? 'kg',
                    'date' => $measurement['date'] ?? now()->toDateString(),
                    'recorded_by' => $data['recorded_by'] ?? null,
                ]);
            }

            return $assessment;
        });
    }
}
