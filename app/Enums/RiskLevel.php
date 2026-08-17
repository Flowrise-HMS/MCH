<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum RiskLevel: string implements HasColor, HasDescription, HasLabel
{
    case LOW = 'low';
    case HIGH = 'high';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::LOW => 'Low',
            self::HIGH => 'High',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::LOW => 'No high-risk factors recorded, or override kept the episode low.',
            self::HIGH => 'One or more high-risk factors, or a clinician override to high.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LOW => 'success',
            self::HIGH => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
