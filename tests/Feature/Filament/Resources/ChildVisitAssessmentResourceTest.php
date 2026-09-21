<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\Core\Models\Branch;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\CreateChildVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\EditChildVisitAssessment;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\ListChildVisitAssessments;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\Pages\ViewChildVisitAssessment;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\Patient\Models\Patient;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'Staff', 'Clinical', 'MCH']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
    $this->patient = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
    $this->record = ChildHealthRecord::factory()->create([
        'patient_id' => $this->patient->id,
        'branch_id' => $this->branch->id,
    ]);
    $this->encounter = Encounter::factory()->create([
        'patient_id' => $this->patient->id,
        'branch_id' => $this->branch->id,
        'type' => EncounterType::CHILD_WELFARE,
    ]);
});

FilamentResourceTestSuite::register([
    'resource' => ChildVisitAssessmentResource::class,
    'subject' => 'ChildVisitAssessment',
    'model' => ChildVisitAssessment::class,
    'listPage' => ListChildVisitAssessments::class,
    'createPage' => CreateChildVisitAssessment::class,
    'editPage' => EditChildVisitAssessment::class,
    'viewPage' => ViewChildVisitAssessment::class,
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): ChildVisitAssessment => ChildVisitAssessment::factory()->create([
        'encounter_id' => $test->encounter->id,
        'child_health_record_id' => $test->record->id,
        'branch_id' => $test->branch->id,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $encounter = Encounter::factory()->create([
                'patient_id' => $test->patient->id,
                'branch_id' => $test->branch->id,
                'type' => EncounterType::CHILD_WELFARE,
            ]);
            $records->push(ChildVisitAssessment::factory()->create([
                'encounter_id' => $encounter->id,
                'child_health_record_id' => $test->record->id,
                'branch_id' => $test->branch->id,
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'encounter_id' => $test->encounter->id,
        'child_health_record_id' => $test->record->id,
        'referral_required' => false,
        'notes' => 'Routine CWC visit',
    ],
    'updateForm' => fn (): array => [
        'notes' => 'Updated CWC notes',
        'referral_required' => false,
    ],
    'schemaState' => fn (mixed $test, ChildVisitAssessment $record): array => [
        'referral_required' => $record->referral_required,
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'encounter_id' => $payload['encounter_id'],
        'child_health_record_id' => $payload['child_health_record_id'],
    ],
]);
