<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\Patient\Models\Patient;

class ChildVisitAssessmentFactory extends Factory
{
    protected $model = ChildVisitAssessment::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => Patient::factory()->child(),
            'branch_id' => Branch::factory(),
            'feeding' => 'exclusive_breastfeeding',
            'vitamin_a_given' => fake()->boolean(),
            'dewormed' => fake()->boolean(),
            'referral_required' => false,
        ];
    }
}
