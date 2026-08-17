<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PregnancyRiskFactor: string implements HasColor, HasDescription, HasLabel
{
    case MULTIPLE_GESTATION = 'multiple_gestation';
    case PREVIOUS_C_SECTION = 'previous_c_section';
    case HYPERTENSION = 'hypertension';
    case DIABETES = 'diabetes';
    case SICKLE_CELL = 'sickle_cell';
    case HIV_POSITIVE = 'hiv_positive';
    case ANAEMIA = 'anaemia';
    case HAEMORRHAGE_HISTORY = 'haemorrhage_history';
    case AGE_UNDER_18 = 'age_under_18';
    case AGE_OVER_35 = 'age_over_35';
    case OTHER = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MULTIPLE_GESTATION => 'Multiple gestation',
            self::PREVIOUS_C_SECTION => 'Previous C-section',
            self::HYPERTENSION => 'Hypertension',
            self::DIABETES => 'Diabetes',
            self::SICKLE_CELL => 'Sickle cell disease',
            self::HIV_POSITIVE => 'HIV positive',
            self::ANAEMIA => 'Anaemia',
            self::HAEMORRHAGE_HISTORY => 'Haemorrhage history',
            self::AGE_UNDER_18 => 'Age under 18',
            self::AGE_OVER_35 => 'Age over 35',
            self::OTHER => 'Other',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::MULTIPLE_GESTATION => 'Twin or higher-order pregnancy.',
            self::PREVIOUS_C_SECTION => 'Prior caesarean delivery.',
            self::HYPERTENSION => 'Chronic or pregnancy-induced hypertension.',
            self::DIABETES => 'Pre-existing or gestational diabetes.',
            self::SICKLE_CELL => 'Sickle cell disease or trait requiring extra surveillance.',
            self::HIV_POSITIVE => 'Known HIV infection.',
            self::ANAEMIA => 'Clinically significant anaemia.',
            self::HAEMORRHAGE_HISTORY => 'Previous obstetric haemorrhage.',
            self::AGE_UNDER_18 => 'Maternal age below 18 years.',
            self::AGE_OVER_35 => 'Maternal age above 35 years.',
            self::OTHER => 'Clinician-specified factor that does not auto-elevate risk.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OTHER => 'gray',
            default => 'danger',
        };
    }

    public function contributesToHighRisk(): bool
    {
        return $this !== self::OTHER;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
