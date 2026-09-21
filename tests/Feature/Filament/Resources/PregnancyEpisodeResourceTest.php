<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\CreatePregnancyEpisode;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\EditPregnancyEpisode;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\ListPregnancyEpisodes;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\Pages\ViewPregnancyEpisode;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;
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
});

FilamentResourceTestSuite::register([
    'resource' => PregnancyEpisodeResource::class,
    'subject' => 'PregnancyEpisode',
    'model' => PregnancyEpisode::class,
    'listPage' => ListPregnancyEpisodes::class,
    'createPage' => CreatePregnancyEpisode::class,
    'editPage' => EditPregnancyEpisode::class,
    'viewPage' => ViewPregnancyEpisode::class,
    'sortColumn' => 'edd',
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'softDeletes' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): PregnancyEpisode => PregnancyEpisode::factory()->create([
        'patient_id' => $test->patient->id,
        'branch_id' => $test->branch->id,
        ...$attributes,
    ]),
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();

        for ($index = 0; $index < $count; $index++) {
            $patient = Patient::factory()->female()->create(['branch_id' => $test->branch->id]);
            $records->push(PregnancyEpisode::factory()->create([
                'patient_id' => $patient->id,
                'branch_id' => $test->branch->id,
                'edd' => now()->addWeeks($index + 4)->toDateString(),
            ]));
        }

        return $records;
    },
    'createForm' => fn (TestCase $test): array => [
        'patient_id' => $test->patient->id,
        'gravida' => 2,
        'parity' => 1,
        'lmp' => now()->subMonths(4)->toDateString(),
        'outcome' => PregnancyOutcome::ACTIVE->value,
    ],
    'updateForm' => fn (): array => [
        'gravida' => 3,
        'parity' => 1,
    ],
    'schemaState' => fn (mixed $test, PregnancyEpisode $record): array => [
        'gravida' => $record->gravida,
        'parity' => $record->parity,
    ],
    'requiredValidation' => [
        'patient is required' => [['patient_id' => null], ['patient_id' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'patient_id' => $payload['patient_id'],
        'gravida' => $payload['gravida'],
    ],
]);
