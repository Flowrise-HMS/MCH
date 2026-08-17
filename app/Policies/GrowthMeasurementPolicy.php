<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\GrowthMeasurement;

class GrowthMeasurementPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny GrowthMeasurement');
    }

    public function view(AuthUser $authUser, GrowthMeasurement $record): bool
    {
        return $authUser->can('View GrowthMeasurement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create GrowthMeasurement');
    }

    public function update(AuthUser $authUser, GrowthMeasurement $record): bool
    {
        return $authUser->can('Update GrowthMeasurement');
    }

    public function delete(AuthUser $authUser, GrowthMeasurement $record): bool
    {
        return $authUser->can('Delete GrowthMeasurement');
    }

    public function restore(AuthUser $authUser, GrowthMeasurement $record): bool
    {
        return $authUser->can('Restore GrowthMeasurement');
    }

    public function forceDelete(AuthUser $authUser, GrowthMeasurement $record): bool
    {
        return $authUser->can('ForceDelete GrowthMeasurement');
    }
}
