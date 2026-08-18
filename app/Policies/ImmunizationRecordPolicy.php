<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\ImmunizationRecord;

class ImmunizationRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny ImmunizationRecord');
    }

    public function view(AuthUser $authUser, ImmunizationRecord $record): bool
    {
        return $authUser->can('View ImmunizationRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create ImmunizationRecord');
    }

    public function update(AuthUser $authUser, ImmunizationRecord $record): bool
    {
        return $authUser->can('Update ImmunizationRecord');
    }

    public function delete(AuthUser $authUser, ImmunizationRecord $record): bool
    {
        return $authUser->can('Delete ImmunizationRecord');
    }

    public function restore(AuthUser $authUser, ImmunizationRecord $record): bool
    {
        return $authUser->can('Restore ImmunizationRecord');
    }

    public function forceDelete(AuthUser $authUser, ImmunizationRecord $record): bool
    {
        return $authUser->can('ForceDelete ImmunizationRecord');
    }
}
