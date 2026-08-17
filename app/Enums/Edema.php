<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum Edema: string implements HasColor, HasDescription, HasLabel
{
    case NONE = 'none';
    case ANKLES = 'ankles';
    case LEGS = 'legs';
    case GENERALIZED = 'generalized';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::NONE => 'None',
            self::ANKLES => 'Ankles',
            self::LEGS => 'Legs',
            self::GENERALIZED => 'Generalized',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::NONE => 'No oedema noted.',
            self::ANKLES => 'Ankle oedema.',
            self::LEGS => 'Leg oedema.',
            self::GENERALIZED => 'Generalized oedema — treat as a danger sign.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NONE => 'success',
            self::ANKLES => 'warning',
            self::LEGS => 'warning',
            self::GENERALIZED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
