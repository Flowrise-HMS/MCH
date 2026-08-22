<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Models\Vaccine;
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
}
