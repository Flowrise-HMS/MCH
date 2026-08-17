<?php

namespace Modules\MCH\Enums;

enum PregnancyOutcome: string
{
    case ACTIVE = 'active';
    case DELIVERED = 'delivered';
    case REFERRED_OUT = 'referred_out';
    case LOST_TO_FOLLOW_UP = 'lost_to_follow_up';
    case STILLBIRTH = 'stillbirth';
    case MATERNAL_DEATH = 'maternal_death';
}
