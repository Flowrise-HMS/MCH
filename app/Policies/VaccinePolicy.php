<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\Vaccine;

class VaccinePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny Vaccine');
    }

    public function view(AuthUser $authUser, Vaccine $record): bool
    {
        return $authUser->can('View Vaccine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create Vaccine');
    }

    public function update(AuthUser $authUser, Vaccine $record): bool
    {
        return $authUser->can('Update Vaccine');
    }

    public function delete(AuthUser $authUser, Vaccine $record): bool
    {
        return $authUser->can('Delete Vaccine');
    }

    public function restore(AuthUser $authUser, Vaccine $record): bool
    {
        return $authUser->can('Restore Vaccine');
    }

    public function forceDelete(AuthUser $authUser, Vaccine $record): bool
    {
        return $authUser->can('ForceDelete Vaccine');
    }
}
