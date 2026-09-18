<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Filament\Clusters\Workspace\Pages\ClinicalWorkspace;
use Modules\Core\Models\Branch;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchWorkspaceHeaderActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);

        $this->branch = Branch::factory()->create();
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create(['branch_id' => $this->branch->id]));
        Filament::setCurrentPanel(Filament::getDefaultPanel());
    }

    public function test_header_actions_appear_after_selecting_a_child_and_disappear_on_clear(): void
    {
        $child = $this->child();

        $page = Livewire::test(MchWorkspace::class);

        $this->assertSame([], $page->instance()->getCachedHeaderActions());

        $page->call('selectPatient', $child->id)
            ->assertSet('mode', 'patient')
            ->assertSet('context.kind', 'child');

        $this->assertSame(
            ['open_clinical_workspace', 'view_timeline', 'view_profile', 'view_medication_canvas', 'vaccination_card', 'group:More Actions'],
            $this->headerActionKeys($page->instance()->getCachedHeaderActions()),
        );

        $page->assertActionVisible('vaccination_card')
            ->assertActionVisible('view_timeline')
            ->assertActionHasUrl('open_clinical_workspace', ClinicalWorkspace::getUrl(['patientId' => $child->id]));

        $page->call('clearPatient')->assertSet('mode', 'home');

        $this->assertSame([], $page->instance()->getCachedHeaderActions());
    }

    public function test_header_actions_are_not_duplicated_when_mounting_with_a_patient(): void
    {
        $child = $this->child();

        $keys = $this->headerActionKeys(
            Livewire::test(MchWorkspace::class, ['patientId' => $child->id])
                ->assertSet('mode', 'patient')
                ->instance()
                ->getCachedHeaderActions(),
        );

        $this->assertContains('view_timeline', $keys);
        $this->assertSame(array_values(array_unique($keys)), $keys);
    }

    public function test_vaccination_card_is_hidden_for_mothers(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $mother->id)
            ->assertSet('context.kind', 'mother')
            ->assertActionHidden('vaccination_card')
            ->assertActionVisible('view_profile');
    }

    private function child(): Patient
    {
        $child = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);

        ChildHealthRecord::factory()->create([
            'patient_id' => $child->id,
            'branch_id' => $this->branch->id,
        ]);

        return $child;
    }

    /**
     * @param  array<int, mixed>  $actions
     * @return list<string>
     */
    private function headerActionKeys(array $actions): array
    {
        return collect($actions)
            ->map(fn ($action): string => $action instanceof Action
                ? $action->getName()
                : 'group:'.$action->getLabel())
            ->values()
            ->all();
    }
}
