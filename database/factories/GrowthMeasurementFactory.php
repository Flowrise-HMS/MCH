<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Models\Patient;

class GrowthMeasurementFactory extends Factory
{
    protected $model = GrowthMeasurement::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory()->child(),
            'branch_id' => Branch::factory(),
            'type' => GrowthMeasurementType::WEIGHT,
            'value' => fake()->randomFloat(3, 2, 40),
            'unit' => 'kg',
            'date' => fake()->date(),
        ];
    }
}
