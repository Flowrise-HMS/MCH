<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\EddSource;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;

class PregnancyEpisodeFactory extends Factory
{
    protected $model = PregnancyEpisode::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory()->female(),
            'branch_id' => Branch::factory(),
            'gravida' => fake()->numberBetween(1, 5),
            'parity' => fake()->numberBetween(0, 4),
            'lmp' => fake()->dateTimeBetween('-24 weeks', '-2 weeks')->format('Y-m-d'),
            'edd_source' => EddSource::LMP,
            'multiple_gestation' => false,
            'risk_level' => RiskLevel::LOW,
            'risk_override' => false,
            'risk_factors' => [],
            'booking_date' => fn (array $attributes): string => Carbon::parse($attributes['lmp'])
                ->addWeek()
                ->addDays(fake()->numberBetween(0, 7))
                ->format('Y-m-d'),
            'outcome' => PregnancyOutcome::ACTIVE,
        ];
    }
}
