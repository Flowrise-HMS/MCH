<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class EpiDueServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    private function setupSchedule(): ImmunizationSchedule
    {
        $schedule = ImmunizationSchedule::create([
            'name' => 'Ghana EPI',
            'target_population' => 'child',
        ]);

        $bcg = Vaccine::create(['antigen' => VaccineAntigen::BCG, 'name' => 'BCG']);
        $opv = Vaccine::create(['antigen' => VaccineAntigen::OPV, 'name' => 'OPV']);

        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $bcg->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
            'label' => 'Birth dose',
        ]);

        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $opv->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 42, // 6 weeks
            'label' => 'OPV-1 at 6 weeks',
        ]);

        return $schedule;
    }

    public function test_generates_due_records_for_newborn(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(3),
        ]);

        $service = app(EpiDueService::class);
        $records = $service->generateDueRecords($child, $schedule, $branch->id);

        // BCG (minimum_age_days=0) should be due; OPV-1 (minimum_age_days=42) should NOT
        $this->assertCount(1, $records);
        $this->assertSame(VaccineAntigen::BCG, $records->first()->vaccine->antigen);
        $this->assertSame(ImmunizationStatus::SCHEDULED, $records->first()->status);
    }

    public function test_does_not_duplicate_existing_scheduled_records(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(3),
        ]);

        $service = app(EpiDueService::class);
        $service->generateDueRecords($child, $schedule, $branch->id);
        $service->generateDueRecords($child, $schedule, $branch->id);

        $bcgRecords = ImmunizationRecord::where('patient_id', $child->id)
            ->where('vaccine_id', Vaccine::where('antigen', VaccineAntigen::BCG)->first()->id)
            ->count();

        $this->assertSame(1, $bcgRecords);
    }

    public function test_skips_already_administered_doses(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(3),
        ]);

        $bcg = Vaccine::where('antigen', VaccineAntigen::BCG)->first();
        ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $bcg->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->subDay()->toDateString(),
        ]);

        $service = app(EpiDueService::class);
        $records = $service->generateDueRecords($child, $schedule, $branch->id);

        $this->assertCount(0, $records);
    }

    public function test_returns_empty_when_schedule_has_no_active_items(): void
    {
        $schedule = ImmunizationSchedule::create([
            'name' => 'Empty',
            'target_population' => 'child',
        ]);
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);

        $service = app(EpiDueService::class);
        $records = $service->generateDueRecords($child, $schedule, $branch->id);

        $this->assertCount(0, $records);
    }

    public function test_classifies_due_overdue_and_not_yet_due_doses(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(50),
        ]);

        $bcgItem = $schedule->items()->whereHas('vaccine', fn ($query) => $query->where('antigen', VaccineAntigen::BCG))->firstOrFail();
        $opvItem = $schedule->items()->whereHas('vaccine', fn ($query) => $query->where('antigen', VaccineAntigen::OPV))->firstOrFail();
        $opvItem->forceFill(['maximum_age_days' => 45])->save();

        $service = app(EpiDueService::class);

        $this->assertSame('due', $service->classifyDose($child, $bcgItem));
        $this->assertSame('overdue', $service->classifyDose($child, $opvItem));

        $newborn = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(3),
        ]);

        $this->assertSame('due', $service->classifyDose($newborn, $bcgItem));
        $this->assertSame('not_yet_due', $service->classifyDose($newborn, $opvItem));
    }

    public function test_classify_scheduled_dose_matches_classify_dose_without_querying(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(50),
        ]);

        $bcgItem = $schedule->items()->whereHas('vaccine', fn ($query) => $query->where('antigen', VaccineAntigen::BCG))->firstOrFail();
        $opvItem = $schedule->items()->whereHas('vaccine', fn ($query) => $query->where('antigen', VaccineAntigen::OPV))->firstOrFail();
        $opvItem->forceFill(['maximum_age_days' => 45])->save();

        $service = app(EpiDueService::class);
        $dob = $service->getDateOfBirth($child);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->assertSame('due', $service->classifyScheduledDose($dob, $bcgItem));
        $this->assertSame('overdue', $service->classifyScheduledDose($dob, $opvItem));

        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }
}
