<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DevelopmentalScreen: string implements HasColor, HasDescription, HasLabel
{
    case NORMAL = 'normal';
    case CONCERN = 'concern';
    case REFERRED = 'referred';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::CONCERN => 'Concern',
            self::REFERRED => 'Referred',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::NORMAL => 'Age-appropriate development.',
            self::CONCERN => 'Developmental concern noted.',
            self::REFERRED => 'Referred for developmental assessment.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::CONCERN => 'warning',
            self::REFERRED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
