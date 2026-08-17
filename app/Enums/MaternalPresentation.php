<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum MaternalPresentation: string implements HasColor, HasDescription, HasLabel
{
    case CEPHALIC = 'cephalic';
    case BREECH = 'breech';
    case TRANSVERSE = 'transverse';
    case OBLIQUE = 'oblique';
    case OTHER = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CEPHALIC => 'Cephalic',
            self::BREECH => 'Breech',
            self::TRANSVERSE => 'Transverse',
            self::OBLIQUE => 'Oblique',
            self::OTHER => 'Other',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::CEPHALIC => 'Head-down presentation.',
            self::BREECH => 'Breech presentation.',
            self::TRANSVERSE => 'Transverse lie.',
            self::OBLIQUE => 'Oblique lie.',
            self::OTHER => 'Presentation not listed.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CEPHALIC => 'success',
            self::BREECH => 'warning',
            self::TRANSVERSE => 'danger',
            self::OBLIQUE => 'warning',
            self::OTHER => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
