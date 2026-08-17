<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;

class MchRecordFactory extends Factory
{
    protected $model = MchRecord::class;

    public function definition(): array
    {
        return [
            'owner_type' => PregnancyEpisode::class,
            'owner_id' => PregnancyEpisode::factory(),
            'branch_id' => Branch::factory(),
            'serial_number' => fake()->unique()->numerify('ANC-####'),
            'unit' => 'ANC',
            'issue_date' => fake()->date(),
            'status' => MchRecordStatus::ACTIVE,
            'data_consented' => false,
        ];
    }
}
