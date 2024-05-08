<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Http\JsonResponse;

/**
 * Menu Controller
 *
 * Returns necessary data for left sidebar.
 */
class MenuController extends Controller
{
    /**
     * Returns server list that is user is authorized to use
     *
     * @return mixed
     */
    public function servers()
    {
        $servers = Server::orderBy('updated_at', 'DESC')
            ->limit(20)
            ->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            })
            ->filter(function ($server) {
                return !(bool) user()->favorites()->where('id', $server->id)->first();
            });

        return response()->json([...user()->favorites()->map(function ($server) {
            $server->is_favorite = true;

            return $server;
        }), ...$servers->map(function ($server) {
            $server->is_favorite = false;
            $server->can_run_command = $server->canRunCommand();

            return $server;
        })]);
    }

    /**
     * Returns server details
     *
     * @param Server $server
     * @return JsonResponse
     */
    public function serverDetails(Server $server)
    {
        if (!Permission::can(user()->id, 'server', 'id', $server->id)) {
            return response()->json([
                'message' => 'You do not have permission to access this server.'
            ], 403);
        }

        $server->is_online = $server->isOnline();
        $server->extensions = $server->extensions()->map(function ($extension) use ($server) {
            $db = getExtensionJson($extension->name);
            if (isset($db['menus']) && $db['menus']) {
                $extension->menus = $this->checkMenu($db['menus'], $extension->name);
            } else {
                $extension->menus = [];
            }

            return $extension;
        });
        $server->is_favorite = (bool) user()->myFavorites()->where('server_id', $server->id)->exists();
        $server->can_run_command = $server->canRunCommand();

        return response()->json($server);
    }

    /**
     * Check if menu is eligible to be shown
     * 
     * @param mixed $menu
     * @param string $extension_name
     * 
     * @return $menu
     */
    private function checkMenu($menus, $extension_name)
    {
        if (auth('api')->user()->isAdmin()) {
            return $menus;
        }

        $extension_name = strtolower($extension_name);

        foreach ($menus as $key => &$menu) {
            if (isset($menu['permission'])) {
                if (!Permission::can(auth('api')->user()->id, 'function', 'name', $extension_name, $menu['permission'])) {
                    unset($menus[$key]);
                    continue;
                }
            }

            if (isset($menu['children'])) {
                $menu['children'] = $this->checkMenu($menu['children'], $extension_name);
                if (empty($menu['children'])) {
                    unset($menus[$key]);
                    continue;
                }
            }
        }

        return array_values($menus);
    }
}
