<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;

/**
 * @group Maternal and child health
 *
 * Anthropometry with WHO Child Growth Standards Z-scores (computed server-side).
 */
class GrowthMeasurementController extends MchApiController
{
    protected string $defaultSort = '-date';

    public static function model(): string
    {
        return GrowthMeasurement::class;
    }

    public function fields(): array
    {
        return ['id', 'patient_id', 'encounter_id', 'type', 'value', 'unit', 'date', 'z_score', 'z_indicator', 'whz', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'type' => 'type'];
    }

    public function sortable(): array
    {
        return ['date', 'created_at'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'type' => ['required', Rule::enum(GrowthMeasurementType::class)],
            'value' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'string', 'max:8'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    protected function createRecord(array $data): Model
    {
        $patient = $this->consentedPatient($data['patient_id']);

        return GrowthMeasurement::query()->create([
            ...$data,
            'patient_id' => $patient->id,
            'date' => $data['date'] ?? now()->toDateString(),
            'recorded_by' => auth()->id(),
        ]);
    }
}
