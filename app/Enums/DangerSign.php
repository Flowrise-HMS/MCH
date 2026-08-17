<?php

namespace Modules\MCH\Enums;

enum DangerSign: string
{
    case BLEEDING = 'bleeding';
    case FEVER = 'fever';
    case HEADACHE_OR_VISUAL = 'headache_or_visual';
    case REDUCED_FETAL_MOVEMENT = 'reduced_fetal_movement';
    case SWELLING = 'swelling';
}
