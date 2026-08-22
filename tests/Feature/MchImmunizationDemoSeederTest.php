<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\MCH\Database\Seeders\MchImmunizationDemoSeeder;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchImmunizationDemoSeederTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    public function test_demo_seeder_creates_catalogue_children_and_mixed_status_records(): void
    {
        $this->seed(MchImmunizationDemoSeeder::class);

        $this->assertSame(9, Vaccine::count());
        $this->assertSame(2, ImmunizationSchedule::count());
        $this->assertSame(23, ImmunizationScheduleItem::count());
        $this->assertSame(4, Patient::query()->where('mrn', 'like', 'MCH-EPI-%')->count());
        $this->assertSame(4, ChildHealthRecord::count());
        $this->assertGreaterThan(0, ImmunizationRecord::where('status', ImmunizationStatus::ADMINISTERED)->count());
        $this->assertGreaterThan(0, ImmunizationRecord::where('status', ImmunizationStatus::SCHEDULED)->count());
        $this->assertGreaterThan(0, ImmunizationRecord::where('status', ImmunizationStatus::DECLINED)->count());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed(MchImmunizationDemoSeeder::class);
        $this->seed(MchImmunizationDemoSeeder::class);

        $this->assertSame(9, Vaccine::count());
        $this->assertSame(2, ImmunizationSchedule::count());
        $this->assertSame(23, ImmunizationScheduleItem::count());
        $this->assertSame(4, Patient::query()->where('mrn', 'like', 'MCH-EPI-%')->count());
        $this->assertSame(4, ChildHealthRecord::count());
    }
}
