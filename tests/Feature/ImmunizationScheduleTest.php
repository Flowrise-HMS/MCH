<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;
use Tests\TestCase;

class ImmunizationScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'MCH']);
    }

    public function test_creates_ghana_epi_schedule(): void
    {
        $schedule = ImmunizationSchedule::create([
            'name' => 'Ghana EPI',
            'description' => 'Ghana Expanded Programme on Immunization schedule.',
            'target_population' => 'child',
        ]);

        $this->assertSame('Ghana EPI', $schedule->name);
        $this->assertSame('child', $schedule->target_population);
        $this->assertTrue($schedule->is_active);
    }

    public function test_schedule_has_items_linked_to_vaccines(): void
    {
        $schedule = ImmunizationSchedule::create([
            'name' => 'Ghana EPI',
            'target_population' => 'child',
        ]);

        $bcg = Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'BCG',
        ]);

        $item = ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $bcg->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
            'label' => 'Birth dose',
        ]);

        $this->assertSame($schedule->id, $item->immunization_schedule_id);
        $this->assertSame($bcg->id, $item->vaccine_id);
        $this->assertSame(1, $item->dose_sequence);
    }

    public function test_schedule_is_not_branch_scoped(): void
    {
        $schedule = ImmunizationSchedule::create([
            'name' => 'Test Schedule',
            'target_population' => 'child',
        ]);

        $found = ImmunizationSchedule::withoutGlobalScopes()->where('name', 'Test Schedule')->first();
        $this->assertNotNull($found);
        $this->assertSame($schedule->id, $found->id);
    }

    public function test_schedule_item_belongs_to_vaccine(): void
    {
        $schedule = ImmunizationSchedule::factory()->create();
        $vaccine = Vaccine::factory()->create();
        $item = ImmunizationScheduleItem::factory()->create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $vaccine->id,
        ]);

        $this->assertTrue($item->vaccine->is($vaccine));
        $this->assertTrue($item->schedule->is($schedule));
    }
}
