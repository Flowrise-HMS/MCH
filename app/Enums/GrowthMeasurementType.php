<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum GrowthMeasurementType: string implements HasColor, HasDescription, HasLabel
{
    case WEIGHT = 'weight';
    case LENGTH_HEIGHT = 'length_height';
    case MUAC = 'muac';
    case HEAD_CIRCUMFERENCE = 'head_circumference';
    case FUNDAL_HEIGHT = 'fundal_height';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::WEIGHT => 'Weight',
            self::LENGTH_HEIGHT => 'Length / height',
            self::MUAC => 'MUAC',
            self::HEAD_CIRCUMFERENCE => 'Head circumference',
            self::FUNDAL_HEIGHT => 'Fundal height',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::WEIGHT => 'Child or maternal body weight.',
            self::LENGTH_HEIGHT => 'Recumbent length or standing height.',
            self::MUAC => 'Mid-upper arm circumference.',
            self::HEAD_CIRCUMFERENCE => 'Occipitofrontal head circumference.',
            self::FUNDAL_HEIGHT => 'Obstetric fundal height — excluded from WHO child Z-score math.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::WEIGHT => 'primary',
            self::LENGTH_HEIGHT => 'info',
            self::MUAC => 'warning',
            self::HEAD_CIRCUMFERENCE => 'success',
            self::FUNDAL_HEIGHT => 'violet',
        };
    }

    public function isChildAnthropometry(): bool
    {
        return $this !== self::FUNDAL_HEIGHT;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
