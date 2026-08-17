<?php

namespace Modules\MCH\Tests\Feature;

use App\Models\User;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Modules\MCH\Enums\ChildHealthRecordStatus;
use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Enums\DevelopmentalScreen;
use Modules\MCH\Enums\EddSource;
use Modules\MCH\Enums\Edema;
use Modules\MCH\Enums\FeedingMethod;
use Modules\MCH\Enums\GrowthMeasurementType;
use Modules\MCH\Enums\MaternalPresentation;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Enums\PregnancyOutcome;
use Modules\MCH\Enums\PregnancyRiskFactor;
use Modules\MCH\Enums\RiskLevel;
use Modules\MCH\Enums\UrineResult;
use Modules\MCH\Filament\Clusters\MCH\MchCluster;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildHealthRecords\ChildHealthRecordResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\ChildVisitAssessments\ChildVisitAssessmentResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\GrowthMeasurements\GrowthMeasurementResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\MaternalVisitAssessments\MaternalVisitAssessmentResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\MchRecordResource;
use Modules\MCH\Filament\Clusters\MCH\Resources\PregnancyEpisodes\PregnancyEpisodeResource;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\MCH\Policies\PregnancyEpisodePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MchModuleConventionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<int, class-string>
     */
    private function enums(): array
    {
        return [
            PregnancyRiskFactor::class,
            RiskLevel::class,
            PregnancyOutcome::class,
            EddSource::class,
            GrowthMeasurementType::class,
            MchRecordStatus::class,
            ChildHealthRecordStatus::class,
            DangerSign::class,
            MaternalPresentation::class,
            Edema::class,
            UrineResult::class,
            FeedingMethod::class,
            DevelopmentalScreen::class,
        ];
    }

    public function test_enums_implement_filament_label_color_and_description_contracts(): void
    {
        foreach ($this->enums() as $enum) {
            $this->assertTrue(is_subclass_of($enum, HasLabel::class), $enum.' must implement HasLabel');
            $this->assertTrue(is_subclass_of($enum, HasColor::class), $enum.' must implement HasColor');
            $this->assertTrue(is_subclass_of($enum, HasDescription::class), $enum.' must implement HasDescription');

            $case = $enum::cases()[0];
            $this->assertNotNull($case->getLabel());
            $this->assertNotNull($case->getColor());
            $this->assertNotNull($case->getDescription());
        }
    }

    public function test_filament_resources_live_in_the_mch_cluster(): void
    {
        $resources = [
            PregnancyEpisodeResource::class,
            ChildHealthRecordResource::class,
            MaternalVisitAssessmentResource::class,
            ChildVisitAssessmentResource::class,
            GrowthMeasurementResource::class,
            MchRecordResource::class,
        ];

        foreach ($resources as $resource) {
            $this->assertSame(MchCluster::class, $resource::getCluster());
        }
    }

    public function test_services_live_under_classes_services(): void
    {
        $this->assertFileExists(base_path('Modules/MCH/app/Classes/Services/PregnancyRiskService.php'));
        $this->assertFileExists(base_path('Modules/MCH/app/Classes/Services/MaternalVisitAssessmentService.php'));
        $this->assertFileExists(base_path('Modules/MCH/app/Classes/Services/ChildVisitAssessmentService.php'));
        $this->assertFileExists(base_path('Modules/MCH/app/Classes/Services/MchBookIssuanceService.php'));
        $this->assertFileExists(base_path('Modules/MCH/app/Classes/Services/AncReturnScheduler.php'));
        $this->assertFileDoesNotExist(base_path('Modules/MCH/app/Services/PregnancyRiskService.php'));
    }

    public function test_policies_are_registered_and_enforced(): void
    {
        $this->migrateModules(['Core', 'Patient', 'MCH']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertInstanceOf(PregnancyEpisodePolicy::class, Gate::getPolicyFor(PregnancyEpisode::class));

        $user = User::factory()->create();
        $this->assertFalse($user->can('viewAny', PregnancyEpisode::class));

        Permission::findOrCreate('ViewAny PregnancyEpisode', 'web');
        $user->givePermissionTo('ViewAny PregnancyEpisode');

        $this->assertTrue($user->can('viewAny', PregnancyEpisode::class));
    }
}
