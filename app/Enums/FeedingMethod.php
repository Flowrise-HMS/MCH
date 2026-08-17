<?php

namespace Modules\MCH\Enums;

enum FeedingMethod: string
{
    case EXCLUSIVE_BREASTFEEDING = 'exclusive_breastfeeding';
    case MIXED = 'mixed';
    case COMPLEMENTARY = 'complementary';
    case OTHER = 'other';
}
