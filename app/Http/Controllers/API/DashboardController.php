<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\UserExtensionUsageStats;
use App\Models\User;

/**
 * Dashboard Controller
 *
 * Returns necessary values to be used on dashboard page.
 */
class DashboardController extends Controller
{
    /**
     * Returns dashboard information
     *
     * @return mixed
     */
    public function information()
    {
        return [
            'server_count' => Server::get()->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            })->count(),
            'user_count' => User::count(),
            'extension_count' => Extension::get()->filter(function ($extension) {
                return Permission::can(
                    user()->id,
                    'extension',
                    'id',
                    $extension->id
                );
            })->count(),
            'version' => getVersion(),
            'version_code' => getVersionCode()
        ];
    }

    /**
     * Returns latest logged in users
     *
     * @return mixed
     */
    public function latestLoggedInUsers()
    {
        if (! auth()->user()->isAdmin()) return [];

        return User::orderBy('last_login_at', 'desc')
            ->whereNot('id', auth('api')->user()->id)
            ->whereNotNull('last_login_at')
            ->take(5)
            ->get();
    }

    /**
     * Returns favorite servers
     *
     * @return mixed
     */
    public function favoriteServers()
    {
        $servers = user()->favorites()->take(6);

        if ($servers->count() < 6) {
            $temp = Server::orderBy('updated_at', 'desc')
                ->whereNotIn('id', $servers->pluck('id'))
                ->take(6 - $servers->count())
                ->get()
                ->filter(function ($server) {
                    return Permission::can(user()->id, 'server', 'id', $server->id);
                });

            $servers = $servers->merge($temp);
        }

        return $servers;
    }

    /**
     * Returns most used extensions
     *
     * @return mixed
     */
    public function mostUsedExtensions()
    {
        return UserExtensionUsageStats::where('user_id', auth('api')->user()->id)
            ->orderBy('usage', 'desc')
            ->with('extension')
            ->with('server')
            ->take(6)
            ->get();
    }
}
