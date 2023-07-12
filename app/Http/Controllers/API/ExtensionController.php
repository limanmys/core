<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExtensionController extends Controller
{
    public function index()
    {
        // If server_id exists, don't return extensions that assigned to that server
        // For listing purposes
        $server_id = request('server_id');

        if ($server_id) {
            $extensions = Extension::whereDoesntHave('servers', function ($query) use ($server_id) {
                $query->where('server_id', $server_id);
            })->orderBy('updated_at', 'DESC')->get();
        } else {
            $extensions = Extension::orderBy('updated_at', 'DESC')->get();
        }

        return response()->json($extensions);
    }

    public function assign()
    {
        $extensions = request('extensions');
        try {
            DB::table('server_extensions')->insert(
                array_map(function ($extension) {
                    return [
                        'id' => Str::uuid()->toString(),
                        'server_id' => request('server_id'),
                        'extension_id' => $extension,
                    ];
                }, $extensions)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occured while assigning server.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Assigned successfully.',
        ]);
    }

    public function unassign()
    {
        $extensions = request('extensions');
        try {
            DB::table('server_extensions')
                ->where('server_id', request('server_id'))
                ->whereIn('extension_id', $extensions)
                ->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occured while unassigning server.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Unassigned successfully.',
        ]);
    }

    public function render()
    {
        if (extension()->status == '0') {
            return respond(
                __('Eklenti şu an güncelleniyor, lütfen birazdan tekrar deneyin.'),
                201
            );
        }

        if (extension()->require_key == 'true' && server()->key() == null) {
            return respond(
                __('Bu eklentiyi kullanabilmek için bir anahtara ihtiyacınız var, lütfen kasa üzerinden bir anahtar ekleyin.'),
                403
            );
        }

        $page = request('target_function')
            ? request('target_function')
            : 'index';
        $view = 'extension_pages.server_json';

        $token = Token::create(user()->id);

        $dbJson = getExtensionJson(extension()->name);
        if (isset($dbJson['preload']) && $dbJson['preload']) {
            $client = new Client(['verify' => false]);
            try {
                $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806'), [
                    'form_params' => [
                        'lmntargetFunction' => $page,
                        'extension_id' => extension()->id,
                        'server_id' => server()->id,
                        'token' => $token,
                    ],
                    'timeout' => 30,
                ]);
                $output = (string) $res->getBody();

                $isJson = isJson($output, true);
                if ($isJson && isset($isJson->status) && $isJson->status != 200) {
                    return respond(
                        $isJson->message,
                        $isJson->status,
                    );
                }
            } catch (\Exception $e) {
                if (env('APP_DEBUG', false)) {
                    return abort(
                        504,
                        __('Liman render service is not working or crashed. ').$e->getMessage(),
                    );
                } else {
                    return abort(
                        504,
                        __('Liman render service is not working or crashed.'),
                    );
                }
            }
        }

        return response()->json(
            [
                'html' => trim(
                    view($view, [
                        'auth_token' => $token,
                        'tokens' => user()
                            ->accessTokens()
                            ->get()
                            ->toArray(),
                        'extContent' => isset($output) ? $output : null,
                        'dbJson' => $dbJson,
                    ])->render()
                ),
            ]
        );
    }
}
