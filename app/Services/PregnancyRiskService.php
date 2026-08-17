<?php

namespace Modules\MCH\Services;

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
            $case = PregnancyRiskFactor::tryFrom($factor);

            if ($case !== null && $case->contributesToHighRisk()) {
                return RiskLevel::HIGH;
            }
        }

        return RiskLevel::LOW;
    }
}
