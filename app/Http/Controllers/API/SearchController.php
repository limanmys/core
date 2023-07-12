<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $searchable = [];

        // Get constant searchables
        if (user()->isAdmin()) {
            foreach (config('liman.admin_searchable') as $constant) {
                if (! isset($searchable['Admin İşlemleri'])) {
                    $searchable['Admin İşlemleri'] = [];
                }
                $constant['name'] = __($constant['name']);
                array_push($searchable['Admin İşlemleri'], $constant);
            }
        }

        foreach (config('liman.user_searchable') as $constant) {
            if (! isset($searchable['Kullanıcı İşlemleri'])) {
                $searchable['Kullanıcı İşlemleri'] = [];
            }
            $constant['name'] = __($constant['name']);
            array_push($searchable['Kullanıcı İşlemleri'], $constant);
        }

        // Server searching
        $servers = Server::select('id', 'name')->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            });

        $searchable['Sunucular'] = [];
        foreach ($servers as $server) {
            if (Permission::can(user()->id, 'liman', 'id', 'server_details')) {
                array_push($searchable['Sunucular'], [
                    'name' => $server->name,
                    'url' => route('server_one', $server->id),
                ]);
            }

            $extensions = $server->extensions();
            foreach ($extensions as $extension) {
                if (! isset($searchable[$server->name])) {
                    $searchable[$server->name] = [];
                }

                if (! empty($extension->display_name)) {
                    array_push($searchable[$server->name], [
                        'name' => $extension->display_name,
                        'url' => route('extension_server', [$extension->id, $server->id]),
                    ]);

                    continue;
                }
                array_push($searchable[$server->name], [
                    'name' => $extension->name,
                    'url' => route('extension_server', [$extension->id, $server->id]),
                ]);
            }
        }

        $results = [];
        $searchQuery = $request->input('query');

        foreach ($searchable as $category => $items) {
            foreach ($items as $item) {
                if (strpos(strtolower($item['name']), strtolower($searchQuery)) !== false) {
                    if (! isset($results[$category])) {
                        $results[$category] = [];
                    }
                    array_push($results[$category], $item);
                }
            }
        }

        return response()->json((object) $results);
    }
}
