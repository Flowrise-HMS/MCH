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

        // BCG has no maximum age: 50 days past its birth due date exceeds the
        // configured 28-day grace period, so it is overdue too.
        $this->assertSame('overdue', $service->classifyDose($child, $bcgItem));
        $this->assertSame('overdue', $service->classifyDose($child, $opvItem));

        $tenDaysOld = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(10),
        ]);

        $this->assertSame('due', $service->classifyDose($tenDaysOld, $bcgItem));

        $newborn = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(3),
        ]);

        $this->assertSame('due', $service->classifyDose($newborn, $bcgItem));
        $this->assertSame('not_yet_due', $service->classifyDose($newborn, $opvItem));
    }

    public function test_maternal_schedule_generates_next_tt_dose_from_previous_dose(): void
    {
        $branch = Branch::factory()->create();
        $tt = Vaccine::create(['antigen' => VaccineAntigen::TETANUS_TOXOID, 'name' => 'TT']);
        $schedule = ImmunizationSchedule::create([
            'name' => 'Maternal TT',
            'target_population' => ImmunizationSchedule::TARGET_MATERNAL,
        ]);

        foreach ([1 => 0, 2 => 28, 3 => 182] as $dose => $minDays) {
            ImmunizationScheduleItem::create([
                'immunization_schedule_id' => $schedule->id,
                'vaccine_id' => $tt->id,
                'dose_sequence' => $dose,
                'minimum_age_days' => $minDays,
            ]);
        }

        $mother = Patient::factory()->female()->create(['branch_id' => $branch->id]);
        $booking = now()->subDays(40)->startOfDay();
        $service = app(EpiDueService::class);

        $first = $service->generateDueRecords($mother, $schedule, $branch->id, $booking);

        $this->assertCount(1, $first);
        $this->assertSame(1, $first->first()->dose_sequence);

        $service->generateDueRecords($mother, $schedule, $branch->id, $booking);
        $this->assertSame(1, ImmunizationRecord::query()->where('patient_id', $mother->id)->count());

        $first->first()->forceFill([
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->subDays(30)->toDateString(),
        ])->save();

        $second = $service->generateDueRecords($mother, $schedule, $branch->id, $booking);

        $this->assertCount(1, $second);
        $this->assertSame(2, $second->first()->dose_sequence);
        $this->assertSame(0, ImmunizationRecord::query()->where('patient_id', $mother->id)->where('dose_sequence', 3)->count());
        // TT2 is due 28 days after TT1 was administered (30 days ago).
        $this->assertSame(now()->subDays(2)->toDateString(), $second->first()->due_date->toDateString());
        $this->assertSame(1, $service->lastGenerationReport()['skipped_dependency']);
    }

    public function test_persists_due_dates_and_reports_skipped_doses(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $newborn = Patient::factory()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(10)->toDateString(),
        ]);

        $created = app(EpiDueService::class)->generateDueRecords($newborn, $schedule, $branch->id);

        $this->assertCount(1, $created);
        $this->assertSame($newborn->date_of_birth->toDateString(), $created->first()->due_date->toDateString());

        $report = app(EpiDueService::class)->lastGenerationReport();
        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['skipped_future']);
    }

    public function test_classify_record_prefers_the_stored_due_date(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->create(['branch_id' => $branch->id, 'date_of_birth' => now()->subDays(100)->toDateString()]);
        $opvItem = $schedule->items()->where('minimum_age_days', 42)->first();
        $opvItem->update(['maximum_age_days' => 56]);

        $record = ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $opvItem->vaccine_id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::SCHEDULED,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $service = app(EpiDueService::class);

        // Stored due date wins over the DOB-based computation (which would be overdue).
        $this->assertSame('not_yet_due', $service->classifyRecord($record, $opvItem->fresh(), $service->getDateOfBirth($child)));

        $record->update(['due_date' => now()->subDays(30)->toDateString()]);
        $this->assertSame('overdue', $service->classifyRecord($record->fresh(), $opvItem->fresh(), $service->getDateOfBirth($child)));

        $record->update(['due_date' => now()->subDays(5)->toDateString()]);
        $this->assertSame('due', $service->classifyRecord($record->fresh(), $opvItem->fresh(), $service->getDateOfBirth($child)));
    }

    public function test_classify_scheduled_dose_matches_classify_dose_without_querying(): void
    {
        $schedule = $this->setupSchedule();
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(50),
        ]);

        // Explicit windows so the grace-period default does not apply:
        // BCG is still open at 50 days, OPV (due at 42 days) closed at 45.
        $bcgItem = $schedule->items()->whereHas('vaccine', fn ($query) => $query->where('antigen', VaccineAntigen::BCG))->firstOrFail();
        $bcgItem->forceFill(['maximum_age_days' => 365])->save();
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
