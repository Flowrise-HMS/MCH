<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;

class ImmunizationRecordFactory extends Factory
{
    protected $model = ImmunizationRecord::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();

        return [
            'patient_id' => Patient::factory()->child()->state([
                'branch_id' => $branch->id,
            ]),
            'branch_id' => $branch->id,
            'vaccine_id' => Vaccine::factory(),
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => fake()->dateTimeThisYear()->format('Y-m-d'),
            'batch_lot' => fake()->bothify('???-####'),
            'site' => fake()->randomElement(['left_upper_arm', 'right_upper_arm', 'thigh']),
            'route' => fake()->randomElement(['intramuscular', 'subcutaneous', 'oral']),
        ];
    }
}
