<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Classes\Services\PostnatalVisitAssessmentService;
use Modules\MCH\Enums\PostnatalContact;
use Modules\MCH\Models\PostnatalVisitAssessment;

/**
 * @group Maternal and child health
 *
 * Postnatal (PNC) checks of the mother or the newborn. The WHO contact is
 * derived from the days since delivery unless given.
 */
class PncVisitController extends MchVisitController
{
    public static function model(): string
    {
        return PostnatalVisitAssessment::class;
    }

    public static function label(): string
    {
        return 'PNC visit';
    }

    public function fields(): array
    {
        return ['id', 'encounter_id', 'patient_id', 'delivery_record_id', 'subject', 'contact', 'days_postpartum', 'temperature',
            'systolic_bp', 'diastolic_bp', 'uterus', 'lochia', 'breastfeeding', 'cord', 'danger_signs', 'family_planning_counselled',
            'family_planning_method', 'referral_required', 'referral_destination', 'notes', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'delivery_record_id' => 'delivery_record_id', 'contact' => 'contact'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'subject' => ['nullable', 'in:mother,baby'],
            'contact' => ['nullable', Rule::enum(PostnatalContact::class)],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'systolic_bp' => ['nullable', 'integer', 'between:50,260'],
            'diastolic_bp' => ['nullable', 'integer', 'between:30,160'],
            'uterus' => ['nullable', 'string', 'max:50'],
            'lochia' => ['nullable', 'string', 'max:50'],
            'breastfeeding' => ['nullable', 'string', 'max:50'],
            'cord' => ['nullable', 'string', 'max:50'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => [Rule::in(PostnatalVisitAssessmentService::DANGER_SIGNS)],
            'family_planning_counselled' => ['nullable', 'boolean'],
            'family_planning_method' => ['nullable', 'string', 'max:100'],
            'referral_required' => ['nullable', 'boolean'],
            'referral_destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function encounterType(): EncounterType
    {
        return EncounterType::POSTNATAL;
    }

    protected function record(Encounter $encounter, array $data): Model
    {
        return app(PostnatalVisitAssessmentService::class)->record($encounter, $data);
    }
}
