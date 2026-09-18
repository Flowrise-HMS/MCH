<?php

namespace Modules\MCH\Classes\Services;

use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;

class PregnancyRiskService
{
    /**
     * @param  array<int, string>  $riskFactors
     */
    public function deriveRiskLevel(array $riskFactors): RiskLevel
    {
        foreach ($riskFactors as $factor) {
            $case = enum_try_from(PregnancyRiskFactor::class, $factor);

            if ($case !== null && $case->contributesToHighRisk()) {
                return RiskLevel::HIGH;
            }
        }

        return RiskLevel::LOW;
    }
}
