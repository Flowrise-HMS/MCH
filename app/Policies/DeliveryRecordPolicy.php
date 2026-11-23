<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\DeliveryRecord;

class DeliveryRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny DeliveryRecord');
    }

    public function view(AuthUser $authUser, DeliveryRecord $record): bool
    {
        return $authUser->can('View DeliveryRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create DeliveryRecord');
    }

    public function update(AuthUser $authUser, DeliveryRecord $record): bool
    {
        return $authUser->can('Update DeliveryRecord');
    }

    public function delete(AuthUser $authUser, DeliveryRecord $record): bool
    {
        return $authUser->can('Delete DeliveryRecord');
    }

    public function restore(AuthUser $authUser, DeliveryRecord $record): bool
    {
        return $authUser->can('Restore DeliveryRecord');
    }

    public function forceDelete(AuthUser $authUser, DeliveryRecord $record): bool
    {
        return $authUser->can('ForceDelete DeliveryRecord');
    }
}
