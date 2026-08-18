<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\ImmunizationSchedule;

class ImmunizationSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny ImmunizationSchedule');
    }

    public function view(AuthUser $authUser, ImmunizationSchedule $record): bool
    {
        return $authUser->can('View ImmunizationSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create ImmunizationSchedule');
    }

    public function update(AuthUser $authUser, ImmunizationSchedule $record): bool
    {
        return $authUser->can('Update ImmunizationSchedule');
    }

    public function delete(AuthUser $authUser, ImmunizationSchedule $record): bool
    {
        return $authUser->can('Delete ImmunizationSchedule');
    }

    public function restore(AuthUser $authUser, ImmunizationSchedule $record): bool
    {
        return $authUser->can('Restore ImmunizationSchedule');
    }

    public function forceDelete(AuthUser $authUser, ImmunizationSchedule $record): bool
    {
        return $authUser->can('ForceDelete ImmunizationSchedule');
    }
}
