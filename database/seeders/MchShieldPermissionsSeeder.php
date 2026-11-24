<?php

namespace Modules\MCH\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MCH\Classes\Services\MchConsentGate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MchShieldPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const RESOURCES = [
        'Vaccine',
        'ImmunizationSchedule',
        'ImmunizationRecord',
        'PregnancyEpisode',
        'ChildHealthRecord',
        'MaternalVisitAssessment',
        'ChildVisitAssessment',
        'GrowthMeasurement',
        'MchRecord',
        'DeliveryRecord',
        'PostnatalVisitAssessment',
    ];

    /**
     * @var list<string>
     */
    private const ACTIONS = [
        'ViewAny',
        'View',
        'Create',
        'Update',
        'Delete',
        'Restore',
        'ForceDelete',
        'ForceDeleteAny',
        'RestoreAny',
        'Replicate',
        'Reorder',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $names = ['View MchCluster', 'View MchWorkspace', 'View VaccinationCard'];

        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $names[] = $action.' '.$resource;
            }
        }

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (['super_admin', 'doctor', 'nurse'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role === null) {
                continue;
            }

            $role->givePermissionTo($names);
        }

        // Created but granted to nobody: overriding data-sharing consent is an
        // explicit decision for each deployment.
        Permission::findOrCreate(MchConsentGate::OVERRIDE_PERMISSION, 'web');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
