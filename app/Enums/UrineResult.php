<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UrineResult: string implements HasColor, HasDescription, HasLabel
{
    case NEGATIVE = 'negative';
    case TRACE = 'trace';
    case POSITIVE_1 = '+';
    case POSITIVE_2 = '++';
    case POSITIVE_3 = '+++';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::NEGATIVE => 'Negative',
            self::TRACE => 'Trace',
            self::POSITIVE_1 => '+',
            self::POSITIVE_2 => '++',
            self::POSITIVE_3 => '+++',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::NEGATIVE => 'Dipstick negative.',
            self::TRACE => 'Trace protein or glucose.',
            self::POSITIVE_1 => 'One-plus dipstick.',
            self::POSITIVE_2 => 'Two-plus dipstick.',
            self::POSITIVE_3 => 'Three-plus dipstick.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEGATIVE => 'success',
            self::TRACE => 'gray',
            self::POSITIVE_1 => 'warning',
            self::POSITIVE_2 => 'danger',
            self::POSITIVE_3 => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
