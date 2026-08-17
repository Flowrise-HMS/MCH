<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DangerSign: string implements HasColor, HasDescription, HasLabel
{
    case BLEEDING = 'bleeding';
    case FEVER = 'fever';
    case HEADACHE_OR_VISUAL = 'headache_or_visual';
    case REDUCED_FETAL_MOVEMENT = 'reduced_fetal_movement';
    case SWELLING = 'swelling';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::BLEEDING => 'Bleeding',
            self::FEVER => 'Fever',
            self::HEADACHE_OR_VISUAL => 'Headache or visual disturbance',
            self::REDUCED_FETAL_MOVEMENT => 'Reduced fetal movement',
            self::SWELLING => 'Swelling',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::BLEEDING => 'Vaginal bleeding during pregnancy.',
            self::FEVER => 'Fever suggesting infection.',
            self::HEADACHE_OR_VISUAL => 'Severe headache or visual changes (pre-eclampsia screen).',
            self::REDUCED_FETAL_MOVEMENT => 'Mother reports reduced fetal movement.',
            self::SWELLING => 'Sudden or generalized swelling.',
        };
    }

    public function getColor(): string|array|null
    {
        return 'danger';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
