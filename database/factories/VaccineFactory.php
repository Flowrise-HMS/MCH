<?php

namespace Modules\MCH\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\Vaccine;

class VaccineFactory extends Factory
{
    protected $model = Vaccine::class;

    public function definition(): array
    {
        $antigen = $this->faker->unique()->randomElement(VaccineAntigen::cases());

        return [
            'antigen' => $antigen,
            'name' => $antigen->getLabel(),
            'route' => $this->faker->randomElement(['intramuscular', 'subcutaneous', 'oral', 'intradermal']),
            'site' => $this->faker->randomElement(['left_upper_arm', 'right_upper_arm', 'thigh', 'mouth']),
            'presentation' => $this->faker->randomElement(['powder_solution', 'liquid', 'tablet']),
            'is_active' => true,
        ];
    }
}
