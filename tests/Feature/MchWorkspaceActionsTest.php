<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Clinical\Models\VitalSign;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\FeedingMethod;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\PatientRelationshipType;
use Modules\Patient\Events\PatientRegistered;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchWorkspaceActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());
    }

    public function test_start_visit_creates_antenatal_encounter_once(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->call('startVisit')
            ->call('startVisit');

        $this->assertSame(
            1,
            Encounter::query()
                ->where('patient_id', $mother->id)
                ->where('type', EncounterType::ANTENATAL)
                ->whereDate('created_at', now()->toDateString())
                ->count(),
        );
    }

    public function test_save_anc_visit_persists_assessment(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->set('ancVisitData.ga_weeks', 24)
            ->set('ancVisitData.return_date', now()->addWeeks(4)->toDateString())
            ->call('saveAncVisit');

        $this->assertDatabaseHas('maternal_visit_assessments', [
            'patient_id' => $mother->id,
            'pregnancy_episode_id' => $episode->id,
            'ga_weeks' => 24,
        ]);
        $this->assertSame(1, MaternalVisitAssessment::where('patient_id', $mother->id)->count());
    }

    public function test_generate_epi_dues_and_administer_from_workspace(): void
    {
        $child = Patient::factory()->child()->create([
            'branch_id' => $this->branch->id,
            'date_of_birth' => now()->subDays(3)->toDateString(),
        ]);
        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);
        $vaccine = Vaccine::create(['antigen' => VaccineAntigen::BCG, 'name' => 'BCG']);
        $schedule = ImmunizationSchedule::create([
            'name' => 'Workspace Action EPI',
            'target_population' => 'child',
        ]);
        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $child->id)
            ->call('generateEpiDues');

        $record = ImmunizationRecord::query()
            ->where('patient_id', $child->id)
            ->where('status', ImmunizationStatus::SCHEDULED)
            ->first();

        $this->assertNotNull($record);

        $component
            ->set('administerBatchLot', 'LOT-WS-1')
            ->call('administerDose', $record->id);

        $this->assertSame(
            ImmunizationStatus::ADMINISTERED,
            $record->fresh()->status,
        );
    }

    public function test_register_mother_creates_real_patient_and_derives_edd(): void
    {
        Event::fake([PatientRegistered::class]);
        $lmp = now()->subWeeks(16)->startOfDay();

        Livewire::test(MchWorkspace::class)
            ->call('startRegistration', 'mother')
            ->fillForm([
                'first_name' => 'Ama',
                'last_name' => 'Mensah',
                'date_of_birth' => '1998-05-04',
                'phone' => '0241234567',
                'lmp' => $lmp->toDateString(),
                'gravida' => 2,
                'parity' => 1,
                'risk_factors' => [PregnancyRiskFactor::PREVIOUS_C_SECTION->value],
            ], 'registerForm')
            ->call('submitRegistration')
            ->assertHasNoFormErrors(form: 'registerForm')
            ->assertSet('mode', 'patient')
            ->assertSet('context.kind', 'mother');

        $patient = Patient::query()->where('first_name', 'Ama')->where('last_name', 'Mensah')->first();

        $this->assertNotNull($patient);
        $this->assertNotEmpty($patient->mrn);
        $this->assertSame(Gender::FEMALE, $patient->gender);
        $this->assertSame($this->branch->id, $patient->branch_id);
        Event::assertDispatched(PatientRegistered::class);

        $episode = PregnancyEpisode::query()->where('patient_id', $patient->id)->first();

        $this->assertNotNull($episode);
        $this->assertSame($lmp->copy()->addDays(280)->toDateString(), $episode->edd->toDateString());
        $this->assertSame(2, $episode->gravida);
        $this->assertSame(RiskLevel::HIGH, $episode->risk_level);
        $this->assertSame(now()->toDateString(), $episode->booking_date->toDateString());
    }

    public function test_register_child_with_mother_links_relationship_and_episode(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'outcome' => PregnancyOutcome::DELIVERED,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('startRegistration', 'child')
            ->fillForm([
                'first_name' => 'Kofi',
                'last_name' => 'Mensah',
                'date_of_birth' => now()->subDays(10)->toDateString(),
                'gender' => Gender::MALE->value,
                'mother_patient_id' => $mother->id,
            ], 'registerForm')
            ->call('submitRegistration')
            ->assertHasNoFormErrors(form: 'registerForm')
            ->assertSet('context.kind', 'child');

        $child = Patient::query()->where('first_name', 'Kofi')->where('last_name', 'Mensah')->first();

        $this->assertNotNull($child);
        $this->assertSame(Gender::MALE, $child->gender);

        $record = ChildHealthRecord::query()->where('patient_id', $child->id)->first();

        $this->assertNotNull($record);
        $this->assertSame($episode->id, $record->pregnancy_episode_id);

        $this->assertDatabaseHas('patient_relationships', [
            'subject_type' => $child->getMorphClass(),
            'subject_id' => $child->id,
            'object_type' => $mother->getMorphClass(),
            'object_id' => $mother->id,
            'type' => PatientRelationshipType::MOTHER->value,
        ]);
    }

    public function test_register_requires_name_and_date_of_birth(): void
    {
        Livewire::test(MchWorkspace::class)
            ->call('startRegistration', 'mother')
            ->fillForm(['first_name' => '', 'last_name' => '', 'date_of_birth' => null], 'registerForm')
            ->call('submitRegistration')
            ->assertHasFormErrors(['first_name', 'last_name', 'date_of_birth'], 'registerForm')
            ->assertSet('mode', 'register');

        $this->assertSame(0, PregnancyEpisode::query()->count());
    }

    public function test_save_anc_visit_with_danger_signs_and_vitals(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'lmp' => now()->subWeeks(30)->toDateString(),
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->fillForm([
                'systolic_bp' => 150,
                'diastolic_bp' => 95,
                'weight' => 70.2,
                'danger_signs' => [DangerSign::BLEEDING->value],
                'referral_destination' => 'District Hospital',
            ], 'ancVisitForm')
            ->call('saveAncVisit')
            ->assertHasNoFormErrors(form: 'ancVisitForm');

        $assessment = MaternalVisitAssessment::query()->where('patient_id', $mother->id)->first();

        $this->assertNotNull($assessment);
        $this->assertSame($episode->id, $assessment->pregnancy_episode_id);
        $this->assertTrue($assessment->referral_required);
        $this->assertSame([DangerSign::BLEEDING->value], $assessment->danger_signs);
        $this->assertSame(30, $assessment->ga_weeks);
        $this->assertSame(1, $assessment->visit_number);

        $vitals = VitalSign::query()->where('encounter_id', $assessment->encounter_id)->first();

        $this->assertNotNull($vitals);
        $this->assertSame(150, (int) $vitals->systolic_bp);
        $this->assertSame(95, (int) $vitals->diastolic_bp);
    }

    public function test_save_cwc_visit_with_head_circumference_and_feeding(): void
    {
        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $child->id)
            ->fillForm([
                'weight' => 6.2,
                'head_circumference' => 41.5,
                'feeding' => FeedingMethod::cases()[0]->value,
                'notes' => 'Thriving',
            ], 'cwcVisitForm')
            ->call('saveCwcVisit')
            ->assertHasNoFormErrors(form: 'cwcVisitForm');

        $assessment = ChildVisitAssessment::query()->where('patient_id', $child->id)->first();

        $this->assertNotNull($assessment);
        $this->assertSame(FeedingMethod::cases()[0], $assessment->feeding);
        $this->assertSame('Thriving', $assessment->notes);

        $types = GrowthMeasurement::query()
            ->where('encounter_id', $assessment->encounter_id)
            ->pluck('type')
            ->map(fn (GrowthMeasurementType $type): string => $type->value)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([GrowthMeasurementType::HEAD_CIRCUMFERENCE->value, GrowthMeasurementType::WEIGHT->value], $types);
    }

    public function test_generate_epi_dues_for_mother_uses_maternal_schedule(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'booking_date' => now()->subDays(10)->toDateString(),
        ]);
        $tt = Vaccine::create(['antigen' => VaccineAntigen::TETANUS_TOXOID, 'name' => 'TT']);
        $schedule = ImmunizationSchedule::create([
            'name' => 'Workspace Maternal TT',
            'target_population' => ImmunizationSchedule::TARGET_MATERNAL,
        ]);
        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $tt->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->call('generateEpiDues');

        $this->assertContains('immunizations', $component->instance()->availableTabs());
        $this->assertDatabaseHas('immunization_records', [
            'patient_id' => $mother->id,
            'vaccine_id' => $tt->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::SCHEDULED->value,
        ]);
    }

    public function test_record_outcome_closes_active_pregnancy(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->assertSet('context.kind', 'mother')
            ->callAction('recordOutcome', data: ['outcome' => PregnancyOutcome::DELIVERED->value])
            ->assertHasNoActionErrors()
            ->assertSet('context.kind', 'unknown');

        $this->assertSame(PregnancyOutcome::DELIVERED, $episode->fresh()->outcome);
    }

    public function test_issue_book_stores_chosen_consent(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->callAction('issueBook', data: ['data_consented' => false]);

        $book = MchRecord::query()->where('owner_id', $episode->id)->first();

        $this->assertNotNull($book);
        $this->assertSame('ANC', $book->unit);
        $this->assertFalse($book->data_consented);
        $this->assertNull($book->consented_by);
    }
}
