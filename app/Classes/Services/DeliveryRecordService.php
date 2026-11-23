<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\MCH\Enums\BirthOutcome;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\DeliveryRecord;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\Newborn;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Classes\Services\PatientService;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Enums\PatientRelationshipType;
use Modules\Patient\Models\Patient;
use Modules\Patient\Models\PatientRelationship;

/**
 * Records a delivery for an active pregnancy episode, in one transaction:
 *
 * - the delivery record and one Newborn row per baby;
 * - every live-born baby is registered as a patient ("Baby <mother's surname>",
 *   mother's address/phone), gets a child health record linked to the delivery
 *   and pregnancy, a MOTHER relationship, birth weight/length/head
 *   circumference as growth measurements (so WHO Z-scores apply) and the
 *   child EPI doses that are due at birth;
 * - the episode is closed as DELIVERED (any live birth), STILLBIRTH (none), or
 *   MATERNAL_DEATH.
 */
class DeliveryRecordService
{
    public function __construct(
        private PatientService $patients,
        private EpiDueService $epiDueService,
    ) {}

    /**
     * @param  array<string, mixed>  $data  delivery fields plus `babies` (list of newborn fields)
     */
    public function record(PregnancyEpisode $episode, array $data, ?int $recordedBy = null): DeliveryRecord
    {
        if ($episode->outcome !== PregnancyOutcome::ACTIVE) {
            throw new InvalidArgumentException('Delivery can only be recorded for an active pregnancy.');
        }

        $babies = array_values($data['babies'] ?? []);

        if ($babies === []) {
            throw new InvalidArgumentException('Record at least one baby.');
        }

        $deliveredAt = Carbon::parse($data['delivered_at'] ?? now());

        return DB::transaction(function () use ($episode, $data, $babies, $deliveredAt, $recordedBy): DeliveryRecord {
            $mother = Patient::query()->withoutGlobalScopes()->findOrFail($episode->patient_id);

            $delivery = DeliveryRecord::create([
                'pregnancy_episode_id' => $episode->id,
                'patient_id' => $mother->id,
                'branch_id' => $episode->branch_id,
                'encounter_id' => $data['encounter_id'] ?? null,
                'delivered_at' => $deliveredAt,
                'place' => $data['place'],
                'mode' => $data['mode'],
                'attendant_type' => $data['attendant_type'] ?? null,
                'attendant_staff_id' => $data['attendant_staff_id'] ?? null,
                'gestation_weeks' => $data['gestation_weeks'] ?? null,
                'number_of_babies' => count($babies),
                'blood_loss_ml' => $data['blood_loss_ml'] ?? null,
                'complications' => array_values($data['complications'] ?? []),
                'maternal_outcome' => $data['maternal_outcome'] ?? 'alive',
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $recordedBy,
            ]);

            $anyLive = false;

            foreach ($babies as $index => $baby) {
                $outcome = $baby['outcome'] instanceof BirthOutcome ? $baby['outcome'] : BirthOutcome::from((string) $baby['outcome']);
                $sex = $baby['sex'] instanceof Gender ? $baby['sex'] : Gender::from((string) $baby['sex']);

                $newborn = Newborn::create([
                    'delivery_record_id' => $delivery->id,
                    'birth_order' => $index + 1,
                    'sex' => $sex,
                    'outcome' => $outcome,
                    'birth_weight_g' => $baby['birth_weight_g'] ?? null,
                    'length_cm' => $baby['length_cm'] ?? null,
                    'head_circumference_cm' => $baby['head_circumference_cm'] ?? null,
                    'apgar_1' => $baby['apgar_1'] ?? null,
                    'apgar_5' => $baby['apgar_5'] ?? null,
                    'resuscitated' => (bool) ($baby['resuscitated'] ?? false),
                ]);

                if ($outcome->isLive()) {
                    $anyLive = true;
                    $this->registerNewborn($newborn, $delivery, $episode, $mother, $sex, count($babies), $recordedBy);
                }
            }

            $episode->update(['outcome' => match (true) {
                ($data['maternal_outcome'] ?? 'alive') === 'died' => PregnancyOutcome::MATERNAL_DEATH,
                $anyLive => PregnancyOutcome::DELIVERED,
                default => PregnancyOutcome::STILLBIRTH,
            }]);

            return $delivery->load('newborns');
        });
    }

    private function registerNewborn(
        Newborn $newborn,
        DeliveryRecord $delivery,
        PregnancyEpisode $episode,
        Patient $mother,
        Gender $sex,
        int $babyCount,
        ?int $recordedBy,
    ): void {
        $birthDate = $delivery->delivered_at->toDateString();
        $suffix = $babyCount > 1 ? ' '.chr(64 + $newborn->birth_order) : '';

        $child = $this->patients->create(array_filter([
            'branch_id' => $delivery->branch_id,
            'first_name' => 'Baby'.$suffix,
            'last_name' => $mother->last_name,
            'gender' => $sex->value,
            'date_of_birth' => $birthDate,
            'phone' => $mother->phone,
            'address' => $mother->address,
        ], fn ($value): bool => $value !== null && $value !== ''));

        $newborn->update(['child_patient_id' => $child->id]);

        ChildHealthRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $delivery->branch_id,
            'pregnancy_episode_id' => $episode->id,
            'delivery_record_id' => $delivery->id,
            'date_of_birth' => $birthDate,
            'recorded_by' => $recordedBy,
        ]);

        PatientRelationship::query()->firstOrCreate([
            'subject_type' => $child->getMorphClass(),
            'subject_id' => $child->id,
            'object_type' => $mother->getMorphClass(),
            'object_id' => $mother->id,
            'type' => PatientRelationshipType::MOTHER,
        ], ['created_by' => $recordedBy]);

        foreach ([
            [GrowthMeasurementType::WEIGHT, $newborn->birth_weight_g, 'g'],
            [GrowthMeasurementType::LENGTH_HEIGHT, $newborn->length_cm, 'cm'],
            [GrowthMeasurementType::HEAD_CIRCUMFERENCE, $newborn->head_circumference_cm, 'cm'],
        ] as [$type, $value, $unit]) {
            if ($value === null || (float) $value <= 0) {
                continue;
            }

            GrowthMeasurement::create([
                'patient_id' => $child->id,
                'branch_id' => $delivery->branch_id,
                'type' => $type,
                'value' => $value,
                'unit' => $unit,
                'date' => $birthDate,
                'recorded_by' => $recordedBy,
            ]);
        }

        $schedule = ImmunizationSchedule::activeFor(ImmunizationSchedule::TARGET_CHILD);

        if ($schedule !== null) {
            $this->epiDueService->generateDueRecords($child->fresh(), $schedule, $delivery->branch_id);
        }
    }
}
