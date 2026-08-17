<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\MaternalVisitAssessment;

class MaternalVisitAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny MaternalVisitAssessment');
    }

    public function view(AuthUser $authUser, MaternalVisitAssessment $record): bool
    {
        return $authUser->can('View MaternalVisitAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create MaternalVisitAssessment');
    }

    public function update(AuthUser $authUser, MaternalVisitAssessment $record): bool
    {
        return $authUser->can('Update MaternalVisitAssessment');
    }

    public function delete(AuthUser $authUser, MaternalVisitAssessment $record): bool
    {
        return $authUser->can('Delete MaternalVisitAssessment');
    }

    public function restore(AuthUser $authUser, MaternalVisitAssessment $record): bool
    {
        return $authUser->can('Restore MaternalVisitAssessment');
    }

    public function forceDelete(AuthUser $authUser, MaternalVisitAssessment $record): bool
    {
        return $authUser->can('ForceDelete MaternalVisitAssessment');
    }
}
