<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Clinical\Enums\EncounterStatus;
use Modules\Clinical\Enums\EncounterType;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MchWorkspaceService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchWorkspaceServiceTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private MchWorkspaceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->service = app(MchWorkspaceService::class);
    }

    public function test_resolve_context_prefers_active_pregnancy(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        $context = $this->service->resolveContext($mother);

        $this->assertSame('mother', $context['kind']);
        $this->assertNotNull($context['pregnancy']);
    }

    public function test_resolve_context_uses_child_health_record(): void
    {
        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        $context = $this->service->resolveContext($child);

        $this->assertSame('child', $context['kind']);
        $this->assertNotNull($context['childHealthRecord']);
    }

    public function test_resolve_context_unknown_without_registry(): void
    {
        $patient = Patient::factory()->create(['branch_id' => $this->branch->id]);

        $this->assertSame('unknown', $this->service->resolveContext($patient)['kind']);
    }

    public function test_anc_today_includes_return_date_today(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $encounter = $this->service->ensureEncounter($mother, EncounterType::ANTENATAL);

        MaternalVisitAssessment::create([
            'encounter_id' => $encounter->id,
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->toDateString(),
        ]);

        $patients = $this->service->ancTodayPatients($this->branch->id);

        $this->assertTrue($patients->contains(fn (Patient $patient): bool => $patient->id === $mother->id));
    }

    public function test_ensure_encounter_is_idempotent_for_today(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $first = $this->service->ensureEncounter($mother, EncounterType::ANTENATAL);
        $second = $this->service->ensureEncounter($mother, EncounterType::ANTENATAL);

        $this->assertSame($first->id, $second->id);
    }

    public function test_epi_due_patients_include_scheduled_due_doses(): void
    {
        $child = Patient::factory()->child()->create([
            'branch_id' => $this->branch->id,
            'date_of_birth' => now()->subDays(3)->toDateString(),
        ]);
        $vaccine = Vaccine::create(['antigen' => VaccineAntigen::BCG, 'name' => 'BCG']);
        $schedule = ImmunizationSchedule::create([
            'name' => 'Workspace EPI',
            'target_population' => 'child',
        ]);
        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
        ]);
        ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::SCHEDULED,
        ]);

        $rows = $this->service->epiDuePatients($this->branch->id);

        $this->assertTrue($rows->contains(fn (array $row): bool => $row['patient']->id === $child->id));
    }

    public function test_high_risk_pregnancies_lists_high_risk_only(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'risk_override' => true,
            'risk_level' => RiskLevel::HIGH,
            'risk_factors' => [PregnancyRiskFactor::cases()[0]->value],
        ]);

        $rows = $this->service->highRiskPregnancies($this->branch->id);

        $this->assertTrue($rows->contains(fn ($episode): bool => $episode->patient_id === $mother->id));
    }

    public function test_anc_today_patients_carry_their_active_pregnancy(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->service->ensureEncounter($mother, EncounterType::ANTENATAL);

        $patient = $this->service->ancTodayPatients($this->branch->id)
            ->first(fn (Patient $patient): bool => $patient->id === $mother->id);

        $this->assertNotNull($patient);
        $this->assertTrue($patient->relationLoaded('activePregnancyEpisode'));
        $this->assertSame($episode->id, $patient->activePregnancyEpisode?->id);
    }

    public function test_edd_due_soon_lists_active_episodes_inside_the_window(): void
    {
        $soon = PregnancyEpisode::factory()->create([
            'patient_id' => Patient::factory()->female()->create(['branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
            'edd' => now()->addDays(5)->toDateString(),
        ]);
        PregnancyEpisode::factory()->create([
            'patient_id' => Patient::factory()->female()->create(['branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
            'edd' => now()->addDays(30)->toDateString(),
        ]);
        PregnancyEpisode::factory()->create([
            'patient_id' => Patient::factory()->female()->create(['branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
            'edd' => now()->addDays(5)->toDateString(),
            'outcome' => PregnancyOutcome::DELIVERED,
        ]);

        $rows = $this->service->eddDueSoon($this->branch->id);

        $this->assertCount(1, $rows);
        $this->assertSame($soon->id, $rows->first()->id);
        $this->assertTrue($rows->first()->relationLoaded('patient'));
    }

    public function test_find_open_encounter_returns_only_todays_open_encounter_of_type(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);

        $this->assertNull($this->service->findOpenEncounter($mother, EncounterType::ANTENATAL));

        $encounter = $this->service->ensureEncounter($mother, EncounterType::ANTENATAL);

        $this->assertSame($encounter->id, $this->service->findOpenEncounter($mother, EncounterType::ANTENATAL)?->id);
        $this->assertNull($this->service->findOpenEncounter($mother, EncounterType::CHILD_WELFARE));

        $encounter->forceFill(['status' => EncounterStatus::CANCELLED])->save();

        $this->assertNull($this->service->findOpenEncounter($mother, EncounterType::ANTENATAL));
    }

    public function test_epi_due_patients_does_not_query_per_record(): void
    {
        $vaccine = Vaccine::create(['antigen' => VaccineAntigen::OPV, 'name' => 'OPV']);
        $schedule = ImmunizationSchedule::create(['name' => 'Bounded EPI', 'target_population' => 'child']);

        foreach ([1, 2, 3] as $dose) {
            ImmunizationScheduleItem::create([
                'immunization_schedule_id' => $schedule->id,
                'vaccine_id' => $vaccine->id,
                'dose_sequence' => $dose,
                'minimum_age_days' => 0,
            ]);
        }

        foreach (range(1, 4) as $i) {
            $child = Patient::factory()->child()->create([
                'branch_id' => $this->branch->id,
                'date_of_birth' => now()->subDays(60),
            ]);

            foreach ([1, 2, 3] as $dose) {
                ImmunizationRecord::create([
                    'patient_id' => $child->id,
                    'branch_id' => $this->branch->id,
                    'vaccine_id' => $vaccine->id,
                    'dose_sequence' => $dose,
                    'status' => ImmunizationStatus::SCHEDULED,
                ]);
            }
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $rows = $this->service->epiDuePatients($this->branch->id);

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(4, $rows);
        $this->assertLessThanOrEqual(5, $queries, "epiDuePatients ran {$queries} queries for 12 scheduled records");
    }
}
