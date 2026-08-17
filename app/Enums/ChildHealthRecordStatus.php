<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ChildHealthRecordStatus: string implements HasColor, HasDescription, HasLabel
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Child is enrolled in CWC at this facility.',
            self::ARCHIVED => 'CWC record closed or transferred.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::ARCHIVED => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
