<?php

namespace Modules\MCH\Enums;

enum UrineResult: string
{
    case NEGATIVE = 'negative';
    case TRACE = 'trace';
    case POSITIVE_1 = '+';
    case POSITIVE_2 = '++';
    case POSITIVE_3 = '+++';
}
