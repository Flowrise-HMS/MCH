<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MCH\Models\ImmunizationSchedule;

class ImmunizationScheduleFactory extends Factory
{
    protected $model = ImmunizationSchedule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' EPI',
            'description' => fake()->sentence(),
            'target_population' => 'child',
            'is_active' => true,
        ];
    }
}
