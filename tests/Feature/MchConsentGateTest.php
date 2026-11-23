<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Fhir\FhirEpisodeOfCareTransformer;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Classes\Services\MchConsentGate;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MchConsentGateTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
        config(['mch.consent.enforce' => true]);
    }

    private function episode(bool $consented): PregnancyEpisode
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        $episode = PregnancyEpisode::factory()->create(['patient_id' => $mother->id, 'branch_id' => $this->branch->id]);
        app(MchBookIssuanceService::class)->issue($episode, $this->branch, 'ANC', ['data_consented' => $consented]);

        return $episode;
    }

    public function test_fhir_only_exposes_consented_patients(): void
    {
        $this->actingAs(User::factory()->create());
        $consented = $this->episode(true);
        $withheld = $this->episode(false);
        $transformer = app(FhirEpisodeOfCareTransformer::class);

        $ids = $transformer->query()->pluck('id')->all();

        $this->assertContains($consented->id, $ids);
        $this->assertNotContains($withheld->id, $ids);
        $this->assertNotNull($transformer->findById($consented->id));
        $this->assertNull($transformer->findById($withheld->id));
    }

    public function test_override_permission_and_disabled_enforcement_bypass_the_gate(): void
    {
        $withheld = $this->episode(false);
        $gate = app(MchConsentGate::class);

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertFalse($gate->allowsSharing($withheld->patient_id));

        Permission::findOrCreate(MchConsentGate::OVERRIDE_PERMISSION, 'web');
        $user->givePermissionTo(MchConsentGate::OVERRIDE_PERMISSION);
        $this->assertTrue($gate->allowsSharing($withheld->patient_id, $user->fresh()));

        config(['mch.consent.enforce' => false]);
        $this->actingAs(User::factory()->create());
        $this->assertTrue($gate->allowsSharing($withheld->patient_id));
        $this->assertContains($withheld->id, app(FhirEpisodeOfCareTransformer::class)->query()->pluck('id')->all());
    }

    public function test_workspace_records_missing_consent(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create(['branch_id' => $this->branch->id]));
        Filament::setCurrentPanel(Filament::getDefaultPanel());
        $withheld = $this->episode(false);

        Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $withheld->patient_id)
            ->assertSee('Data-sharing consent not recorded')
            ->callAction(TestAction::make('recordConsent'))
            ->assertNotified('Consent recorded');

        $this->assertTrue(app(MchConsentGate::class)->hasConsent($withheld->patient_id));
    }
}
