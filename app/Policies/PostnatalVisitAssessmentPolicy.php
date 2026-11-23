<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\PostnatalVisitAssessment;

class PostnatalVisitAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny PostnatalVisitAssessment');
    }

    public function view(AuthUser $authUser, PostnatalVisitAssessment $record): bool
    {
        return $authUser->can('View PostnatalVisitAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create PostnatalVisitAssessment');
    }

    public function update(AuthUser $authUser, PostnatalVisitAssessment $record): bool
    {
        return $authUser->can('Update PostnatalVisitAssessment');
    }

    public function delete(AuthUser $authUser, PostnatalVisitAssessment $record): bool
    {
        return $authUser->can('Delete PostnatalVisitAssessment');
    }

    public function restore(AuthUser $authUser, PostnatalVisitAssessment $record): bool
    {
        return $authUser->can('Restore PostnatalVisitAssessment');
    }

    public function forceDelete(AuthUser $authUser, PostnatalVisitAssessment $record): bool
    {
        return $authUser->can('ForceDelete PostnatalVisitAssessment');
    }
}
