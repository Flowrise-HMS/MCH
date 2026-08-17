<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum FeedingMethod: string implements HasColor, HasDescription, HasLabel
{
    case EXCLUSIVE_BREASTFEEDING = 'exclusive_breastfeeding';
    case MIXED = 'mixed';
    case COMPLEMENTARY = 'complementary';
    case OTHER = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::EXCLUSIVE_BREASTFEEDING => 'Exclusive breastfeeding',
            self::MIXED => 'Mixed feeding',
            self::COMPLEMENTARY => 'Complementary feeding',
            self::OTHER => 'Other',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::EXCLUSIVE_BREASTFEEDING => 'Breast milk only.',
            self::MIXED => 'Breast milk plus other milk or formula.',
            self::COMPLEMENTARY => 'Breast milk plus complementary foods.',
            self::OTHER => 'Feeding method not listed.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EXCLUSIVE_BREASTFEEDING => 'success',
            self::MIXED => 'warning',
            self::COMPLEMENTARY => 'info',
            self::OTHER => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
