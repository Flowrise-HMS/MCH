<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Enums\PostnatalContact;
use Modules\MCH\Models\PostnatalVisitAssessment;

/**
 * @extends Factory<PostnatalVisitAssessment>
 */
class PostnatalVisitAssessmentFactory extends Factory
{
    protected $model = PostnatalVisitAssessment::class;

    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory()->state(['type' => EncounterType::POSTNATAL]),
            'patient_id' => fn (array $attributes) => Encounter::query()->withoutGlobalScope('branch')->find($attributes['encounter_id'])?->patient_id,
            'branch_id' => fn (array $attributes) => Encounter::query()->withoutGlobalScope('branch')->find($attributes['encounter_id'])?->branch_id,
            'subject' => 'mother',
            'contact' => PostnatalContact::DAY_3,
            'days_postpartum' => 3,
            'breastfeeding' => 'exclusive',
            'lochia' => 'normal',
        ];
    }
}
