<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Filament\Widgets\PatientVitalsHistoryWidget;
use Modules\Clinical\Filament\Widgets\PatientVitalsOverviewWidget;
use Modules\Clinical\Models\VitalSign;
use Modules\Core\Models\Branch;
use Modules\Core\Support\OptionalClass;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Filament\Widgets\PatientChildVisitsWidget;
use Modules\MCH\Filament\Widgets\PatientGrowthMeasurementsWidget;
use Modules\MCH\Filament\Widgets\PatientImmunizationsWidget;
use Modules\MCH\Filament\Widgets\PatientMaternalVisitsWidget;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchWorkspaceWidgetsTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->default()->create();
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_resolves_clinical_vitals_overview_via_optional_class(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id);

        $this->assertSame(
            PatientVitalsOverviewWidget::class,
            $component->instance()->vitalsOverviewWidgetClass(),
        );
        $this->assertSame(
            PatientVitalsOverviewWidget::class,
            OptionalClass::resolve(
                'Modules\\Clinical\\Filament\\Widgets\\PatientVitalsOverviewWidget',
                'Clinical',
            ),
        );

        $this->assertStringContainsString(
            'OptionalClass::resolve',
            (string) file_get_contents(base_path('Modules/MCH/app/Filament/Clusters/Workspace/Pages/MchWorkspace.php')),
        );
        $this->assertStringContainsString(
            'PatientVitalsOverviewWidget',
            (string) file_get_contents(base_path('Modules/MCH/app/Filament/Clusters/Workspace/Pages/MchWorkspace.php')),
        );

        $component->assertSee('Current Vitals');
    }

    public function test_mother_footer_includes_vitals_history_and_anc_tables(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id);

        $widgets = collect(invade($component->instance())->getFooterWidgets())
            ->map(fn ($configuration) => is_object($configuration) ? $configuration->widget : $configuration)
            ->all();

        $this->assertContains(PatientVitalsHistoryWidget::class, $widgets);
        $this->assertContains(PatientMaternalVisitsWidget::class, $widgets);
        $this->assertContains(PatientImmunizationsWidget::class, $widgets);
        $this->assertNotContains(PatientChildVisitsWidget::class, $widgets);

        $vitalsConfig = collect(invade($component->instance())->getFooterWidgets())
            ->first(fn ($configuration) => is_object($configuration) && $configuration->widget === PatientVitalsHistoryWidget::class);

        $this->assertNotNull($vitalsConfig);
        $this->assertSame(['patientId' => $mother->id], $vitalsConfig->getProperties());
    }

    public function test_child_footer_includes_vitals_growth_and_immunization_tables(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $child->id);

        $widgets = collect(invade($component->instance())->getFooterWidgets())
            ->map(fn ($configuration) => is_object($configuration) ? $configuration->widget : $configuration)
            ->all();

        $this->assertContains(PatientVitalsHistoryWidget::class, $widgets);
        $this->assertContains(PatientChildVisitsWidget::class, $widgets);
        $this->assertContains(PatientImmunizationsWidget::class, $widgets);
        $this->assertContains(PatientGrowthMeasurementsWidget::class, $widgets);
        $this->assertContains('vitals', $component->instance()->availableTabs());
    }

    public function test_vitals_history_widget_shows_opd_recorded_vitals_for_patient(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);

        $patient = Patient::factory()->create(['branch_id' => $this->branch->id]);
        $vital = VitalSign::factory()->forPatient($patient)->create([
            'recorded_by' => $this->user->id,
            'heart_rate' => 92,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(PatientVitalsHistoryWidget::class, [
            'patientId' => $patient->id,
        ])
            ->loadTable()
            ->assertCanSeeTableRecords([$vital])
            ->assertSee('92');
    }

    public function test_maternal_visits_widget_lists_saved_assessments(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);

        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $visit = MaternalVisitAssessment::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'ga_weeks' => 28,
        ]);

        Livewire::test(PatientMaternalVisitsWidget::class, [
            'patientId' => $mother->id,
        ])
            ->loadTable()
            ->assertCanSeeTableRecords([$visit])
            ->assertSee('28');
    }

    public function test_child_record_widgets_list_saved_data(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs($this->user);

        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
        $visit = ChildVisitAssessment::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);
        $growth = GrowthMeasurement::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
            'value' => 6.5,
        ]);
        $imm = ImmunizationRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(PatientChildVisitsWidget::class, ['patientId' => $child->id])
            ->loadTable()
            ->assertCanSeeTableRecords([$visit]);

        Livewire::test(PatientGrowthMeasurementsWidget::class, ['patientId' => $child->id])
            ->loadTable()
            ->assertCanSeeTableRecords([$growth])
            ->assertSee('6.5');

        Livewire::test(PatientImmunizationsWidget::class, ['patientId' => $child->id])
            ->loadTable()
            ->assertCanSeeTableRecords([$imm]);
    }
}
