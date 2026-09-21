<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\CreateChildHealthRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\EditChildHealthRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\ListChildHealthRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\Pages\ViewChildHealthRecord;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\Patient\Models\Patient;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'MCH']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
    $this->patient = Patient::factory()->child()->create(['branch_id' => $this->branch->id]);
});

FilamentResourceTestSuite::register([
    'resource' => ChildHealthRecordResource::class,
    'subject' => 'ChildHealthRecord',
    'model' => ChildHealthRecord::class,
    'listPage' => ListChildHealthRecords::class,
    'createPage' => CreateChildHealthRecord::class,
    'editPage' => EditChildHealthRecord::class,
    'viewPage' => ViewChildHealthRecord::class,
    'sortColumn' => 'date_of_birth',
    'filter' => [
        'name' => 'status',
        'value' => ChildHealthRecordStatus::ACTIVE->value,
        'attribute' => 'status',
    ],
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'softDeletes' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): ChildHealthRecord => ChildHealthRecord::factory()->create([
        'patient_id' => $test->patient->id,
        'branch_id' => $test->branch->id,
        'status' => ChildHealthRecordStatus::ACTIVE,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $patient = Patient::factory()->child()->create(['branch_id' => $test->branch->id]);
            $records->push(ChildHealthRecord::factory()->create([
                'patient_id' => $patient->id,
                'branch_id' => $test->branch->id,
                'status' => ChildHealthRecordStatus::ACTIVE,
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'patient_id' => $test->patient->id,
        'date_of_birth' => '2024-01-15',
        'status' => ChildHealthRecordStatus::ACTIVE->value,
        'notes' => 'Routine child welfare record',
    ],
    'updateForm' => fn (): array => [
        'notes' => 'Updated child health notes',
        'status' => ChildHealthRecordStatus::ACTIVE->value,
    ],
    'schemaState' => fn (mixed $test, ChildHealthRecord $record): array => [
        'status' => $record->status,
    ],
    'requiredValidation' => [
        'patient is required' => [['patient_id' => null], ['patient_id' => 'required']],
        'status is required' => [['status' => null], ['status' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'patient_id' => $payload['patient_id'],
        'status' => $payload['status'],
    ],
]);
