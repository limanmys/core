<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Server;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Server Policy
 */
class ServerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any servers.
     *
     * @return bool
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the server.
     *
     * @return bool
     */
    public function view(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can create servers.
     *
     * @return true
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the server.
     *
     * @return bool
     */
    public function update(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can delete the server.
     *
     * @return bool
     */
    public function delete(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can restore the server.
     *
     * @return bool
     */
    public function restore(User $user, Server $server)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the server.
     *
     * @return bool
     */
    public function forceDelete(User $user, Server $server)
    {
        return $user->isAdmin();
    }
}
