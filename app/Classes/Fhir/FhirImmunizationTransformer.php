<?php

namespace Modules\MCH\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;

class FhirImmunizationTransformer implements FhirResourceContract
{
    public function resourceType(): string
    {
        return 'Immunization';
    }

    public function toFhir(Model $model): array
    {
        /** @var ImmunizationRecord $record */
        $record = $model;

        return [
            'resourceType' => 'Immunization',
            'id' => $record->id,
            'status' => match ($record->status) {
                ImmunizationStatus::SCHEDULED => 'not-done',
                ImmunizationStatus::ADMINISTERED => 'completed',
                ImmunizationStatus::DECLINED => 'not-done',
                ImmunizationStatus::CONTRAINDICATED => 'not-done',
            },
            'vaccineCode' => [
                'coding' => [[
                    'system' => 'urn:oid:2.16.840.1.113883.6.59',
                    'code' => $record->vaccine->antigen->value,
                    'display' => $record->vaccine->name,
                ]],
            ],
            'patient' => [
                'reference' => 'Patient/'.$record->patient_id,
            ],
            'occurrenceDateTime' => $record->administered_date?->toIso8601String(),
            'lotNumber' => $record->batch_lot,
            'site' => $record->site ? [
                'coding' => [['display' => $record->site]],
            ] : null,
            'route' => $record->route ? [
                'coding' => [['display' => $record->route]],
            ] : null,
        ];
    }

    public function fromFhir(array $fhirResource): array
    {
        throw new LogicException('FHIR Immunization import is not yet implemented.');
    }

    public function findById(string $id): ?Model
    {
        return ImmunizationRecord::find($id);
    }

    /**
     * `toFhir()` reads `$record->vaccine->antigen` and `->name` without a
     * relationLoaded() guard, so a bare query both N+1s and dereferences null on any
     * record whose vaccine is missing.
     */
    public function query(): Builder
    {
        return ImmunizationRecord::query()->with(['vaccine']);
    }

    public function searchableParameters(): array
    {
        return [
            '_id' => ['column' => 'id'],
            'patient' => ['column' => 'patient_id'],
            'status' => ['column' => 'status'],
            'date' => ['column' => 'administered_date'],
        ];
    }

    public function validateBusinessRules(array $fhirResource): array
    {
        return [];
    }
}
