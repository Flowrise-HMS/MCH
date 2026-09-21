<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\CreateMaternalVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\EditMaternalVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\ListMaternalVisitAssessments;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\Pages\ViewMaternalVisitAssessment;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'Staff', 'Clinical', 'MCH']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
    $this->patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
    $this->episode = PregnancyEpisode::factory()->create([
        'patient_id' => $this->patient->id,
        'branch_id' => $this->branch->id,
    ]);
    $this->encounter = Encounter::factory()->create([
        'patient_id' => $this->patient->id,
        'branch_id' => $this->branch->id,
        'type' => EncounterType::ANTENATAL,
    ]);
});

FilamentResourceTestSuite::register([
    'resource' => MaternalVisitAssessmentResource::class,
    'subject' => 'MaternalVisitAssessment',
    'model' => MaternalVisitAssessment::class,
    'listPage' => ListMaternalVisitAssessments::class,
    'createPage' => CreateMaternalVisitAssessment::class,
    'editPage' => EditMaternalVisitAssessment::class,
    'viewPage' => ViewMaternalVisitAssessment::class,
    'sortColumn' => 'visit_number',
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): MaternalVisitAssessment => MaternalVisitAssessment::factory()->create([
        'encounter_id' => $test->encounter->id,
        'pregnancy_episode_id' => $test->episode->id,
        'branch_id' => $test->branch->id,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $encounter = Encounter::factory()->create([
                'patient_id' => $test->patient->id,
                'branch_id' => $test->branch->id,
                'type' => EncounterType::ANTENATAL,
            ]);
            $records->push(MaternalVisitAssessment::factory()->create([
                'encounter_id' => $encounter->id,
                'pregnancy_episode_id' => $test->episode->id,
                'branch_id' => $test->branch->id,
                'visit_number' => $index + 1,
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'encounter_id' => $test->encounter->id,
        'pregnancy_episode_id' => $test->episode->id,
        'visit_number' => 1,
        'ga_weeks' => 20,
        'referral_required' => false,
    ],
    'updateForm' => fn (): array => [
        'visit_number' => 2,
        'ga_weeks' => 24,
    ],
    'schemaState' => fn (mixed $test, MaternalVisitAssessment $record): array => [
        'visit_number' => $record->visit_number,
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'encounter_id' => $payload['encounter_id'],
        'pregnancy_episode_id' => $payload['pregnancy_episode_id'],
        'visit_number' => $payload['visit_number'],
    ],
]);
