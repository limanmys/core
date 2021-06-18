<?php

namespace App\Http\Controllers;

use App\Models\Server;
use \Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $searchable = [];

        // Get constant searchables
        foreach (config('liman.searchable') as $constant)
        {
            array_push($searchable, $constant);
        }

        // Server searching
        $servers = Server::select('id', 'name', 'city')->get();
        foreach ($servers as $server)
        {
            array_push($searchable, [
                'name' => $server->name,
                'url' => route('server_one', $server->id)
            ]);
            
            // Extension searching
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

        // Searching inside the searchable array
        foreach ($searchable as $search_item)
        {
            if (str_contains(strtolower($search_item['name']), strtolower($search_query)))
            {
                array_push($results, $search_item);
            }
        }

        return response()->json($results);
    }
}
