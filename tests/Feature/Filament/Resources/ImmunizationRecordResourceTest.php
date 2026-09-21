<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\ImmunizationRecordResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\CreateImmunizationRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\EditImmunizationRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\ListImmunizationRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationRecords\Pages\ViewImmunizationRecord;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\Vaccine;
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
    $this->vaccine = Vaccine::factory()->create();
});

FilamentResourceTestSuite::register([
    'resource' => ImmunizationRecordResource::class,
    'subject' => 'ImmunizationRecord',
    'model' => ImmunizationRecord::class,
    'listPage' => ListImmunizationRecords::class,
    'createPage' => CreateImmunizationRecord::class,
    'editPage' => EditImmunizationRecord::class,
    'viewPage' => ViewImmunizationRecord::class,
    'searchColumn' => 'batch_lot',
    'sortColumn' => 'administered_date',
    'filter' => [
        'name' => 'status',
        'value' => ImmunizationStatus::SCHEDULED->value,
        'attribute' => 'status',
    ],
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): ImmunizationRecord => ImmunizationRecord::factory()->create([
        'patient_id' => $test->patient->id,
        'vaccine_id' => $test->vaccine->id,
        'branch_id' => $test->branch->id,
        'status' => ImmunizationStatus::SCHEDULED,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $records->push(ImmunizationRecord::factory()->create([
                'patient_id' => $test->patient->id,
                'vaccine_id' => $test->vaccine->id,
                'branch_id' => $test->branch->id,
                'status' => ImmunizationStatus::SCHEDULED,
                'dose_sequence' => $index + 1,
                'batch_lot' => 'LOT-'.$index.'-'.$test->patient->id,
                'administered_date' => now()->subDays($index + 1)->toDateString(),
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'patient_id' => $test->patient->id,
        'vaccine_id' => $test->vaccine->id,
        'dose_sequence' => 1,
        'status' => ImmunizationStatus::SCHEDULED->value,
    ],
    'updateForm' => fn (): array => [
        'dose_sequence' => 2,
        'status' => ImmunizationStatus::SCHEDULED->value,
    ],
    'schemaState' => fn (mixed $test, ImmunizationRecord $record): array => [
        'dose_sequence' => $record->dose_sequence,
        'status' => $record->status,
    ],
    'requiredValidation' => [
        'patient is required' => [['patient_id' => null], ['patient_id' => 'required']],
        'vaccine is required' => [['vaccine_id' => null], ['vaccine_id' => 'required']],
        'status is required' => [['status' => null], ['status' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'patient_id' => $payload['patient_id'],
        'vaccine_id' => $payload['vaccine_id'],
        'dose_sequence' => $payload['dose_sequence'],
    ],
]);
