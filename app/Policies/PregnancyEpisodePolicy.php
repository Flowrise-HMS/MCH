<?php

declare(strict_types=1);

namespace Modules\MCH\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\MCH\Models\PregnancyEpisode;

class PregnancyEpisodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny PregnancyEpisode');
    }

    public function view(AuthUser $authUser, PregnancyEpisode $record): bool
    {
        return $authUser->can('View PregnancyEpisode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create PregnancyEpisode');
    }

    public function update(AuthUser $authUser, PregnancyEpisode $record): bool
    {
        return $authUser->can('Update PregnancyEpisode');
    }

    public function delete(AuthUser $authUser, PregnancyEpisode $record): bool
    {
        return $authUser->can('Delete PregnancyEpisode');
    }

    public function restore(AuthUser $authUser, PregnancyEpisode $record): bool
    {
        return $authUser->can('Restore PregnancyEpisode');
    }

    public function forceDelete(AuthUser $authUser, PregnancyEpisode $record): bool
    {
        return $authUser->can('ForceDelete PregnancyEpisode');
    }
}
