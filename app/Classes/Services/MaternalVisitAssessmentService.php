<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Clinical\Classes\Services\VitalSignService;
use Modules\Clinical\Enums\EncounterType;
use Modules\Clinical\Models\Encounter;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\PregnancyEpisode;

class MaternalVisitAssessmentService
{
    /**
     * Keys that are written through Clinical's VitalSign store rather than the assessment.
     *
     * @var list<string>
     */
    private const VITAL_KEYS = ['systolic_bp', 'diastolic_bp', 'weight'];

    public function __construct(private VitalSignService $vitalSignService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Encounter $encounter, array $data): MaternalVisitAssessment
    {
        if ($encounter->type !== EncounterType::ANTENATAL) {
            throw new \InvalidArgumentException('Maternal visit assessment requires an ANTENATAL encounter.');
        }

        $dangerSigns = array_map(
            fn ($sign) => $sign instanceof DangerSign ? $sign->value : $sign,
            $data['danger_signs'] ?? [],
        );

        if (isset($data['patient_id']) && $data['patient_id'] !== $encounter->patient_id) {
            throw new \InvalidArgumentException('patient_id must match the encounter patient.');
        }

        $visitDate = Carbon::parse($data['measurement_date'] ?? now()->toDateString());
        $episode = $this->resolveEpisode($data['pregnancy_episode_id'] ?? null);
        $gestationalAge = $this->resolveGestationalAge($data, $episode, $visitDate);

        return DB::transaction(function () use ($encounter, $data, $dangerSigns, $episode, $gestationalAge, $visitDate): MaternalVisitAssessment {
            $assessment = MaternalVisitAssessment::create([
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'branch_id' => $encounter->branch_id,
                'pregnancy_episode_id' => $episode?->id,
                'visit_number' => $this->resolveVisitNumber($data, $episode),
                'ga_weeks' => $gestationalAge['weeks'],
                'ga_days' => $gestationalAge['days'],
                'fetal_heart_rate' => $data['fetal_heart_rate'] ?? null,
                'presentation' => $data['presentation'] ?? null,
                'edema' => $data['edema'] ?? null,
                'urine_protein' => $data['urine_protein'] ?? null,
                'urine_glucose' => $data['urine_glucose'] ?? null,
                'danger_signs' => $dangerSigns,
                'drugs_given' => $data['drugs_given'] ?? [],
                'referral_required' => (bool) ($data['referral_required'] ?? false) || count($dangerSigns) > 0,
                'referral_destination' => $data['referral_destination'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null,
            ]);

            if (isset($data['fundal_height'])) {
                GrowthMeasurement::create([
                    'patient_id' => $assessment->patient_id,
                    'encounter_id' => $encounter->id,
                    'branch_id' => $encounter->branch_id,
                    'type' => GrowthMeasurementType::FUNDAL_HEIGHT,
                    'value' => $data['fundal_height'],
                    'unit' => $data['fundal_height_unit'] ?? 'cm',
                    'date' => $visitDate->toDateString(),
                    'recorded_by' => $data['recorded_by'] ?? null,
                ]);
            }

            $this->recordVitals($encounter, $data);

            return $assessment;
        });
    }

    private function resolveEpisode(?string $episodeId): ?PregnancyEpisode
    {
        if ($episodeId === null || $episodeId === '') {
            return null;
        }

        return PregnancyEpisode::query()->find($episodeId);
    }

    /**
     * Derive GA from the episode LMP when the caller did not supply it.
     *
     * @param  array<string, mixed>  $data
     * @return array{weeks: ?int, days: ?int}
     */
    private function resolveGestationalAge(array $data, ?PregnancyEpisode $episode, Carbon $visitDate): array
    {
        if (filled($data['ga_weeks'] ?? null)) {
            return [
                'weeks' => (int) $data['ga_weeks'],
                'days' => filled($data['ga_days'] ?? null) ? (int) $data['ga_days'] : null,
            ];
        }

        $derived = $episode?->gestationalAgeAt($visitDate);

        return [
            'weeks' => $derived['weeks'] ?? null,
            'days' => $derived['days'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveVisitNumber(array $data, ?PregnancyEpisode $episode): ?int
    {
        if (filled($data['visit_number'] ?? null)) {
            return (int) $data['visit_number'];
        }

        if ($episode === null) {
            return null;
        }

        return MaternalVisitAssessment::query()
            ->where('pregnancy_episode_id', $episode->id)
            ->count() + 1;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function recordVitals(Encounter $encounter, array $data): void
    {
        $vitals = array_filter(
            array_intersect_key($data, array_flip(self::VITAL_KEYS)),
            fn ($value): bool => $value !== null && $value !== '',
        );

        if ($vitals === []) {
            return;
        }

        $encounter->loadMissing('patient');

        if ($encounter->patient === null) {
            return;
        }

        $this->vitalSignService->record(
            $encounter->patient,
            $vitals,
            $encounter->id,
            recordedBy: $data['recorded_by'] ?? null,
        );
    }
}
