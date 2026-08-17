<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\MchRecord;

class MchRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny MchRecord');
    }

    public function view(AuthUser $authUser, MchRecord $record): bool
    {
        return $authUser->can('View MchRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create MchRecord');
    }

    public function update(AuthUser $authUser, MchRecord $record): bool
    {
        return $authUser->can('Update MchRecord');
    }

    public function delete(AuthUser $authUser, MchRecord $record): bool
    {
        return $authUser->can('Delete MchRecord');
    }

    public function restore(AuthUser $authUser, MchRecord $record): bool
    {
        return $authUser->can('Restore MchRecord');
    }

    public function forceDelete(AuthUser $authUser, MchRecord $record): bool
    {
        return $authUser->can('ForceDelete MchRecord');
    }
}
