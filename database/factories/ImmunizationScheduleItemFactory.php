<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;

class ImmunizationScheduleItemFactory extends Factory
{
    protected $model = ImmunizationScheduleItem::class;

    public function definition(): array
    {
        return [
            'immunization_schedule_id' => ImmunizationSchedule::factory(),
            'vaccine_id' => Vaccine::factory(),
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
            'maximum_age_days' => null,
            'label' => fake()->optional()->word(),
        ];
    }
}
