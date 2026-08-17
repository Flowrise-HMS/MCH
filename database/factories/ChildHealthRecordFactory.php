<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\Patient\Models\Patient;

class ChildHealthRecordFactory extends Factory
{
    protected $model = ChildHealthRecord::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory()->child(),
            'branch_id' => Branch::factory(),
            'date_of_birth' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'status' => ChildHealthRecordStatus::ACTIVE,
        ];
    }
}
