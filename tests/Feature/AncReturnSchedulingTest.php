<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Services\AncReturnScheduler;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class AncReturnSchedulingTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
    }

    private function assessment(array $data = [])
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = Encounter::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => EncounterType::ANTENATAL,
        ]);

        return \Modules\MCH\Models\MaternalVisitAssessment::create(array_merge([
            'encounter_id' => $encounter->id,
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->addWeeks(4)->toDateString(),
        ], $data));
    }

    public function test_creates_recurring_anc_series_when_appointment_enabled(): void
    {
        $assessment = $this->assessment();

        $instances = app(AncReturnScheduler::class)->schedule($assessment);

        $this->assertCount(8, $instances);
        $this->assertSame(
            $assessment->return_date->copy()->addWeeks(4)->toDateString(),
            $instances->first()->start_at->toDateString(),
        );

        $anchor = \Modules\Appointment\Models\Appointment::where('external_reference', 'anc-return:'.$assessment->id)->first();
        $this->assertNotNull($anchor);
        $this->assertSame($assessment->return_date->toDateString(), $anchor->start_at->toDateString());
    }

    public function test_scheduling_is_idempotent_per_assessment(): void
    {
        $assessment = $this->assessment();
        $scheduler = app(AncReturnScheduler::class);

        $scheduler->schedule($assessment);
        $second = $scheduler->schedule($assessment);

        $this->assertEmpty($second);
        $this->assertSame(
            1,
            \Modules\Appointment\Models\Appointment::where('external_reference', 'anc-return:'.$assessment->id)->count(),
        );
        $this->assertSame(
            8,
            \Modules\Appointment\Models\Appointment::where('external_reference', 'like', 'recur:%')->count(),
        );
    }

    public function test_returns_null_when_no_return_date(): void
    {
        $assessment = $this->assessment(['return_date' => null]);

        $this->assertNull(app(AncReturnScheduler::class)->schedule($assessment));
    }
}
