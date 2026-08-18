<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ImmunizationStatus: string implements HasColor, HasDescription, HasLabel
{
    case SCHEDULED = 'scheduled';
    case ADMINISTERED = 'administered';
    case DECLINED = 'declined';
    case CONTRAINDICATED = 'contraindicated';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::ADMINISTERED => 'Administered',
            self::DECLINED => 'Declined',
            self::CONTRAINDICATED => 'Contraindicated',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::SCHEDULED => 'Due but not yet given.',
            self::ADMINISTERED => 'Successfully administered.',
            self::DECLINED => 'Refused by patient or guardian.',
            self::CONTRAINDICATED => 'Medically contraindicated.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SCHEDULED => 'info',
            self::ADMINISTERED => 'success',
            self::DECLINED => 'warning',
            self::CONTRAINDICATED => 'danger',
        };
    }
}
