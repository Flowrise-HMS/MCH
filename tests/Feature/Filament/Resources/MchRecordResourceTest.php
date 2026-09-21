<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\MchRecordResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\CreateMchRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\EditMchRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\ListMchRecords;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages\ViewMchRecord;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'MCH']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
    $this->patient = Patient::factory()->female()->create(['branch_id' => $this->branch->id]);
    $this->episode = PregnancyEpisode::factory()->create([
        'patient_id' => $this->patient->id,
        'branch_id' => $this->branch->id,
    ]);
});

FilamentResourceTestSuite::register([
    'resource' => MchRecordResource::class,
    'subject' => 'MchRecord',
    'model' => MchRecord::class,
    'listPage' => ListMchRecords::class,
    'createPage' => CreateMchRecord::class,
    'editPage' => EditMchRecord::class,
    'viewPage' => ViewMchRecord::class,
    'searchColumn' => 'serial_number',
    'sortColumn' => 'serial_number',
    'filter' => [
        'name' => 'unit',
        'value' => 'ANC',
        'attribute' => 'unit',
    ],
    'hasBulkDelete' => false,
    'hasRecordDelete' => false,
    'hasTableDelete' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): MchRecord => MchRecord::factory()->create([
        'owner_type' => PregnancyEpisode::class,
        'owner_id' => $test->episode->id,
        'unit' => 'ANC',
        'branch_id' => $test->branch->id,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $patient = Patient::factory()->female()->create(['branch_id' => $test->branch->id]);
            $episode = PregnancyEpisode::factory()->create([
                'patient_id' => $patient->id,
                'branch_id' => $test->branch->id,
            ]);
            $records->push(MchRecord::factory()->create([
                'owner_type' => PregnancyEpisode::class,
                'owner_id' => $episode->id,
                'unit' => 'ANC',
                'branch_id' => $test->branch->id,
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'owner_type' => PregnancyEpisode::class,
        'owner_id' => $test->episode->id,
        'unit' => 'ANC',
        'data_consented' => true,
    ],
    'updateForm' => fn (): array => [
        'data_consented' => false,
    ],
    'schemaState' => fn (mixed $test, MchRecord $record): array => [
        'unit' => $record->unit,
    ],
    'requiredValidation' => [
        'owner type is required' => [['owner_type' => null], ['owner_type' => 'required']],
        'unit is required' => [['unit' => null], ['unit' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'owner_type' => $payload['owner_type'],
        'owner_id' => $payload['owner_id'],
        'unit' => $payload['unit'],
    ],
]);
