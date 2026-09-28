<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use App\Support\ServerConnectionStatus;
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
        $userId = auth('api')->user()->id;
        $servers = Server::orderBy('updated_at', 'DESC')
            ->get()
            ->filter(function ($server) {
                return Permission::can(auth('api')->user()->id, 'server', 'id', $server->id);
            });
        // Fetch metadata once for the visible list; never select encrypted data.
        $keys = ServerKey::whereIn('server_id', $servers->pluck('id'))
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('shared', true);
            })
            ->orderByDesc('updated_at')->orderBy('id')
            ->get(['server_id', 'user_id', 'type', 'shared'])->groupBy('server_id');

        return $servers->map(function ($server) use ($keys, $userId) {
            $serverKeys = $keys->get($server->id, collect());
            $server->connection_status = ServerConnectionStatus::describe(
                $server->type,
                $serverKeys->firstWhere('user_id', $userId)?->type,
                $serverKeys->firstWhere('shared', true)?->type,
            );
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
     * @return JsonResponse
     */
    public function favorite(Request $request)
    {
        auth('api')->user()
            ->myFavorites()
            ->toggle($request->server_id);

        return response()->json([
            'message' => 'İşlem başarılı.',
        ]);
    }
}
