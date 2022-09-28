<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Server;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any servers.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the server.
     *
     * @return mixed
     */
    public function view(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can create servers.
     *
     * @return mixed
     */
    public function create(User $user): bool
    {
        return true;
        // return Permission::can($user->id,'liman','serverCreate','true');
    }

    /**
     * Determine whether the user can update the server.
     *
     * @return mixed
     */
    public function update(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can delete the server.
     *
     * @return mixed
     */
    public function delete(User $user, Server $server)
    {
        return Permission::can($user->id, 'server', 'id', $server->id);
    }

    /**
     * Determine whether the user can restore the server.
     *
     * @return mixed
     */
    public function restore(User $user, Server $server)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the server.
     *
     * @return mixed
     */
    public function forceDelete(User $user, Server $server)
    {
        return $user->isAdmin();
    }
}
