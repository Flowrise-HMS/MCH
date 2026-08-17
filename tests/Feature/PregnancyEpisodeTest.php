<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class PregnancyEpisodeTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
    }

    public function test_creates_episode_with_booking_ga(): void
    {
        $patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'gravida' => 2,
            'parity' => 1,
            'lmp' => now()->subWeeks(10)->toDateString(),
            'edd_source' => 'lmp',
            'booking_date' => now()->toDateString(),
        ]);

        $this->assertSame(10, $episode->booking_ga_weeks);
        $this->assertSame(RiskLevel::LOW, $episode->risk_level);
        $this->assertSame(PregnancyOutcome::ACTIVE, $episode->outcome);
    }

    public function test_risk_level_is_derived_from_factors(): void
    {
        $patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $high = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'risk_factors' => [PregnancyRiskFactor::HYPERTENSION->value, PregnancyRiskFactor::SICKLE_CELL->value],
        ]);

        $this->assertSame(RiskLevel::HIGH, $high->risk_level);
    }

    public function test_risk_override_keeps_manual_value(): void
    {
        $patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'risk_factors' => [PregnancyRiskFactor::OTHER->value],
            'risk_level' => RiskLevel::HIGH,
            'risk_override' => true,
        ]);

        $this->assertSame(RiskLevel::HIGH, $episode->risk_level);
    }

    public function test_gestational_age_tracking(): void
    {
        $patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'lmp' => now()->subWeeks(8)->subDays(3)->toDateString(),
        ]);

        $age = $episode->gestationalAgeAt(now());

        $this->assertSame(8, $age['weeks']);
        $this->assertSame(3, $age['days']);
    }

    public function test_marks_outcome(): void
    {
        $patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
        ]);

        $episode->update(['outcome' => PregnancyOutcome::REFERRED_OUT]);

        $this->assertSame(PregnancyOutcome::REFERRED_OUT, $episode->fresh()->outcome);
    }
}
