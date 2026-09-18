<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Clinical\Models\VitalSign;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\PregnancyEpisode;
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

    public function test_rejects_mismatched_patient_id(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $other = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('patient_id must match the encounter patient.');

        app(MaternalVisitAssessmentService::class)->record($encounter, [
            'patient_id' => $other->id,
        ]);
    }

    public function test_rejects_non_antenatal_encounter(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::CHILD_WELFARE,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maternal visit assessment requires an ANTENATAL encounter.');

        app(MaternalVisitAssessmentService::class)->record($encounter, []);
    }

    public function test_persists_drugs_given(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        $assessment = app(MaternalVisitAssessmentService::class)->record($encounter, [
            'drugs_given' => ['Iron', 'Folic acid'],
        ]);

        $this->assertSame(['Iron', 'Folic acid'], $assessment->drugs_given);
    }

    public function test_derives_gestational_age_and_visit_number_from_episode(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'lmp' => now()->subWeeks(20)->subDays(3)->toDateString(),
        ]);

        $first = app(MaternalVisitAssessmentService::class)->record($this->antenatalEncounter($mother), [
            'pregnancy_episode_id' => $episode->id,
        ]);

        $this->assertSame(20, $first->ga_weeks);
        $this->assertSame(3, $first->ga_days);
        $this->assertSame(1, $first->visit_number);

        $second = app(MaternalVisitAssessmentService::class)->record($this->antenatalEncounter($mother), [
            'pregnancy_episode_id' => $episode->id,
            'ga_weeks' => 21,
        ]);

        $this->assertSame(21, $second->ga_weeks);
        $this->assertSame(2, $second->visit_number);
    }

    public function test_records_bp_and_weight_through_clinical_vital_signs(): void
    {
        $this->actingAs(User::factory()->create());
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = $this->antenatalEncounter($mother);

        app(MaternalVisitAssessmentService::class)->record($encounter, [
            'systolic_bp' => 128,
            'diastolic_bp' => 82,
            'weight' => 64.5,
        ]);

        $vitals = VitalSign::query()->where('encounter_id', $encounter->id)->first();

        $this->assertNotNull($vitals);
        $this->assertSame($mother->id, $vitals->patient_id);
        $this->assertSame(128, (int) $vitals->systolic_bp);
        $this->assertSame(82, (int) $vitals->diastolic_bp);
        $this->assertEqualsWithDelta(64.5, (float) $vitals->weight, 0.01);

        $this->assertSame(1, VitalSign::query()->where('patient_id', $mother->id)->count());

        app(MaternalVisitAssessmentService::class)->record($this->antenatalEncounter($mother), []);

        $this->assertSame(1, VitalSign::query()->where('patient_id', $mother->id)->count());
    }

    private function antenatalEncounter(Patient $mother): Encounter
    {
        return Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);
    }
}
