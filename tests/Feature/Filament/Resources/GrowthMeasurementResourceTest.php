<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\GrowthMeasurementResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\CreateGrowthMeasurement;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\EditGrowthMeasurement;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\ListGrowthMeasurements;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\Pages\ViewGrowthMeasurement;
use Modules\MCH\Models\GrowthMeasurement;
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
    'resource' => GrowthMeasurementResource::class,
    'subject' => 'GrowthMeasurement',
    'model' => GrowthMeasurement::class,
    'listPage' => ListGrowthMeasurements::class,
    'createPage' => CreateGrowthMeasurement::class,
    'editPage' => EditGrowthMeasurement::class,
    'viewPage' => ViewGrowthMeasurement::class,
    'sortColumn' => 'date',
    'filter' => [
        'name' => 'type',
        'value' => GrowthMeasurementType::WEIGHT->value,
        'attribute' => 'type',
    ],
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): GrowthMeasurement => GrowthMeasurement::factory()->create([
        'patient_id' => $test->patient->id,
        'branch_id' => $test->branch->id,
        'type' => GrowthMeasurementType::WEIGHT,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $records->push(GrowthMeasurement::factory()->create([
                'patient_id' => $test->patient->id,
                'branch_id' => $test->branch->id,
                'type' => GrowthMeasurementType::WEIGHT,
                'date' => now()->subDays($index + 1)->toDateString(),
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'patient_id' => $test->patient->id,
        'type' => GrowthMeasurementType::WEIGHT->value,
        'value' => 12.4,
        'unit' => 'kg',
        'date' => now()->toDateString(),
    ],
    'updateForm' => fn (): array => [
        'value' => 13.1,
        'unit' => 'kg',
    ],
    'schemaState' => fn (mixed $test, GrowthMeasurement $record): array => [
        'type' => $record->type,
        'unit' => $record->unit,
    ],
    'requiredValidation' => [
        'type is required' => [['type' => null], ['type' => 'required']],
        'value is required' => [['value' => null], ['value' => 'required']],
        'unit is required' => [['unit' => null], ['unit' => 'required']],
        'date is required' => [['date' => null], ['date' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'patient_id' => $payload['patient_id'],
        'type' => $payload['type'],
        'unit' => $payload['unit'],
    ],
]);
