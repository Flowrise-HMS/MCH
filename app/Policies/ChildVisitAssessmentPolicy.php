<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\ChildVisitAssessment;

class ChildVisitAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny ChildVisitAssessment');
    }

    public function view(AuthUser $authUser, ChildVisitAssessment $record): bool
    {
        return $authUser->can('View ChildVisitAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create ChildVisitAssessment');
    }

    public function update(AuthUser $authUser, ChildVisitAssessment $record): bool
    {
        return $authUser->can('Update ChildVisitAssessment');
    }

    public function delete(AuthUser $authUser, ChildVisitAssessment $record): bool
    {
        return $authUser->can('Delete ChildVisitAssessment');
    }

    public function restore(AuthUser $authUser, ChildVisitAssessment $record): bool
    {
        return $authUser->can('Restore ChildVisitAssessment');
    }

    public function forceDelete(AuthUser $authUser, ChildVisitAssessment $record): bool
    {
        return $authUser->can('ForceDelete ChildVisitAssessment');
    }
}
