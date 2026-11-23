<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * WHO Child Growth Standards indicators computed for children 0–5 years.
 */
enum GrowthIndicator: string implements HasColor, HasDescription, HasLabel
{
    case WEIGHT_FOR_AGE = 'wfa';
    case LENGTH_FOR_AGE = 'lhfa';
    case HEAD_CIRCUMFERENCE_FOR_AGE = 'hcfa';
    case MUAC_FOR_AGE = 'acfa';
    case WEIGHT_FOR_LENGTH = 'wfl';
    case WEIGHT_FOR_HEIGHT = 'wfh';

    public function getLabel(): string
    {
        return match ($this) {
            self::WEIGHT_FOR_AGE => __('Weight-for-age'),
            self::LENGTH_FOR_AGE => __('Length/height-for-age'),
            self::HEAD_CIRCUMFERENCE_FOR_AGE => __('Head circumference-for-age'),
            self::MUAC_FOR_AGE => __('MUAC-for-age'),
            self::WEIGHT_FOR_LENGTH => __('Weight-for-length'),
            self::WEIGHT_FOR_HEIGHT => __('Weight-for-height'),
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::WEIGHT_FOR_AGE => 'WAZ',
            self::LENGTH_FOR_AGE => 'HAZ',
            self::HEAD_CIRCUMFERENCE_FOR_AGE => 'HCZ',
            self::MUAC_FOR_AGE => 'ACZ',
            self::WEIGHT_FOR_LENGTH, self::WEIGHT_FOR_HEIGHT => 'WHZ',
        };
    }

    /** Reference file in Modules/MCH/database/data/who. */
    public function file(): string
    {
        return match ($this) {
            self::WEIGHT_FOR_AGE => 'weianthro.txt',
            self::LENGTH_FOR_AGE => 'lenanthro.txt',
            self::HEAD_CIRCUMFERENCE_FOR_AGE => 'hcanthro.txt',
            self::MUAC_FOR_AGE => 'acanthro.txt',
            self::WEIGHT_FOR_LENGTH => 'wflanthro.txt',
            self::WEIGHT_FOR_HEIGHT => 'wfhanthro.txt',
        };
    }

    /** Indexed by length/height (cm) instead of age (days). */
    public function byLength(): bool
    {
        return $this === self::WEIGHT_FOR_LENGTH || $this === self::WEIGHT_FOR_HEIGHT;
    }

    /**
     * WHO applies the restricted ("adjusted") method beyond ±3 SD to skewed
     * indicators; length and head circumference use the plain LMS formula.
     */
    public function usesRestrictedZ(): bool
    {
        return ! in_array($this, [self::LENGTH_FOR_AGE, self::HEAD_CIRCUMFERENCE_FOR_AGE], true);
    }

    public static function forMeasurement(GrowthMeasurementType $type): ?self
    {
        return match ($type) {
            GrowthMeasurementType::WEIGHT => self::WEIGHT_FOR_AGE,
            GrowthMeasurementType::LENGTH_HEIGHT => self::LENGTH_FOR_AGE,
            GrowthMeasurementType::HEAD_CIRCUMFERENCE => self::HEAD_CIRCUMFERENCE_FOR_AGE,
            GrowthMeasurementType::MUAC => self::MUAC_FOR_AGE,
            GrowthMeasurementType::FUNDAL_HEIGHT => null,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::WEIGHT_FOR_AGE => __('WHO weight-for-age Z-score (WAZ).'),
            self::LENGTH_FOR_AGE => __('WHO length/height-for-age Z-score (HAZ).'),
            self::HEAD_CIRCUMFERENCE_FOR_AGE => __('WHO head circumference-for-age Z-score.'),
            self::MUAC_FOR_AGE => __('WHO MUAC-for-age Z-score (from 3 months).'),
            self::WEIGHT_FOR_LENGTH => __('WHO weight-for-length Z-score (under 2 years).'),
            self::WEIGHT_FOR_HEIGHT => __('WHO weight-for-height Z-score (2 years and over).'),
        };
    }

    public function getColor(): string
    {
        return 'primary';
    }
}
