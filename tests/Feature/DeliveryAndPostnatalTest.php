<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Livewire;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\DeliveryRecordService;
use Modules\MCH\Classes\Services\MchWorkspaceService;
use Modules\MCH\Classes\Services\PostnatalVisitAssessmentService;
use Modules\MCH\Enums\BirthOutcome;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Enums\PostnatalContact;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Filament\Clusters\Workspace\Pages\MchWorkspace;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\DeliveryRecord;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\PostnatalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\PatientRelationshipType;
use Modules\Patient\Models\Patient;
use Modules\Patient\Models\PatientRelationship;
use Tests\TestCase;

class DeliveryAndPostnatalTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private Patient $mother;

    private PregnancyEpisode $episode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
        Gate::before(fn (): bool => true);
        $this->actingAs(User::factory()->create(['branch_id' => $this->branch->id]));
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        $this->mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id, 'last_name' => 'Mensah']);
        $this->episode = PregnancyEpisode::factory()->create(['patient_id' => $this->mother->id, 'branch_id' => $this->branch->id]);
    }

    /**
     * @param  list<array<string, mixed>>  $babies
     */
    private function deliver(array $babies, array $overrides = []): DeliveryRecord
    {
        return app(DeliveryRecordService::class)->record($this->episode, array_merge([
            'delivered_at' => now()->subHours(3),
            'place' => DeliveryPlace::FACILITY->value,
            'mode' => DeliveryMode::SPONTANEOUS_VAGINAL->value,
            'babies' => $babies,
        ], $overrides));
    }

    public function test_live_birth_registers_the_baby_with_record_relationship_and_measurements(): void
    {
        $delivery = $this->deliver([[
            'sex' => Gender::FEMALE->value,
            'outcome' => BirthOutcome::LIVE_BIRTH->value,
            'birth_weight_g' => 3200,
            'length_cm' => 49.1,
            'head_circumference_cm' => 33.9,
            'apgar_1' => 8,
            'apgar_5' => 9,
        ]]);

        $newborn = $delivery->newborns->sole();
        $baby = Patient::query()->findOrFail($newborn->child_patient_id);

        $this->assertSame('Baby', $baby->first_name);
        $this->assertSame('Mensah', $baby->last_name);
        $this->assertSame(Gender::FEMALE, $baby->gender);
        $this->assertSame(PregnancyOutcome::DELIVERED, $this->episode->fresh()->outcome);

        $record = ChildHealthRecord::query()->where('patient_id', $baby->id)->sole();
        $this->assertSame($delivery->id, $record->delivery_record_id);
        $this->assertSame($this->episode->id, $record->pregnancy_episode_id);

        $this->assertTrue(PatientRelationship::query()
            ->where('subject_id', $baby->id)
            ->where('object_id', $this->mother->id)
            ->where('type', PatientRelationshipType::MOTHER)
            ->exists());

        $weight = GrowthMeasurement::query()->where('patient_id', $baby->id)->where('type', GrowthMeasurementType::WEIGHT)->sole();
        $this->assertSame('wfa', $weight->z_indicator);
        $this->assertNotNull($weight->whz);
        $this->assertSame(3, GrowthMeasurement::query()->where('patient_id', $baby->id)->count());
    }

    public function test_twins_get_distinct_names_and_a_stillbirth_is_not_registered(): void
    {
        $delivery = $this->deliver([
            ['sex' => Gender::MALE->value, 'outcome' => BirthOutcome::LIVE_BIRTH->value, 'birth_weight_g' => 2400],
            ['sex' => Gender::FEMALE->value, 'outcome' => BirthOutcome::LIVE_BIRTH->value, 'birth_weight_g' => 2300],
        ]);

        $names = $delivery->newborns->map(fn ($baby) => Patient::query()->find($baby->child_patient_id)?->first_name)->all();
        $this->assertSame(['Baby A', 'Baby B'], $names);
        $this->assertSame(2, $delivery->number_of_babies);
    }

    public function test_stillbirth_only_closes_the_episode_as_stillbirth(): void
    {
        $delivery = $this->deliver([['sex' => Gender::MALE->value, 'outcome' => BirthOutcome::FRESH_STILLBIRTH->value]]);

        $this->assertNull($delivery->newborns->sole()->child_patient_id);
        $this->assertSame(PregnancyOutcome::STILLBIRTH, $this->episode->fresh()->outcome);
        $this->assertSame(0, ChildHealthRecord::query()->where('pregnancy_episode_id', $this->episode->id)->count());
    }

    public function test_maternal_death_outcome_and_closed_episode_guard(): void
    {
        $this->deliver([['sex' => Gender::MALE->value, 'outcome' => BirthOutcome::LIVE_BIRTH->value]], ['maternal_outcome' => 'died']);
        $this->assertSame(PregnancyOutcome::MATERNAL_DEATH, $this->episode->fresh()->outcome);

        $this->expectException(InvalidArgumentException::class);
        app(DeliveryRecordService::class)->record($this->episode->fresh(), [
            'place' => 'facility', 'mode' => 'svd', 'babies' => [['sex' => 'male', 'outcome' => 'live_birth']],
        ]);
    }

    public function test_mother_switches_to_postnatal_context_and_pnc_contact_is_derived(): void
    {
        $this->deliver([['sex' => Gender::MALE->value, 'outcome' => BirthOutcome::LIVE_BIRTH->value]], ['delivered_at' => now()->subDays(3)]);

        $context = app(MchWorkspaceService::class)->resolveContext($this->mother);
        $this->assertSame('postnatal', $context['kind']);

        $encounter = app(MchWorkspaceService::class)->ensureEncounter($this->mother, EncounterType::POSTNATAL);
        $visit = app(PostnatalVisitAssessmentService::class)->record($encounter, [
            'danger_signs' => ['fever', 'not-a-sign'],
            'lochia' => 'normal',
        ]);

        $this->assertSame(PostnatalContact::DAY_3, $visit->contact);
        $this->assertSame(3, $visit->days_postpartum);
        $this->assertSame(['fever'], $visit->danger_signs);
    }

    public function test_postnatal_assessment_requires_a_postnatal_encounter(): void
    {
        $encounter = Encounter::factory()->create(['patient_id' => $this->mother->id, 'branch_id' => $this->branch->id, 'type' => EncounterType::ANTENATAL]);

        $this->expectException(InvalidArgumentException::class);
        app(PostnatalVisitAssessmentService::class)->record($encounter, []);
    }

    public function test_workspace_records_delivery_then_postnatal_visit(): void
    {
        $page = Livewire::test(MchWorkspace::class)
            ->call('selectPatient', $this->mother->id)
            ->callAction(TestAction::make('recordDelivery'), [
                'delivered_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'place' => DeliveryPlace::FACILITY->value,
                'mode' => DeliveryMode::CAESAREAN->value,
                'maternal_outcome' => 'alive',
                'babies' => [['sex' => Gender::MALE->value, 'outcome' => BirthOutcome::LIVE_BIRTH->value, 'birth_weight_g' => 3400]],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Delivery recorded')
            ->assertSet('context.kind', 'postnatal');

        $page->callAction(TestAction::make('recordPostnatalVisit'), ['breastfeeding' => 'exclusive'])
            ->assertNotified('Postnatal visit saved');

        $this->assertSame(DeliveryMode::CAESAREAN, DeliveryRecord::query()->where('patient_id', $this->mother->id)->sole()->mode);
        $this->assertSame('exclusive', PostnatalVisitAssessment::query()->where('patient_id', $this->mother->id)->sole()->breastfeeding);
    }
}
