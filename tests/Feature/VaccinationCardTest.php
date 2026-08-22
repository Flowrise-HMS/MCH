<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Filament\Clusters\MCH\Pages\VaccinationCard;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class VaccinationCardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
    }

    public function test_vaccination_card_lists_patient_immunization_records(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);
        $vaccine = Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'BCG',
        ]);

        ImmunizationRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'vaccine_id' => $vaccine->id,
            'dose_sequence' => 1,
            'status' => ImmunizationStatus::ADMINISTERED,
            'administered_date' => now()->toDateString(),
            'batch_lot' => 'BCG-CARD-001',
        ]);

        Livewire::test(VaccinationCard::class, ['patientId' => $child->id])
            ->assertOk()
            ->assertSee('BCG')
            ->assertSee('BCG-CARD-001')
            ->assertSee($child->full_name)
            ->assertSee('id="vaccination-card-print"', false)
            ->assertSee('@media print', false)
            ->assertSee('window.print()', false);
    }
}
