<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum DeliveryPlace: string implements HasColor, HasDescription, HasLabel
{
    case FACILITY = 'facility';
    case OTHER_FACILITY = 'other_facility';
    case HOME = 'home';
    case IN_TRANSIT = 'in_transit';

    public function getLabel(): string
    {
        return match ($this) {
            self::FACILITY => __('This facility'),
            self::OTHER_FACILITY => __('Another facility'),
            self::HOME => __('Home'),
            self::IN_TRANSIT => __('Before arrival / in transit'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::FACILITY => __('Delivered at this health facility.'),
            self::OTHER_FACILITY => __('Delivered at another health facility.'),
            self::HOME => __('Delivered at home.'),
            self::IN_TRANSIT => __('Born before arrival or on the way to a facility.'),
        };
    }

    public function getColor(): string
    {
        return 'info';
    }
}
