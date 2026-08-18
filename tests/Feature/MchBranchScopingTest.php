<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Context;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchBranchScopingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'MCH']);
    }

    protected function tearDown(): void
    {
        Context::forget('current_branch_id');

        parent::tearDown();
    }

    /**
     * @param  class-string  $modelClass
     */
    private function assertBranchScoped(string $modelClass, array $attributesForBranchA, array $attributesForBranchB): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $recordA = $modelClass::query()->create(array_merge($attributesForBranchA, ['branch_id' => $branchA->id]));
        $recordB = $modelClass::query()->create(array_merge($attributesForBranchB, ['branch_id' => $branchB->id]));

        Context::add('current_branch_id', $branchA->id);

        $visibleIds = $modelClass::query()->pluck('id');

        $this->assertTrue($visibleIds->contains($recordA->id));
        $this->assertFalse($visibleIds->contains($recordB->id));
    }

    public function test_pregnancy_episodes_are_scoped_to_current_branch(): void
    {
        $patientA = Patient::factory()->female()->create();
        $patientB = Patient::factory()->female()->create();

        $this->assertBranchScoped(
            PregnancyEpisode::class,
            ['patient_id' => $patientA->id],
            ['patient_id' => $patientB->id],
        );
    }

    public function test_child_health_records_are_scoped_to_current_branch(): void
    {
        $childA = Patient::factory()->child()->create();
        $childB = Patient::factory()->child()->create();

        $this->assertBranchScoped(
            ChildHealthRecord::class,
            ['patient_id' => $childA->id],
            ['patient_id' => $childB->id],
        );
    }

    public function test_growth_measurements_are_scoped_to_current_branch(): void
    {
        $patientA = Patient::factory()->create();
        $patientB = Patient::factory()->create();

        $this->assertBranchScoped(
            GrowthMeasurement::class,
            ['patient_id' => $patientA->id, 'type' => GrowthMeasurementType::WEIGHT, 'value' => 10, 'unit' => 'kg', 'date' => now()->toDateString()],
            ['patient_id' => $patientB->id, 'type' => GrowthMeasurementType::WEIGHT, 'value' => 11, 'unit' => 'kg', 'date' => now()->toDateString()],
        );
    }

    public function test_mch_records_are_scoped_to_current_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $episodeA = PregnancyEpisode::factory()->create(['branch_id' => $branchA->id]);
        $episodeB = PregnancyEpisode::factory()->create(['branch_id' => $branchB->id]);

        $recordA = MchRecord::query()->create([
            'branch_id' => $branchA->id,
            'owner_type' => PregnancyEpisode::class,
            'owner_id' => $episodeA->id,
            'serial_number' => 'ANC-0001',
            'unit' => 'ANC',
            'issue_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $recordB = MchRecord::query()->create([
            'branch_id' => $branchB->id,
            'owner_type' => PregnancyEpisode::class,
            'owner_id' => $episodeB->id,
            'serial_number' => 'ANC-0001',
            'unit' => 'ANC',
            'issue_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        Context::add('current_branch_id', $branchA->id);

        $visibleIds = MchRecord::query()->pluck('id');

        $this->assertTrue($visibleIds->contains($recordA->id));
        $this->assertFalse($visibleIds->contains($recordB->id));
    }
}
