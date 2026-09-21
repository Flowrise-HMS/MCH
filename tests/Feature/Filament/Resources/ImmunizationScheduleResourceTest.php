<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\ImmunizationScheduleResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\CreateImmunizationSchedule;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\EditImmunizationSchedule;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\ListImmunizationSchedules;
use Modules\MCH\Filament\Clusters\MCH\Resources\ImmunizationSchedules\Pages\ViewImmunizationSchedule;
use Modules\MCH\Models\ImmunizationSchedule;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'MCH']);
});

FilamentResourceTestSuite::register([
    'resource' => ImmunizationScheduleResource::class,
    'subject' => 'ImmunizationSchedule',
    'model' => ImmunizationSchedule::class,
    'listPage' => ListImmunizationSchedules::class,
    'createPage' => CreateImmunizationSchedule::class,
    'editPage' => EditImmunizationSchedule::class,
    'viewPage' => ViewImmunizationSchedule::class,
    'searchColumn' => 'name',
    'sortColumn' => 'name',
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'uniqueField' => 'name',
    'createForm' => fn (): array => [
        'name' => 'EPI '.fake()->unique()->bothify('SCH-##'),
        'target_population' => 'child',
        'description' => 'Expanded programme on immunization',
        'is_active' => true,
    ],
    'updateForm' => fn (): array => [
        'description' => 'Updated immunization schedule',
        'is_active' => true,
    ],
    'schemaState' => fn (mixed $test, ImmunizationSchedule $record): array => [
        'name' => $record->name,
        'target_population' => $record->target_population,
    ],
    'requiredValidation' => [
        'name is required' => [['name' => null], ['name' => 'required']],
        'target population is required' => [['target_population' => null], ['target_population' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'name' => $payload['name'],
        'target_population' => $payload['target_population'],
    ],
]);
