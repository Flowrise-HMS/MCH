<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\CreateVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\EditVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\ListVaccines;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages\ViewVaccine;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\VaccineResource;
use Modules\MCH\Models\Vaccine;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('MCH');
    $this->migrateModules(['Core', 'Patient', 'MCH']);
});

FilamentResourceTestSuite::register([
    'resource' => VaccineResource::class,
    'subject' => 'Vaccine',
    'model' => Vaccine::class,
    'listPage' => ListVaccines::class,
    'createPage' => CreateVaccine::class,
    'editPage' => EditVaccine::class,
    'viewPage' => ViewVaccine::class,
    'searchColumn' => 'name',
    'hasBulkDelete' => false,
    'hasRecordDelete' => true,
    'hasTableDelete' => true,
    'uniqueField' => 'antigen',
    'makeRecord' => function (TestCase $test, array $attributes = []): Vaccine {
        return Vaccine::factory()->create($attributes);
    },
    'makeRecords' => function (TestCase $test, int $count) {
        $records = collect();
        $antigens = collect(VaccineAntigen::cases())->take(min($count, count(VaccineAntigen::cases())));

        foreach ($antigens as $antigen) {
            $records->push(Vaccine::factory()->create([
                'antigen' => $antigen,
                'name' => $antigen->getLabel().' '.$antigen->value,
            ]));
        }

        return $records;
    },
    'createForm' => function (): array {
        $used = Vaccine::query()->pluck('antigen')->map(
            fn (mixed $antigen): string => $antigen instanceof VaccineAntigen ? $antigen->value : (string) $antigen
        )->all();

        $antigen = collect(VaccineAntigen::cases())->first(
            fn (VaccineAntigen $case): bool => ! in_array($case->value, $used, true)
        ) ?? VaccineAntigen::BCG;

        return [
            'antigen' => $antigen->value,
            'name' => $antigen->getLabel(),
            'route' => 'intramuscular',
            'site' => 'left_upper_arm',
            'is_active' => true,
        ];
    },
    'updateForm' => fn (): array => [
        'name' => 'Updated vaccine label',
        'route' => 'oral',
    ],
    'schemaState' => fn (mixed $test, Vaccine $record): array => [
        'antigen' => $record->antigen,
        'name' => $record->name,
    ],
    'requiredValidation' => [
        'antigen is required' => [['antigen' => null], ['antigen' => 'required']],
        'name is required' => [['name' => null], ['name' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'antigen' => $payload['antigen'],
        'name' => $payload['name'],
    ],
]);
