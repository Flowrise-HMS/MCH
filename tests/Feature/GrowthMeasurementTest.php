<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class GrowthMeasurementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    public function test_stores_child_weight_from_cwc_visit(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'type' => EncounterType::CHILD_WELFARE,
        ]);

        $measurement = GrowthMeasurement::create([
            'patient_id' => $child->id,
            'encounter_id' => $encounter->id,
            'branch_id' => $branch->id,
            'type' => GrowthMeasurementType::WEIGHT,
            'value' => 8.5,
            'unit' => 'kg',
            'date' => now()->toDateString(),
        ]);

        $this->assertSame($encounter->id, $measurement->encounter_id);
        $this->assertSame('8.500', $measurement->value);
        $this->assertSame(GrowthMeasurementType::WEIGHT, $measurement->type);
    }

    public function test_stores_obstetric_fundal_height_from_anc_visit(): void
    {
        $branch = Branch::factory()->create();
        $mother = Patient::factory()->female()->create(['branch_id' => $branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        $measurement = GrowthMeasurement::create([
            'patient_id' => $mother->id,
            'encounter_id' => $encounter->id,
            'branch_id' => $branch->id,
            'type' => GrowthMeasurementType::FUNDAL_HEIGHT,
            'value' => 28,
            'unit' => 'cm',
            'date' => now()->toDateString(),
        ]);

        $this->assertSame(GrowthMeasurementType::FUNDAL_HEIGHT, $measurement->type);
        $this->assertSame('28.000', $measurement->value);
    }
}
