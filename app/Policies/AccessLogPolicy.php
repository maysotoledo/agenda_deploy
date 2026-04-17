<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AccessLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccessLog');
    }

    public function view(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('View:AccessLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccessLog');
    }

    public function update(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('Update:AccessLog');
    }

    public function delete(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('Delete:AccessLog');
    }

    public function restore(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('Restore:AccessLog');
    }

    public function forceDelete(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('ForceDelete:AccessLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccessLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccessLog');
    }

    public function replicate(AuthUser $authUser, AccessLog $accessLog): bool
    {
        return $authUser->can('Replicate:AccessLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccessLog');
    }

}