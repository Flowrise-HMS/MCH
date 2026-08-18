<?php

namespace Modules\MCH\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\MCH\Classes\Services\AncReturnScheduler;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Classes\Services\PregnancyRiskService;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Policies\ChildHealthRecordPolicy;
use Modules\MCH\Policies\ChildVisitAssessmentPolicy;
use Modules\MCH\Policies\GrowthMeasurementPolicy;
use Modules\MCH\Policies\MaternalVisitAssessmentPolicy;
use Modules\MCH\Policies\MchRecordPolicy;
use Modules\MCH\Policies\PregnancyEpisodePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MchServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'MCH';

    protected string $nameLower = 'mch';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerPolicies();
        $this->registerServices();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(PregnancyEpisode::class, PregnancyEpisodePolicy::class);
        Gate::policy(ChildHealthRecord::class, ChildHealthRecordPolicy::class);
        Gate::policy(GrowthMeasurement::class, GrowthMeasurementPolicy::class);
        Gate::policy(MaternalVisitAssessment::class, MaternalVisitAssessmentPolicy::class);
        Gate::policy(ChildVisitAssessment::class, ChildVisitAssessmentPolicy::class);
        Gate::policy(MchRecord::class, MchRecordPolicy::class);
    }

    protected function registerServices(): void
    {
        $this->app->singleton(PregnancyRiskService::class);
        $this->app->singleton(MaternalVisitAssessmentService::class);
        $this->app->singleton(ChildVisitAssessmentService::class);
        $this->app->singleton(MchBookIssuanceService::class);
        $this->app->singleton(AncReturnScheduler::class);
        $this->app->singleton(ImmunizationRecordService::class);
        $this->app->singleton(EpiDueService::class);
    }
}
