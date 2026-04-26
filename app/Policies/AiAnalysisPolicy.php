<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AiAnalysis;
use Illuminate\Auth\Access\HandlesAuthorization;

class AiAnalysisPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiAnalysis');
    }

    public function view(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('View:AiAnalysis');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiAnalysis');
    }

    public function update(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('Update:AiAnalysis');
    }

    public function delete(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('Delete:AiAnalysis');
    }

    public function restore(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('Restore:AiAnalysis');
    }

    public function forceDelete(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('ForceDelete:AiAnalysis');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiAnalysis');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiAnalysis');
    }

    public function replicate(AuthUser $authUser, AiAnalysis $aiAnalysis): bool
    {
        return $authUser->can('Replicate:AiAnalysis');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AiAnalysis');
    }

}