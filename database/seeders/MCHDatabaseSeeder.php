<?php

namespace Modules\MCH\Database\Seeders;

use Illuminate\Database\Seeder;

class MCHDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            MchImmunizationSeeder::class,
        ]);
    }
}
