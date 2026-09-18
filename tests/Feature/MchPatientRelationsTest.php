<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Filament\RelationManagers\Patient\PatientGrowthMeasurementsRelationManager;
use Modules\MCH\Filament\RelationManagers\Patient\PatientImmunizationRecordsRelationManager;
use Modules\MCH\Filament\RelationManagers\Patient\PatientPregnancyEpisodesRelationManager;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Filament\Clusters\Patient\Resources\Patients\PatientResource;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchPatientRelationsTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'MCH']);
        $this->branch = Branch::factory()->create();
    }

    public function test_patient_exposes_mch_relations(): void
    {
        $mother = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
        PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'outcome' => PregnancyOutcome::DELIVERED,
        ]);
        $active = PregnancyEpisode::factory()->create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
        ]);
        GrowthMeasurement::create([
            'patient_id' => $mother->id,
            'branch_id' => $this->branch->id,
            'type' => GrowthMeasurementType::FUNDAL_HEIGHT,
            'value' => 28,
            'unit' => 'cm',
            'date' => now()->toDateString(),
        ]);

        $this->assertSame(2, $mother->pregnancyEpisodes()->count());
        $this->assertSame($active->id, $mother->activePregnancyEpisode?->id);
        $this->assertSame(1, $mother->growthMeasurements()->count());
        $this->assertSame(0, $mother->immunizationRecords()->count());
        $this->assertNull($mother->childHealthRecord);
    }

    public function test_patient_resource_lists_mch_relation_managers(): void
    {
        $relations = PatientResource::getRelations();

        $this->assertContains(PatientPregnancyEpisodesRelationManager::class, $relations);
        $this->assertContains(PatientImmunizationRecordsRelationManager::class, $relations);
        $this->assertContains(PatientGrowthMeasurementsRelationManager::class, $relations);
    }
}
