<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\MCH\Enums\EddSource;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Models\PregnancyEpisode;

/**
 * @group Maternal and child health
 *
 * Pregnancy episodes (antenatal registry). Risk level and EDD are derived
 * server-side. Close with a delivery (POST deliveries) or an outcome update.
 */
class PregnancyEpisodeController extends MchApiController
{
    public static function model(): string
    {
        return PregnancyEpisode::class;
    }

    public static function label(): string
    {
        return 'pregnancy';
    }

    public function fields(): array
    {
        return ['id', 'patient_id', 'gravida', 'parity', 'lmp', 'edd', 'edd_source', 'multiple_gestation', 'risk_level', 'risk_override',
            'risk_factors', 'booking_date', 'booking_ga_weeks', 'outcome', 'created_at', 'updated_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'outcome' => 'outcome', 'risk_level' => 'risk_level'];
    }

    public function sortable(): array
    {
        return ['created_at', 'edd', 'booking_date'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'gravida' => ['nullable', 'integer', 'min:1', 'max:30'],
            'parity' => ['nullable', 'integer', 'min:0', 'max:30'],
            'lmp' => ['nullable', 'date', 'before_or_equal:today'],
            'edd' => ['nullable', 'date'],
            'edd_source' => ['nullable', Rule::enum(EddSource::class)],
            'multiple_gestation' => ['nullable', 'boolean'],
            'risk_factors' => ['nullable', 'array'],
            'booking_date' => ['nullable', 'date'],
        ];
    }

    public function updateRules(?Model $record = null): array
    {
        return collect(parent::updateRules($record))
            ->except('patient_id')
            ->put('outcome', ['sometimes', Rule::enum(PregnancyOutcome::class)])
            ->all();
    }

    protected function createRecord(array $data): Model
    {
        $patient = $this->consentedPatient($data['patient_id']);

        return PregnancyEpisode::query()->create([...$data, 'patient_id' => $patient->id, 'recorded_by' => auth()->id()]);
    }
}
