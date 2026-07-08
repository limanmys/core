<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server Details Controller
 */
class DetailsController extends Controller
{
    /**
     * List servers that user can access
     *
     * @return JsonResponse
     */
    public function index()
    {
        return Server::orderBy('updated_at', 'DESC')
            ->get()
            ->filter(function ($server) {
                return Permission::can(auth('api')->user()->id, 'server', 'id', $server->id);
            })
            ->map(function ($server) {
                $server->extension_count = $server->extensions()->filter(function ($extension) {
                    return Permission::can(auth('api')->user()->id, 'extension', 'id', $extension->id);
                })->count();

                return $server;
            })
            ->values();
    }

    /**
     * Add server to favorites
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function favorite(Request $request)
    {
        auth('api')->user()
            ->myFavorites()
            ->toggle($request->server_id);

        return response()->json([
            'message' => 'İşlem başarılı.'
        ]);
    }
}
