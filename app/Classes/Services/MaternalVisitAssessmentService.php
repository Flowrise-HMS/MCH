<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Facades\DB;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\MaternalVisitAssessment;

class MaternalVisitAssessmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Encounter $encounter, array $data): MaternalVisitAssessment
    {
        if ($encounter->type !== EncounterType::ANTENATAL) {
            throw new \InvalidArgumentException('Maternal visit assessment requires an ANTENATAL encounter.');
        }

        $dangerSigns = array_map(
            fn ($sign) => $sign instanceof DangerSign ? $sign->value : $sign,
            $data['danger_signs'] ?? [],
        );

        if (isset($data['patient_id']) && $data['patient_id'] !== $encounter->patient_id) {
            throw new \InvalidArgumentException('patient_id must match the encounter patient.');
        }

        return DB::transaction(function () use ($encounter, $data, $dangerSigns): MaternalVisitAssessment {
            $assessment = MaternalVisitAssessment::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'branch_id' => $encounter->branch_id,
                'pregnancy_episode_id' => $data['pregnancy_episode_id'] ?? null,
                'visit_number' => $data['visit_number'] ?? null,
                'ga_weeks' => $data['ga_weeks'] ?? null,
                'ga_days' => $data['ga_days'] ?? null,
                'fetal_heart_rate' => $data['fetal_heart_rate'] ?? null,
                'presentation' => $data['presentation'] ?? null,
                'edema' => $data['edema'] ?? null,
                'urine_protein' => $data['urine_protein'] ?? null,
                'urine_glucose' => $data['urine_glucose'] ?? null,
                'danger_signs' => $dangerSigns,
                'drugs_given' => $data['drugs_given'] ?? [],
                'referral_required' => $data['referral_required'] ?? count($dangerSigns) > 0,
                'referral_destination' => $data['referral_destination'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null,
            ]);

            if (isset($data['fundal_height'])) {
                GrowthMeasurement::create([
                    'patient_id' => $assessment->patient_id,
                    'encounter_id' => $encounter->id,
                    'branch_id' => $encounter->branch_id,
                    'type' => GrowthMeasurementType::FUNDAL_HEIGHT,
                    'value' => $data['fundal_height'],
                    'unit' => $data['fundal_height_unit'] ?? 'cm',
                    'date' => $data['measurement_date'] ?? now()->toDateString(),
                    'recorded_by' => $data['recorded_by'] ?? null,
                ]);
            }

            return $assessment;
        });
    }
}
