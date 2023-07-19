<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use App\Models\UserExtensionUsageStats;
use App\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function latestLoggedInUsers()
    {
        $users = User::orderBy("last_login_at", "desc")
            ->whereNot('id', auth('api')->user()->id)
            ->whereNotNull('last_login_at')
            ->take(5)
            ->get();

        return response()->json($users);
    }

    public function favoriteServers()
    {
        $servers = user()->favorites()->take(6);

        if ($servers->count() < 6) {
            $temp = Server::orderBy("updated_at", "desc")
                ->whereNotIn('id', $servers->pluck('id'))
                ->take(6 - $servers->count())
                ->get()
                ->filter(function ($server) {
                    return Permission::can(user()->id, 'server', 'id', $server->id);
                });

            $servers = $servers->merge($temp);
        }

        return response()->json($servers);
    }

    public function mostUsedExtensions()
    {
        $mostUsedExtensions = UserExtensionUsageStats::where('user_id', auth('api')->user()->id)
            ->orderBy('usage', 'desc')
            ->with('extension')
            ->with('server')
            ->take(6)
            ->get();

        return response()->json($mostUsedExtensions);
    }
}
