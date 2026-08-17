<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum MchRecordStatus: string implements HasColor, HasDescription, HasLabel
{
    case ACTIVE = 'active';
    case LOST = 'lost';
    case DAMAGED = 'damaged';
    case REPLACED = 'replaced';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::LOST => 'Lost',
            self::DAMAGED => 'Damaged',
            self::REPLACED => 'Replaced',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'This is the current client-held book for the owner.',
            self::LOST => 'The physical book was reported lost.',
            self::DAMAGED => 'The physical book was reported damaged.',
            self::REPLACED => 'Superseded by a replacement book; kept for history.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::LOST => 'warning',
            self::DAMAGED => 'danger',
            self::REPLACED => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
