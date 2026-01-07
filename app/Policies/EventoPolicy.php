<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Evento;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventoPolicy
{
    use HandlesAuthorization;

    /**
     * Super admin passa em tudo (opcional, mas comum).
     */
    public function before(AuthUser $authUser, string $ability): bool|null
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin')) {
            return true;
        }

        return null; // continua para os métodos abaixo
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Evento');
    }

    public function view(AuthUser $authUser, Evento $evento): bool
    {
        return $authUser->can('View:Evento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Evento');
    }

    public function update(AuthUser $authUser, Evento $evento): bool
    {
        // precisa ter a permissão do Shield
        if (! $authUser->can('Update:Evento')) {
            return false;
        }

        // epc pode editar
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('epc')) {
            return true;
        }

        // criador pode editar
        return (int) $evento->created_by === (int) $authUser->getKey();
    }

    public function delete(AuthUser $authUser, Evento $evento): bool
    {
        // precisa ter a permissão do Shield
        if (! $authUser->can('Delete:Evento')) {
            return false;
        }

        // epc pode cancelar
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('epc')) {
            return true;
        }

        // criador pode cancelar
        return (int) $evento->created_by === (int) $authUser->getKey();
    }

    public function restore(AuthUser $authUser, Evento $evento): bool
    {
        return $authUser->can('Restore:Evento');
    }

    public function forceDelete(AuthUser $authUser, Evento $evento): bool
    {
        return $authUser->can('ForceDelete:Evento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Evento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Evento');
    }

    public function replicate(AuthUser $authUser, Evento $evento): bool
    {
        return $authUser->can('Replicate:Evento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Evento');
    }
}
