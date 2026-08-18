<?php

namespace Modules\MCH\Tests\Unit;

use Modules\MCH\Classes\Services\PregnancyRiskService;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PregnancyRiskServiceTest extends TestCase
{
    private PregnancyRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PregnancyRiskService::class);
    }

    #[Test]
    public function it_returns_low_risk_when_no_factors_are_present(): void
    {
        $this->assertSame(RiskLevel::LOW, $this->service->deriveRiskLevel([]));
    }

    #[Test]
    public function it_returns_high_risk_when_a_high_risk_factor_is_present(): void
    {
        $level = $this->service->deriveRiskLevel([
            PregnancyRiskFactor::HYPERTENSION->value,
        ]);

        $this->assertSame(RiskLevel::HIGH, $level);
    }

    #[Test]
    public function it_ignores_unknown_factor_values(): void
    {
        $this->assertSame(RiskLevel::LOW, $this->service->deriveRiskLevel(['not_a_real_factor']));
    }
}
