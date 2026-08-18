<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Fhir\FhirEpisodeOfCareTransformer;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class FhirEpisodeOfCareTransformerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'FHIR', 'MCH']);
    }

    public function test_to_fhir_maps_active_episode(): void
    {
        $branch = Branch::factory()->create();
        $patient = Patient::factory()->female()->create(['branch_id' => $branch->id]);
        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'lmp' => now()->subWeeks(12)->toDateString(),
        ]);

        $resource = app(FhirEpisodeOfCareTransformer::class)->toFhir($episode);

        $this->assertSame('EpisodeOfCare', $resource['resourceType']);
        $this->assertSame('active', $resource['status']);
        $this->assertSame("Patient/{$patient->id}", $resource['patient']['reference']);
    }

    public function test_to_fhir_maps_finished_episode(): void
    {
        $branch = Branch::factory()->create();
        $patient = Patient::factory()->female()->create(['branch_id' => $branch->id]);
        $episode = PregnancyEpisode::create([
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'outcome' => PregnancyOutcome::DELIVERED,
        ]);

        $resource = app(FhirEpisodeOfCareTransformer::class)->toFhir($episode);

        $this->assertSame('finished', $resource['status']);
    }

    public function test_validate_business_rules_requires_status(): void
    {
        $transformer = app(FhirEpisodeOfCareTransformer::class);

        $this->assertSame(
            ['EpisodeOfCare.status is required'],
            $transformer->validateBusinessRules(['resourceType' => 'EpisodeOfCare']),
        );
        $this->assertSame([], $transformer->validateBusinessRules(['status' => 'active']));
    }
}
