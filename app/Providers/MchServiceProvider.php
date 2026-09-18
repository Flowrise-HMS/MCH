<?php

namespace Modules\MCH\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Classes\Support\RelationManagersRegistry;
use Modules\MCH\Classes\Services\AncReturnScheduler;
use Modules\MCH\Classes\Services\ChildVisitAssessmentService;
use Modules\MCH\Classes\Services\EpiAppointmentScheduler;
use Modules\MCH\Classes\Services\EpiDueService;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Classes\Services\MaternalVisitAssessmentService;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Classes\Services\MchWorkspaceService;
use Modules\MCH\Classes\Services\PregnancyRiskService;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Filament\RelationManagers\Patient\PatientGrowthMeasurementsRelationManager;
use Modules\MCH\Filament\RelationManagers\Patient\PatientImmunizationRecordsRelationManager;
use Modules\MCH\Filament\RelationManagers\Patient\PatientPregnancyEpisodesRelationManager;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\ChildVisitAssessment;
use Modules\MCH\Models\GrowthMeasurement;
use Modules\MCH\Models\ImmunizationRecord;
use Modules\MCH\Models\ImmunizationSchedule;
use Modules\MCH\Models\MaternalVisitAssessment;
use Modules\MCH\Models\MchRecord;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Models\Vaccine;
use Modules\MCH\Policies\ChildHealthRecordPolicy;
use Modules\MCH\Policies\ChildVisitAssessmentPolicy;
use Modules\MCH\Policies\GrowthMeasurementPolicy;
use Modules\MCH\Policies\ImmunizationRecordPolicy;
use Modules\MCH\Policies\ImmunizationSchedulePolicy;
use Modules\MCH\Policies\MaternalVisitAssessmentPolicy;
use Modules\MCH\Policies\MchRecordPolicy;
use Modules\MCH\Policies\PregnancyEpisodePolicy;
use Modules\MCH\Policies\VaccinePolicy;
use Modules\Patient\Models\Patient;
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
        $this->registerPatientRelations();
        $this->registerPatientRelationManagers();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(PregnancyEpisode::class, PregnancyEpisodePolicy::class);
        Gate::policy(ChildHealthRecord::class, ChildHealthRecordPolicy::class);
        Gate::policy(GrowthMeasurement::class, GrowthMeasurementPolicy::class);
        Gate::policy(MaternalVisitAssessment::class, MaternalVisitAssessmentPolicy::class);
        Gate::policy(ChildVisitAssessment::class, ChildVisitAssessmentPolicy::class);
        Gate::policy(MchRecord::class, MchRecordPolicy::class);
        Gate::policy(Vaccine::class, VaccinePolicy::class);
        Gate::policy(ImmunizationSchedule::class, ImmunizationSchedulePolicy::class);
        Gate::policy(ImmunizationRecord::class, ImmunizationRecordPolicy::class);
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
        $this->app->singleton(EpiAppointmentScheduler::class);
        $this->app->singleton(MchWorkspaceService::class);
    }

    /**
     * MCH-owned relations on Patient, resolved dynamically so Patient keeps no MCH imports
     * (same pattern Clinical uses for encounters and vitals).
     */
    protected function registerPatientRelations(): void
    {
        Patient::resolveRelationUsing('pregnancyEpisodes', function (Patient $patient) {
            return $patient->hasMany(PregnancyEpisode::class, 'patient_id', 'id');
        });

        Patient::resolveRelationUsing('activePregnancyEpisode', function (Patient $patient) {
            return $patient->hasOne(PregnancyEpisode::class, 'patient_id', 'id')
                ->where('outcome', PregnancyOutcome::ACTIVE)
                ->orderByDesc('created_at');
        });

        Patient::resolveRelationUsing('childHealthRecord', function (Patient $patient) {
            return $patient->hasOne(ChildHealthRecord::class, 'patient_id', 'id');
        });

        Patient::resolveRelationUsing('immunizationRecords', function (Patient $patient) {
            return $patient->hasMany(ImmunizationRecord::class, 'patient_id', 'id');
        });

        Patient::resolveRelationUsing('growthMeasurements', function (Patient $patient) {
            return $patient->hasMany(GrowthMeasurement::class, 'patient_id', 'id');
        });
    }

    protected function registerPatientRelationManagers(): void
    {
        $patientResource = 'Modules\\Patient\\Filament\\Clusters\\Patient\\Resources\\Patients\\PatientResource';

        if (! $this->app->bound(RelationManagersRegistry::class) || ! class_exists($patientResource)) {
            return;
        }

        $this->app->make(RelationManagersRegistry::class)->register($patientResource, fn (): array => [
            PatientPregnancyEpisodesRelationManager::class,
            PatientImmunizationRecordsRelationManager::class,
            PatientGrowthMeasurementsRelationManager::class,
        ]);
    }
}
