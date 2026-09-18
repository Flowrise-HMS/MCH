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
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\ImmunizationScheduleItem;
use Modules\MCH\Models\Vaccine;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
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

    public function test_vaccination_card_shows_schedule_doses_without_records(): void
    {
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create([
            'branch_id' => $branch->id,
            'date_of_birth' => now()->subDays(60)->toDateString(),
        ]);
        $bcg = Vaccine::create(['antigen' => VaccineAntigen::BCG, 'name' => 'BCG']);
        $measles = Vaccine::create(['antigen' => VaccineAntigen::MEASLES_RUBELLA, 'name' => 'Measles-Rubella']);
        $schedule = ImmunizationSchedule::create(['name' => 'Card EPI', 'target_population' => 'child']);
        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $bcg->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 0,
            'maximum_age_days' => 14,
        ]);
        ImmunizationScheduleItem::create([
            'immunization_schedule_id' => $schedule->id,
            'vaccine_id' => $measles->id,
            'dose_sequence' => 1,
            'minimum_age_days' => 270,
        ]);

        $component = Livewire::test(VaccinationCard::class, ['patientId' => $child->id])
            ->assertOk()
            ->assertSee('BCG')
            ->assertSee('(overdue)')
            ->assertSee('Measles-Rubella')
            ->assertDontSee('(due)');

        $rows = $component->instance()->rows();

        $this->assertCount(2, $rows);
        $this->assertSame('overdue', $rows[0]['classification']);
        $this->assertSame('not_yet_due', $rows[1]['classification']);
    }

    public function test_vaccination_card_requires_shield_permission(): void
    {
        Permission::findOrCreate('View VaccinationCard', 'web');
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        Livewire::test(VaccinationCard::class)
            ->assertForbidden();
    }
}
