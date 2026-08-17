<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class ChildVisitAssessmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    public function test_records_cwc_assessment_with_anthropometry(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'type' => EncounterType::CHILD_WELFARE,
        ]);

        $assessment = app(ChildVisitAssessmentService::class)->record($encounter, [
            'feeding' => 'exclusive_breastfeeding',
            'vitamin_a_given' => true,
            'dewormed' => false,
            'measurements' => [
                ['type' => GrowthMeasurementType::WEIGHT, 'value' => 8.2, 'unit' => 'kg'],
                ['type' => GrowthMeasurementType::MUAC, 'value' => 14.5, 'unit' => 'cm'],
            ],
        ]);

        $this->assertSame($encounter->id, $assessment->encounter_id);
        $this->assertSame('exclusive_breastfeeding', $assessment->feeding->value);
        $this->assertTrue($assessment->vitamin_a_given);

        $weights = GrowthMeasurement::where('encounter_id', $encounter->id)
            ->where('type', GrowthMeasurementType::WEIGHT)
            ->get();
        $this->assertCount(1, $weights);
        $this->assertSame('8.200', $weights->first()->value);
    }
}
