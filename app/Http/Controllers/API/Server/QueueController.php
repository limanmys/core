<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QueueController extends Controller
{
    public function index(Request $request) {
        $queues = Queue::where('type', 'install')
            ->whereJsonContains('data->server_id', $request->server_id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return $queues;
    }

    public function create(Request $request) {
        $tus = app('tus-server');
        $file = $tus->getCache()->get($request->file);

        $token = Token::create(auth('api')->user()->id);
        $client = new Client([
            'verify' => false,
            'headers' => [
                'Authorization' => $token
            ]
        ]);
        try {
            $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806') . "/queue", [
                'json' => [
                    'type' => 'install',
                    'data' => [
                        'server_id' => $request->server_id,
                        'path' => $file['file_path']
                    ]
                ],
                'timeout' => 30,
            ]);
            $output = (string) $res->getBody();

            $isJson = isJson($output, true);
            if ($isJson) {
                return response()->json($isJson, $res->getStatusCode());
            } else {
                return response()->json(
                    $output, $res->getStatusCode()
                );
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Liman render service is not working or crashed. '). !env('APP_DEBUG', false) ?: $e->getMessage()
            ], Response::HTTP_GATEWAY_TIMEOUT);
        }
    }
}