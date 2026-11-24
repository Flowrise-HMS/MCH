<?php

namespace Modules\MCH\Classes\Services;

use Illuminate\Support\Carbon;
use Modules\MCH\Classes\Support\WhoGrowthReference;
use Modules\MCH\Enums\GrowthIndicator;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\Patient\Enums\Gender;
use Modules\Patient\Models\Patient;

/**
 * WHO Child Growth Standards Z-scores (0–5 years) using the LMS method:
 * z = ((y/M)^L − 1) / (L·S), with WHO's restricted adjustment beyond ±3 SD
 * for skewed indicators (weight, weight-for-length/height, MUAC).
 *
 * Each child measurement gets its own age-based Z-score (WAZ, HAZ, HCZ, ACZ);
 * a weight also gets WHZ when a length/height was recorded the same day
 * (weight-for-length below 2 years, weight-for-height from 2 years).
 */
class GrowthZScoreService
{
    public const MAX_AGE_DAYS = 1856;

    private const LENGTH_TO_HEIGHT_DAYS = 731;

    public function zScore(GrowthIndicator $indicator, int $sex, float $index, float $value): ?float
    {
        if ($value <= 0 || ! in_array($sex, [1, 2], true)) {
            return null;
        }

        $lms = WhoGrowthReference::lms($indicator, $sex, $index);

        if ($lms === null) {
            return null;
        }

        [$l, $m, $s] = $lms;
        $z = abs($l) < 1e-9
            ? log($value / $m) / $s
            : (pow($value / $m, $l) - 1) / ($l * $s);

        if ($indicator->usesRestrictedZ() && abs($z) > 3) {
            $sdAt = fn (float $sd): float => abs($l) < 1e-9 ? $m * exp($s * $sd) : $m * pow(1 + $l * $s * $sd, 1 / $l);

            $z = $z > 3
                ? 3 + ($value - $sdAt(3)) / ($sdAt(3) - $sdAt(2))
                : -3 + ($value - $sdAt(-3)) / ($sdAt(-2) - $sdAt(-3));
        }

        return round($z, 2);
    }

    /**
     * Fill the measurement's own Z-score and indicator (not saved).
     */
    public function apply(GrowthMeasurement $measurement): void
    {
        $measurement->z_score = null;
        $measurement->z_indicator = null;

        $type = $measurement->type instanceof GrowthMeasurementType ? $measurement->type : enum_try_from(GrowthMeasurementType::class, (string) $measurement->type);
        $indicator = $type !== null ? GrowthIndicator::forMeasurement($type) : null;
        [$sex, $ageDays] = $this->childContext($measurement);

        if ($indicator === null || $sex === null || $ageDays === null) {
            return;
        }

        $value = $this->normalisedValue($type, (float) $measurement->value, (string) $measurement->unit);
        $z = $value !== null ? $this->zScore($indicator, $sex, $ageDays, $value) : null;

        if ($z !== null) {
            $measurement->z_score = $z;
            $measurement->z_indicator = $indicator->value;
        }
    }

    /**
     * Recompute WHZ on the weight(s) recorded the same day as $measurement.
     */
    public function syncWeightForLength(GrowthMeasurement $measurement): void
    {
        $sameDay = GrowthMeasurement::query()
            ->withoutGlobalScope('branch')
            ->where('patient_id', $measurement->patient_id)
            ->whereDate('date', $measurement->date)
            ->whereIn('type', [GrowthMeasurementType::WEIGHT->value, GrowthMeasurementType::LENGTH_HEIGHT->value])
            ->get();

        $length = $sameDay->first(fn (GrowthMeasurement $m): bool => $m->type === GrowthMeasurementType::LENGTH_HEIGHT);
        [$sex, $ageDays] = $this->childContext($measurement);

        foreach ($sameDay->filter(fn (GrowthMeasurement $m): bool => $m->type === GrowthMeasurementType::WEIGHT) as $weight) {
            $whz = null;

            if ($length !== null && $sex !== null && $ageDays !== null) {
                $cm = $this->normalisedValue(GrowthMeasurementType::LENGTH_HEIGHT, (float) $length->value, (string) $length->unit);
                $kg = $this->normalisedValue(GrowthMeasurementType::WEIGHT, (float) $weight->value, (string) $weight->unit);
                $indicator = $ageDays < self::LENGTH_TO_HEIGHT_DAYS ? GrowthIndicator::WEIGHT_FOR_LENGTH : GrowthIndicator::WEIGHT_FOR_HEIGHT;
                $whz = $cm !== null && $kg !== null ? $this->zScore($indicator, $sex, $cm, $kg) : null;
            }

            if ((string) $weight->whz !== (string) $whz) {
                $weight->whz = $whz;
                $weight->saveQuietly();
            }
        }
    }

