<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Resources\FieldListTransformer;
use Modules\MCH\Classes\Services\DeliveryRecordService;
use Modules\MCH\Enums\BirthOutcome;
use Modules\MCH\Enums\DeliveryMode;
use Modules\MCH\Enums\DeliveryPlace;
use Modules\MCH\Models\DeliveryRecord;
use Modules\MCH\Models\Newborn;
use Modules\MCH\Models\PregnancyEpisode;

/**
 * @group Maternal and child health
 *
 * Deliveries. Recording one closes the pregnancy and registers each live-born
 * baby as a patient with a CWC record, birth measurements and birth EPI doses.
 */
class DeliveryRecordController extends MchApiController
{
    protected array $with = ['newborns'];

    protected string $defaultSort = '-delivered_at';

    public function __construct(protected DeliveryRecordService $deliveries) {}

    public static function model(): string
    {
        return DeliveryRecord::class;
    }

    public static function label(): string
    {
        return 'delivery';
    }

    public function fields(): array
    {
        return ['id', 'pregnancy_episode_id', 'patient_id', 'encounter_id', 'delivered_at', 'place', 'mode', 'attendant_type',
            'gestation_weeks', 'number_of_babies', 'blood_loss_ml', 'complications', 'maternal_outcome', 'notes', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'mode' => 'mode', 'place' => 'place'];
    }

    public function sortable(): array
    {
        return ['delivered_at', 'created_at'];
    }

    public function storeRules(): array
    {
        return [
            'pregnancy_episode_id' => ['required', 'uuid'],
            'delivered_at' => ['required', 'date', 'before_or_equal:now'],
            'place' => ['required', Rule::enum(DeliveryPlace::class)],
            'mode' => ['required', Rule::enum(DeliveryMode::class)],
            'attendant_type' => ['nullable', 'string', 'max:50'],
            'gestation_weeks' => ['nullable', 'integer', 'between:20,45'],
            'blood_loss_ml' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'complications' => ['nullable', 'array'],
            'maternal_outcome' => ['nullable', 'in:alive,referred,died'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'babies' => ['required', 'array', 'min:1', 'max:6'],
            'babies.*.sex' => ['required', 'in:male,female'],
            'babies.*.outcome' => ['required', Rule::enum(BirthOutcome::class)],
            'babies.*.birth_weight_g' => ['nullable', 'integer', 'between:300,7000'],
            'babies.*.length_cm' => ['nullable', 'numeric', 'between:25,65'],
            'babies.*.head_circumference_cm' => ['nullable', 'numeric', 'between:20,45'],
            'babies.*.apgar_1' => ['nullable', 'integer', 'between:0,10'],
            'babies.*.apgar_5' => ['nullable', 'integer', 'between:0,10'],
            'babies.*.resuscitated' => ['nullable', 'boolean'],
        ];
    }

    protected function createRecord(array $data): Model
    {
        $episode = PregnancyEpisode::query()->findOrFail($data['pregnancy_episode_id']);
        $this->consentedPatient($episode->patient_id);

        return $this->deliveries->record($episode, $data, auth()->id());
    }

    protected function present(Model $record, Request $request): array
    {
        return [
            ...parent::present($record, $request),
            'babies' => $record->newborns->map(fn (Newborn $baby): array => FieldListTransformer::using($baby, [
                'birth_order', 'sex', 'outcome', 'birth_weight_g', 'length_cm', 'head_circumference_cm', 'apgar_1', 'apgar_5',
                'resuscitated', 'child_patient_id',
            ])->toArray($request))->values()->all(),
        ];
    }
}
