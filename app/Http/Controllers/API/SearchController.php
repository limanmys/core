<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $searchQuery = strtolower($request->input('query'));

        $results = Cache::remember(auth('api')->user()->id . '_searchable_' . $searchQuery, now()->addHour(), function () use ($searchable, $searchQuery) {
            $configs = [
                'user' => 'Kullanıcı',
                'common' => 'Genel'
            ];
            
            if (auth('api')->user()->isAdmin()) {
                $configs['admin'] = 'Yönetici';
            }

            foreach ($configs as $configKey => $defaultCategory) {
                foreach (config("liman.search.$configKey") as $constant) {
                    $category = $constant['name'] ?? $defaultCategory;
                    if (isset($constant['children'])) {
                        foreach ($constant['children'] as $child) {
                            $child['name'] = __($child['name']);
                            $searchable[$category][] = $child;
                        }
                    } else {
                        $constant['name'] = __($constant['name']);
                        $searchable[$defaultCategory][] = $constant;
                    }
                }
            }

            $servers = Server::select('id', 'name')
                ->get()
                ->filter(function ($server) {
                    return Permission::can(user()->id, 'server', 'id', $server->id);
                });

            foreach ($servers as $server) {
                $searchable['Sunucular'][] = [
                    'name' => $server->name,
                    'url' => "/servers/$server->id",
                ];

                foreach ($server->extensions() as $extension) {
                    $searchable[$server->name][] = [
                        'name' => $extension->display_name ?: $extension->name,
                        'url' => "/servers/$server->id/extensions/$extension->id",
                    ];

                    $searchSettings = getExtensionJson($extension->name)["search"] ?? [];
                    foreach ($searchSettings as $search) {
                        $searchable[$server->name] = [
                            ...$searchable[$server->name], 
                            ...$this->searchFromExtensions($server, $extension, $search, $searchQuery)
                        ];
                    }
                }
            }

            $results = [];
            foreach ($searchable as $category => $items) {
                foreach ($items as $item) {
                    if (str_contains(strtolower($item['name']), $searchQuery)) {
                        $results[$category][] = $item;
                    }
                }
            }

            return json_encode($results);
        });

        return $results;
    }

    /**
     * Search from microservices
     */
    private function searchFromExtensions($server, $extension, $settings, $searchQuery) {
        // Check if permission exists
        if (! Permission::can(auth('api')->user()->id, 'function', 'name', $extension->name, $settings['permission']))
            return [];

        if (strlen($searchQuery) < 2) 
            return [];
        
        if (is_array($settings['search']['query_parameters'])) 
            $settings['search']['query_parameters'] = json_encode($settings['search']['query_parameters']);

        $settings['search']['query_parameters'] = str_replace(
            sprintf("%%%%%s%%%%", $settings['search']['key']),
            $searchQuery,
            $settings['search']['query_parameters']
        );

        $settings['request'][$settings['search']['append_to']] .= 
            sprintf("?%s=%s", $settings['search']['query_key'], $settings['search']['query_parameters']);

        $extensionCall = Cache::remember(
            sprintf(
                "%s_search_%s_%s_%s_%s",
                auth('api')->user()->id,
                $server->id,
                $extension->id,
                $searchQuery,
                json_encode($settings['request'])
            ),
            $settings['metadata']['cache_enabled'] ? now()->addHours(2) : now()->addSeconds(15),
            function () use ($server, $extension, $settings) {
                return callExtensionFunction(
                    $extension,
                    $server,
                    $settings['request'],
                    $settings['function'],
                    ($settings['metadata']['timeout_ms'] / 1000) ?: 5
                );
            },
        );

        $records = [];
        if (is_array($extensionCall)) {
            $records = empty($settings['result']['take_from']) ? $extensionCall : ($extensionCall[$settings['result']['take_from']] ?? []);
        } elseif (is_object($extensionCall)) {
            $records = empty($settings['result']['take_from']) ? (array) $extensionCall : ((array) $extensionCall->{$settings['result']['take_from']} ?? []);
        }

        $results = [];
        preg_match_all("/%%(.*?)%%/", $settings['result']['format'], $resultKeys);
        preg_match_all("/%%(.*?)%%/", $settings['metadata']['url_format'], $urlKeys);
        foreach ($records as $record) {
            $record = (array) $record;
            $results[] = [
                'name' => (function () use ($resultKeys, $record, $settings) {
                    $name = $settings['result']['format'];
                    foreach ($resultKeys[1] as $key) {
                        $name = str_replace("%%$key%%", $record[$key] ?? '', $name);
                    }
                    return $name;
                })(),
                'url' => (function () use ($urlKeys, $record, $settings, $server, $extension) {
                    $url = $settings['metadata']['url_format'];
                    foreach ($urlKeys[1] as $key) {
                        $url = str_replace("%%$key%%", $record[$key] ?? '', $url);
                    }
                    return sprintf(
                        "/servers/%s/extensions/%s",
                        $server->id,
                        $extension->id
                    ) . $url;
                })(),
            ];
        }

        return $results;
    }
}
