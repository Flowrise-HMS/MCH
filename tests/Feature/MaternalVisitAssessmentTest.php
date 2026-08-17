<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MaternalVisitAssessmentTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
    }

    public function test_records_assessment_and_referral_flag(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        $assessment = app(MaternalVisitAssessmentService::class)->record($encounter, [
            'visit_number' => 3,
            'fetal_heart_rate' => 140,
            'danger_signs' => [DangerSign::BLEEDING->value],
            'referral_destination' => 'District Hospital',
            'return_date' => now()->addWeeks(4)->toDateString(),
        ]);

        $this->assertSame($encounter->id, $assessment->encounter_id);
        $this->assertSame(3, $assessment->visit_number);
        $this->assertTrue($assessment->referral_required);
        $this->assertSame('District Hospital', $assessment->referral_destination);
        $this->assertNotNull($assessment->return_date);
    }

    public function test_fundal_height_writes_to_growth_measurements(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        app(MaternalVisitAssessmentService::class)->record($encounter, [
            'fundal_height' => 26,
            'fundal_height_unit' => 'cm',
        ]);

        $measurement = GrowthMeasurement::where('encounter_id', $encounter->id)->first();

        $this->assertNotNull($measurement);
        $this->assertSame(GrowthMeasurementType::FUNDAL_HEIGHT, $measurement->type);
        $this->assertSame('26.000', $measurement->value);
    }
}
