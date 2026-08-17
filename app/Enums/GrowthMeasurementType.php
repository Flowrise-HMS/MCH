<?php

namespace Modules\MCH\Enums;

enum GrowthMeasurementType: string
{
    case WEIGHT = 'weight';
    case LENGTH_HEIGHT = 'length_height';
    case MUAC = 'muac';
    case HEAD_CIRCUMFERENCE = 'head_circumference';
    case FUNDAL_HEIGHT = 'fundal_height';
}
