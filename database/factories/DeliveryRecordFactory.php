<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\MCH\Models\DeliveryRecord;
use Modules\MCH\Models\PregnancyEpisode;

/**
 * @extends Factory<DeliveryRecord>
 */
class DeliveryRecordFactory extends Factory
{
    protected $model = DeliveryRecord::class;

    public function definition(): array
    {
        return [
            'pregnancy_episode_id' => PregnancyEpisode::factory(),
            'patient_id' => fn (array $attributes) => PregnancyEpisode::query()->withoutGlobalScope('branch')->find($attributes['pregnancy_episode_id'])?->patient_id,
            'branch_id' => fn (array $attributes) => PregnancyEpisode::query()->withoutGlobalScope('branch')->find($attributes['pregnancy_episode_id'])?->branch_id,
            'delivered_at' => now()->subDays(2),
            'place' => DeliveryPlace::FACILITY,
            'mode' => DeliveryMode::SPONTANEOUS_VAGINAL,
            'attendant_type' => 'midwife',
            'gestation_weeks' => 39,
            'number_of_babies' => 1,
            'maternal_outcome' => 'alive',
        ];
    }
}
