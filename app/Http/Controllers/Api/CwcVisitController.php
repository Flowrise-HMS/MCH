<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Enums\DevelopmentalScreen;
use Modules\MCH\Enums\FeedingMethod;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\ChildVisitAssessment;

/**
 * @group Maternal and child health
 *
 * Child welfare clinic (CWC) visits with anthropometry (WHO Z-scores are computed).
 */
class CwcVisitController extends MchVisitController
{
    public static function model(): string
    {
        return ChildVisitAssessment::class;
    }

    public static function label(): string
    {
        return 'CWC visit';
    }

    public function fields(): array
    {
        return ['id', 'encounter_id', 'patient_id', 'child_health_record_id', 'feeding', 'vitamin_a_given', 'dewormed',
            'developmental_screen', 'referral_required', 'referral_destination', 'notes', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'child_health_record_id' => 'child_health_record_id'];
    }

    public function storeRules(): array
    {
        return [
            'patient_id' => ['required', 'uuid'],
            'child_health_record_id' => ['nullable', 'uuid'],
            'feeding' => ['nullable', Rule::enum(FeedingMethod::class)],
            'vitamin_a_given' => ['nullable', 'boolean'],
            'dewormed' => ['nullable', 'boolean'],
            'developmental_screen' => ['nullable', Rule::enum(DevelopmentalScreen::class)],
            'referral_required' => ['nullable', 'boolean'],
            'referral_destination' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'measurements' => ['nullable', 'array', 'max:10'],
            'measurements.*.type' => ['required', Rule::enum(GrowthMeasurementType::class)],
            'measurements.*.value' => ['required', 'numeric', 'gt:0'],
            'measurements.*.unit' => ['required', 'string', 'max:8'],
        ];
    }

    protected function encounterType(): EncounterType
    {
        return EncounterType::CHILD_WELFARE;
    }

    protected function record(Encounter $encounter, array $data): Model
    {
        return app(ChildVisitAssessmentService::class)->record($encounter, $data);
    }
}
