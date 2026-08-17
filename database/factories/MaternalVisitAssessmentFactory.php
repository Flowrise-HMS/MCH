<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\Patient\Models\Patient;

class MaternalVisitAssessmentFactory extends Factory
{
    protected $model = MaternalVisitAssessment::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory()->female(),
            'branch_id' => Branch::factory(),
            'visit_number' => fake()->numberBetween(1, 8),
            'fetal_heart_rate' => fake()->numberBetween(110, 160),
            'danger_signs' => [],
            'drugs_given' => [],
            'referral_required' => false,
        ];
    }
}
