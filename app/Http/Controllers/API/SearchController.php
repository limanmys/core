<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search Controller
 *
 * This controller utilizes ajax search on Liman
 */
class SearchController extends Controller
{
    /**
     * Search Function
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $searchable = [];

        // Get constant searchables
        if (user()->isAdmin()) {
            foreach (config('liman.new_search.admin') as $constant) {
                if (isset($constant['children'])) {
                    foreach ($constant['children'] as $child) {
                        if (! isset($searchable[$constant['name']])) {
                            $searchable[$constant['name']] = [];
                        }
                        $child['name'] = __($child['name']);
                        $searchable[$constant['name']][] = $child;
                    }
                } else {
                    if (! isset($searchable['Yönetim'])) {
                        $searchable['Yönetim'] = [];
                    }
                    $constant['name'] = __($constant['name']);
                    $searchable['Yönetim'][] = $constant;
                }
            }
        }

        foreach (config('liman.new_search.user') as $constant) {
            if (isset($constant['children'])) {
                foreach ($constant['children'] as $child) {
                    if (! isset($searchable[$constant['name']])) {
                        $searchable[$constant['name']] = [];
                    }
                    $child['name'] = __($child['name']);
                    $searchable[$constant['name']][] = $child;
                }
            } else {
                if (! isset($searchable['Kullanıcı'])) {
                    $searchable['Kullanıcı'] = [];
                }
                $constant['name'] = __($constant['name']);
                $searchable['Kullanıcı'][] = $constant;
            }
        }

        foreach (config('liman.new_search.common') as $constant) {
            if (isset($constant['children'])) {
                foreach ($constant['children'] as $child) {
                    if (! isset($searchable[$constant['name']])) {
                        $searchable[$constant['name']] = [];
                    }
                    $child['name'] = __($child['name']);
                    $searchable[$constant['name']][] = $child;
                }
            } else {
                if (! isset($searchable['Genel'])) {
                    $searchable['Genel'] = [];
                }
                $constant['name'] = __($constant['name']);
                $searchable['Genel'][] = $constant;
            }
        }

        // Server searching
        $servers = Server::select('id', 'name')->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            });

        $searchable['Sunucular'] = [];
        foreach ($servers as $server) {
            if (Permission::can(user()->id, 'liman', 'id', 'server_details')) {
                $searchable['Sunucular'][] = [
                    'name' => $server->name,
                    'url' => "/servers/$server->id",
                ];
            }

            $extensions = $server->extensions();
            foreach ($extensions as $extension) {
                if (! isset($searchable[$server->name])) {
                    $searchable[$server->name] = [];
                }

                if (! empty($extension->display_name)) {
                    $searchable[$server->name][] = [
                        'name' => $extension->display_name,
                        'url' => "/servers/$server->id/extensions/$extension->id",
                    ];

                    continue;
                }
                $searchable[$server->name][] = [
                    'name' => $extension->name,
                    'url' => "/servers/$server->id/extensions/$extension->id",
                ];
            }
        }

        $results = [];
        $searchQuery = $request->input('query');

        foreach ($searchable as $category => $items) {
            foreach ($items as $item) {
                if (str_contains(strtolower($item['name']), strtolower($searchQuery))) {
                    if (! isset($results[$category])) {
                        $results[$category] = [];
                    }
                    $results[$category][] = $item;
                }
            }
        }

        return response()->json((object) $results);
    }
}
