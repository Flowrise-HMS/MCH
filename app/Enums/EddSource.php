<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EddSource: string implements HasColor, HasDescription, HasLabel
{
    case LMP = 'lmp';
    case ULTRASOUND = 'ultrasound';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::LMP => 'LMP',
            self::ULTRASOUND => 'Ultrasound',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::LMP => 'Estimated delivery date derived from last menstrual period.',
            self::ULTRASOUND => 'Estimated delivery date taken from ultrasound dating.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LMP => 'gray',
            self::ULTRASOUND => 'info',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
