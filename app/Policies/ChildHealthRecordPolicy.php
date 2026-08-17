<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\ChildHealthRecord;

class ChildHealthRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny ChildHealthRecord');
    }

    public function view(AuthUser $authUser, ChildHealthRecord $record): bool
    {
        return $authUser->can('View ChildHealthRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create ChildHealthRecord');
    }

    public function update(AuthUser $authUser, ChildHealthRecord $record): bool
    {
        return $authUser->can('Update ChildHealthRecord');
    }

    public function delete(AuthUser $authUser, ChildHealthRecord $record): bool
    {
        return $authUser->can('Delete ChildHealthRecord');
    }

    public function restore(AuthUser $authUser, ChildHealthRecord $record): bool
    {
        return $authUser->can('Restore ChildHealthRecord');
    }

    public function forceDelete(AuthUser $authUser, ChildHealthRecord $record): bool
    {
        return $authUser->can('ForceDelete ChildHealthRecord');
    }
}
