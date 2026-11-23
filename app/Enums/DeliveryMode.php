<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum DeliveryMode: string implements HasColor, HasDescription, HasLabel
{
    case SPONTANEOUS_VAGINAL = 'svd';
    case VACUUM = 'vacuum';
    case FORCEPS = 'forceps';
    case BREECH = 'breech';
    case CAESAREAN = 'caesarean';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPONTANEOUS_VAGINAL => __('Spontaneous vaginal delivery'),
            self::VACUUM => __('Vacuum-assisted'),
            self::FORCEPS => __('Forceps-assisted'),
            self::BREECH => __('Assisted breech'),
            self::CAESAREAN => __('Caesarean section'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SPONTANEOUS_VAGINAL => __('Vaginal birth without instruments.'),
            self::VACUUM => __('Vaginal birth assisted by vacuum extraction.'),
            self::FORCEPS => __('Vaginal birth assisted by forceps.'),
            self::BREECH => __('Vaginal breech delivery with assistance.'),
            self::CAESAREAN => __('Operative abdominal delivery.'),
        };
    }

    public function getColor(): string
    {
        return 'primary';
    }
}
