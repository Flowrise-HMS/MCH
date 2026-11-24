<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Models\MaternalVisitAssessment;

/**
 * @group Maternal and child health
 *
 * Antenatal (ANC) visits. BP and weight are stored as Clinical vital signs;
 * any danger sign flags a referral.
 */
class AncVisitController extends MchVisitController
{
    public static function model(): string
    {
        return MaternalVisitAssessment::class;
    }

    public static function label(): string
    {
        return 'ANC visit';
    }

    public function fields(): array
    {
        return ['id', 'encounter_id', 'patient_id', 'pregnancy_episode_id', 'visit_number', 'ga_weeks', 'ga_days', 'fetal_heart_rate',
            'presentation', 'edema', 'urine_protein', 'urine_glucose', 'danger_signs', 'drugs_given', 'referral_required',
            'referral_destination', 'return_date', 'notes', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'pregnancy_episode_id' => 'pregnancy_episode_id'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'pregnancy_episode_id' => ['nullable', 'uuid'],
            'measurement_date' => ['nullable', 'date'],
            'ga_weeks' => ['nullable', 'integer', 'between:0,45'],
            'ga_days' => ['nullable', 'integer', 'between:0,6'],
            'fetal_heart_rate' => ['nullable', 'integer', 'between:60,220'],
            'presentation' => ['nullable', 'string', 'max:50'],
            'edema' => ['nullable', 'string', 'max:50'],
            'urine_protein' => ['nullable', 'string', 'max:50'],
            'urine_glucose' => ['nullable', 'string', 'max:50'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::enum(DangerSign::class)],
            'drugs_given' => ['nullable', 'array'],
            'systolic_bp' => ['nullable', 'integer', 'between:50,260'],
            'diastolic_bp' => ['nullable', 'integer', 'between:30,160'],
            'weight' => ['nullable', 'numeric', 'between:20,250'],
            'fundal_height' => ['nullable', 'numeric', 'between:5,50'],
            'referral_required' => ['nullable', 'boolean'],
            'referral_destination' => ['nullable', 'string', 'max:255'],
            'return_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function encounterType(): EncounterType
    {
        return EncounterType::ANTENATAL;
    }

    protected function record(Encounter $encounter, array $data): Model
    {
        return app(MaternalVisitAssessmentService::class)->record($encounter, $data);
    }
}
