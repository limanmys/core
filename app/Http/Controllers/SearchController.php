<?php

namespace App\Http\Controllers;

use App\Models\Server;
use \Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $searchable = [
            [
                'name' => 'Sistem Ayarları',
                'url' => route('settings')
            ],
            [
                'name' => 'Kullanıcı Ayarları',
                'url' => route('settings') . '#users'
            ],
            [
                'name' => 'Sistem Ayarları / Eklentiler',
                'url' => route('settings') . '#extensions'
            ],
            [
                'name' => 'Sistem Ayarları / Rol Grupları',
                'url' => route('settings') . '#roles'
            ],
            [
                'name' => 'Sistem Ayarları / Sunucu Grupları',
                'url' => route('settings') . '#serverGroups'
            ],
            [
                'name' => 'Sistem Ayarları / Sertifikalar',
                'url' => route('settings') . '#certificates'
            ],
            [
                'name' => 'Sistem Ayarları / Sağlık Durumu',
                'url' => route('settings') . '#health'
            ],
            [
                'name' => 'Sistem Ayarları / Dış Bildirimler',
                'url' => route('settings') . '#externalNotifications'
            ],
            [
                'name' => 'Sistem Ayarları / Kısıtlı Mod',
                'url' => route('settings') . '#restrictedMode'
            ],
            [
                'name' => 'Sistem Ayarları / Liman Market',
                'url' => route('settings') . '#limanMarket'
            ],
            [
                'name' => 'Sistem Ayarları / DNS',
                'url' => route('settings') . '#dnsSettings'
            ],
            [
                'name' => 'Sistem Ayarları / Mail Ayarları',
                'url' => route('settings') . '#mailSettings'
            ],
            [
                'name' => 'Sistem Ayarları / İnce Ayarlar',
                'url' => route('settings') . '#limanTweaks'
            ],
            [
                'name' => 'Eklenti Mağazası',
                'url' => route('market')
            ],
        ];

        $servers = Server::select('id', 'name', 'city')->get();
        foreach ($servers as $server)
        {
            array_push($searchable, [
                'name' => $server->name,
                'url' => route('server_one', $server->id)
            ]);

            $extensions = $server->extensions();
            
            foreach($extensions as $extension)
            {
                array_push($searchable, [
                    'name' => $server->name . ' / ' . $extension->name,
                    'url' => route('extension_server', [$extension->id, $server->city, $server->id])
                ]);
            }
        }

        $results = [];
        $search_query = $request->search_query;

        foreach ($searchable as $search_item)
        {
            //if (strpos(strtolower($search_item['name']), strtolower($search_query)))
            if (str_contains(strtolower($search_item['name']), strtolower($search_query)))
            {
                array_push($results, $search_item);
            }
        }

        return response()->json($results);
    }
}
