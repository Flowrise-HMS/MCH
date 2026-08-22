<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Models\Branch;
use Modules\Core\Settings\FeatureSettings;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MchWorkspacePageTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
    }

    public function test_mother_patient_exposes_mother_tabs(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->assertSet('mode', 'patient')
            ->assertSet('context.kind', 'mother')
            ->assertSet('activeTab', 'overview');

        $this->assertContains('anc-visit', $component->instance()->availableTabs());
    }

    public function test_child_patient_exposes_child_tabs(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $child->id)
            ->assertSet('context.kind', 'child');

        $this->assertContains('immunizations', $component->instance()->availableTabs());
    }

    public function test_unknown_context_limits_tabs_to_overview(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $patient = Patient::factory()->create(['branch_id' => $this->branch->id]);

        $component = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $patient->id)
            ->assertSet('context.kind', 'unknown');

        $this->assertSame(['overview'], $component->instance()->availableTabs());
    }

    public function test_navigation_hidden_when_feature_flag_disabled(): void
    {
        $this->artisan('migrate', [
            '--path' => base_path('Modules/Core/database/settings'),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $settings = app(FeatureSettings::class);
        $settings->mch_workspace_enabled = false;
        $settings->save();

        $this->assertFalse(MchWorkspace::shouldRegisterNavigation());

        $settings->mch_workspace_enabled = true;
        $settings->save();
    }

    public function test_page_requires_shield_permission(): void
    {
        Permission::findOrCreate('View MchWorkspace', 'web');
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        Livewire::test(MchWorkspace::class)
            ->assertForbidden();
    }
}
