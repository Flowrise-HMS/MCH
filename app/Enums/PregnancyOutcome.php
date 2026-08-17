<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PregnancyOutcome: string implements HasColor, HasDescription, HasLabel
{
    case ACTIVE = 'active';
    case DELIVERED = 'delivered';
    case REFERRED_OUT = 'referred_out';
    case LOST_TO_FOLLOW_UP = 'lost_to_follow_up';
    case STILLBIRTH = 'stillbirth';
    case MATERNAL_DEATH = 'maternal_death';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DELIVERED => 'Delivered',
            self::REFERRED_OUT => 'Referred out',
            self::LOST_TO_FOLLOW_UP => 'Lost to follow-up',
            self::STILLBIRTH => 'Stillbirth',
            self::MATERNAL_DEATH => 'Maternal death',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => 'Pregnancy is ongoing at this facility.',
            self::DELIVERED => 'Pregnancy ended in a live birth.',
            self::REFERRED_OUT => 'Mother referred to another facility.',
            self::LOST_TO_FOLLOW_UP => 'No further contact after missed returns.',
            self::STILLBIRTH => 'Pregnancy ended in stillbirth.',
            self::MATERNAL_DEATH => 'Maternal death recorded for this episode.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'info',
            self::DELIVERED => 'success',
            self::REFERRED_OUT => 'warning',
            self::LOST_TO_FOLLOW_UP => 'gray',
            self::STILLBIRTH => 'danger',
            self::MATERNAL_DEATH => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
