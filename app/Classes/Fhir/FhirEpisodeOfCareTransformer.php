<?php

namespace Modules\MCH\Classes\Fhir;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\FHIR\Contracts\FhirResourceContract;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Models\PregnancyEpisode;

class FhirEpisodeOfCareTransformer implements FhirResourceContract
{
    private const STATUS_MAP = [
        PregnancyOutcome::ACTIVE->value => 'active',
        PregnancyOutcome::DELIVERED->value => 'finished',
        PregnancyOutcome::REFERRED_OUT->value => 'onhold',
        PregnancyOutcome::LOST_TO_FOLLOW_UP->value => 'onhold',
        PregnancyOutcome::STILLBIRTH->value => 'finished',
        PregnancyOutcome::MATERNAL_DEATH->value => 'finished',
    ];

    public function resourceType(): string
    {
        return 'EpisodeOfCare';
    }

    public function toFhir(Model $model): array
    {
        /** @var PregnancyEpisode $episode */
        $episode = $model;

        return [
            'resourceType' => 'EpisodeOfCare',
            'id' => $episode->id,
            'status' => self::STATUS_MAP[$episode->outcome->value] ?? 'active',
            'patient' => ['reference' => 'Patient/'.$episode->patient_id],
            'period' => [
                'start' => $episode->booking_date?->toIso8601String() ?? $episode->created_at->toIso8601String(),
            ],
            'diagnosis' => [
                ['condition' => ['display' => 'Pregnancy'],
                    'role' => ['coding' => [['code' => 'CC']]]],
            ],
        ];
    }

    public function fromFhir(array $fhirResource): array
    {
        return [
            'patient_id' => isset($fhirResource['patient']['reference'])
                ? Str::afterLast($fhirResource['patient']['reference'], '/')
                : null,
        ];
    }

    public function findById(string $id): ?Model
    {
        return PregnancyEpisode::find($id);
    }

    public function query(): Builder
    {
        return PregnancyEpisode::query();
    }

    public function searchableParameters(): array
    {
        return ['patient', '_id'];
    }

    public function validateBusinessRules(array $fhirResource): array
    {
        $errors = [];

        if (($fhirResource['status'] ?? null) === null) {
            $errors[] = 'EpisodeOfCare.status is required';
        }

        return $errors;
    }
}