    /**
     * @return array{label: string, color: string}|null
     */
    public static function classify(GrowthIndicator|string|null $indicator, ?float $z): ?array
    {
        $indicator = is_string($indicator) ? enum_try_from(GrowthIndicator::class, $indicator) : $indicator;

        if ($indicator === null || $z === null) {
            return null;
        }

        return match ($indicator) {
            GrowthIndicator::WEIGHT_FOR_AGE => match (true) {
                $z < -3 => ['label' => __('Severely underweight'), 'color' => 'danger'],
                $z < -2 => ['label' => __('Underweight'), 'color' => 'warning'],
                $z > 2 => ['label' => __('High weight-for-age'), 'color' => 'warning'],
                default => ['label' => __('Normal'), 'color' => 'success'],
            },
            GrowthIndicator::LENGTH_FOR_AGE => match (true) {
                $z < -3 => ['label' => __('Severely stunted'), 'color' => 'danger'],
                $z < -2 => ['label' => __('Stunted'), 'color' => 'warning'],
                $z > 3 => ['label' => __('Very tall'), 'color' => 'info'],
                default => ['label' => __('Normal'), 'color' => 'success'],
            },
            GrowthIndicator::WEIGHT_FOR_LENGTH, GrowthIndicator::WEIGHT_FOR_HEIGHT => match (true) {
                $z < -3 => ['label' => __('Severely wasted'), 'color' => 'danger'],
                $z < -2 => ['label' => __('Wasted'), 'color' => 'warning'],
                $z > 3 => ['label' => __('Obese'), 'color' => 'danger'],
                $z > 2 => ['label' => __('Overweight'), 'color' => 'warning'],
                $z > 1 => ['label' => __('Possible risk of overweight'), 'color' => 'info'],
                default => ['label' => __('Normal'), 'color' => 'success'],
            },
            GrowthIndicator::HEAD_CIRCUMFERENCE_FOR_AGE => match (true) {
                $z < -2 => ['label' => __('Small head (microcephaly risk)'), 'color' => 'warning'],
                $z > 2 => ['label' => __('Large head (macrocephaly risk)'), 'color' => 'warning'],
                default => ['label' => __('Normal'), 'color' => 'success'],
            },
            GrowthIndicator::MUAC_FOR_AGE => match (true) {
                $z < -3 => ['label' => __('Severe acute malnutrition'), 'color' => 'danger'],
                $z < -2 => ['label' => __('Moderate acute malnutrition'), 'color' => 'warning'],
                default => ['label' => __('Normal'), 'color' => 'success'],
            },
        };
    }

    /**
     * Child's sex code (1/2) and age in days at the measurement date, or nulls
     * when the patient is unknown, has no sex/DOB, or is outside 0–5 years.
     *
     * @return array{0: int|null, 1: int|null}
     */
    public function childContext(GrowthMeasurement $measurement): array
    {
        $patient = $measurement->relationLoaded('patient') && $measurement->patient !== null
            ? $measurement->patient
            : Patient::query()->withoutGlobalScopes()->find($measurement->patient_id, ['id', 'gender', 'date_of_birth']);

        if ($patient === null || $patient->date_of_birth === null || $measurement->date === null) {
            return [null, null];
        }

        $sex = match ($patient->gender) {
            Gender::MALE => 1,
            Gender::FEMALE => 2,
            default => null,
        };

        $ageDays = (int) Carbon::parse($patient->date_of_birth)->startOfDay()->diffInDays(Carbon::parse($measurement->date)->startOfDay(), false);

        if ($ageDays < 0 || $ageDays > self::MAX_AGE_DAYS) {
            return [$sex, null];
        }

        return [$sex, $ageDays];
    }

    /**
     * Value in kg (weight) or cm (lengths, circumferences); null for unknown units.
     */
    public function normalisedValue(GrowthMeasurementType $type, float $value, string $unit): ?float
    {
        $unit = strtolower(trim($unit));

        if ($type === GrowthMeasurementType::WEIGHT) {
            return match ($unit) {
                'kg', '' => $value,
                'g' => $value / 1000,
                'lb', 'lbs' => $value * 0.45359237,
                default => null,
            };
        }

        return match ($unit) {
            'cm', '' => $value,
            'mm' => $value / 10,
            'm' => $value * 100,
            'in' => $value * 2.54,
            default => null,
        };
    }
}
