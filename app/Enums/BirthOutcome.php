<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum BirthOutcome: string implements HasColor, HasDescription, HasLabel
{
    case LIVE_BIRTH = 'live_birth';
    case FRESH_STILLBIRTH = 'fresh_stillbirth';
    case MACERATED_STILLBIRTH = 'macerated_stillbirth';

    public function getLabel(): string
    {
        return match ($this) {
            self::LIVE_BIRTH => __('Live birth'),
            self::FRESH_STILLBIRTH => __('Fresh stillbirth'),
            self::MACERATED_STILLBIRTH => __('Macerated stillbirth'),
        };
    }

    public function getColor(): string
    {
        return $this === self::LIVE_BIRTH ? 'success' : 'danger';
    }

    public function isLive(): bool
    {
        return $this === self::LIVE_BIRTH;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::LIVE_BIRTH => __('Born alive; registered as a patient.'),
            self::FRESH_STILLBIRTH => __('Intrapartum death (skin intact).'),
            self::MACERATED_STILLBIRTH => __('Antepartum death (macerated).'),
        };
    }
}
