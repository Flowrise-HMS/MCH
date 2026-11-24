<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Modules\MCH\Models\ChildHealthRecord;

/**
 * @group Maternal and child health
 *
 * Child welfare clinic (CWC) registry.
 */
class ChildHealthRecordController extends MchApiController
{
    public static function model(): string
    {
        return ChildHealthRecord::class;
    }

    public static function label(): string
    {
        return 'child health record';
    }

    public function fields(): array
    {
        return ['id', 'patient_id', 'pregnancy_episode_id', 'delivery_record_id', 'date_of_birth', 'status', 'notes', 'created_at', 'updated_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'status' => 'status'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'pregnancy_episode_id' => ['nullable', 'uuid', 'exists:pregnancy_episodes,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updateRules(?Model $record = null): array
    {
        return ['notes' => ['sometimes', 'nullable', 'string', 'max:2000']];
    }

    protected function createRecord(array $data): Model
    {
        $patient = $this->consentedPatient($data['patient_id']);

        return ChildHealthRecord::query()->create([
            ...$data,
            'patient_id' => $patient->id,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'recorded_by' => auth()->id(),
        ]);
    }
}
