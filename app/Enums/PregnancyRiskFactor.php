<?php

namespace Modules\MCH\Enums;

enum PregnancyRiskFactor: string
{
    case MULTIPLE_GESTATION = 'multiple_gestation';
    case PREVIOUS_C_SECTION = 'previous_c_section';
    case HYPERTENSION = 'hypertension';
    case DIABETES = 'diabetes';
    case SICKLE_CELL = 'sickle_cell';
    case HIV_POSITIVE = 'hiv_positive';
    case ANAEMIA = 'anaemia';
    case HAEMORRHAGE_HISTORY = 'haemorrhage_history';
    case AGE_UNDER_18 = 'age_under_18';
    case AGE_OVER_35 = 'age_over_35';
    case OTHER = 'other';

    public function contributesToHighRisk(): bool
    {
        return $this !== self::OTHER;
    }
}
