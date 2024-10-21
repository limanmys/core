<?php

namespace App\Http\Controllers\API;

use App\Connectors\GenericConnector;
use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use mervick\aesEverywhere\AES256;

class ServerController extends Controller
{
    /**
     * This function creates server in Liman database
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception|GuzzleException
     */
    public function create(Request $request)
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'add_server')) {
            return response()->json([
                'message' => 'Bu işlemi yapmak için izniniz yok.',
            ], 403);
        }

        $server = Server::create([
            'name' => request('name'),
            'ip_address' => request('ip_address'),
            'type' => request('key_type') != 'no_key' ? request('key_type') : 'none',
            'control_port' => request('port'),
            'os' => request('os_type') ?? 'none',
            'user_id' => auth('api')->user()->id,
            'shared_key' => request('shared') == 'true' ? 1 : 0,
            'key_port' => request('port'),
            'enabled' => 1,
        ]);

        // Add Server to request object to use it later.
        request()->request->add(['server' => $server]);

        if ($request->key_type != 'no_key') {
            $encKey = env('APP_KEY').auth('api')->user()->id.server()->id;
            $data = [
                'clientUsername' => AES256::encrypt(
                    $request->username,
                    $encKey
                ),
                'clientPassword' => AES256::encrypt(
                    $request->password,
                    $encKey
                ),
                'key_port' => request('port'),
            ];

            ServerKey::updateOrCreate(
                ['server_id' => server()->id, 'user_id' => auth('api')->user()->id],
                ['type' => $request->key_type, 'data' => json_encode($data)]
            );
        }

        return $this->grantPermissions($server);
    }

    /**
     * Update server name and IP address
     *
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'update_server')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!',
            ], '', Response::HTTP_FORBIDDEN);
        }

        $server = Server::find($request->server_id);
        if (! $server) {
            throw new JsonResponseException([
                'message' => 'Sunucu bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!',
            ], '', Response::HTTP_FORBIDDEN);
        }

        AuditLog::write(
            'server',
            'update',
            [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'server_ip' => $server->ip_address,
                'shared_status' => $server->shared_key ? 'true' : 'false',
                'new_server_name' => $request->name,
                'new_server_ip' => $request->ip_address,
                'new_shared_status' => $request->shared_key ? 'true' : 'false',
            ],
            'SERVER_UPDATE'
        );

        $server->name = $request->name;
        $server->ip_address = $request->ip_address;
        $server->shared_key = (bool) $request->shared_key;
        $server->save();

        return response()->json([
            'message' => 'İşlem başarılı.',
        ]);
    }

    /**
     * Delete server from system
     *
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        $server = Server::find($request->server_id);
        if (! $server) {
            throw new JsonResponseException([
                'message' => 'Sunucu bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (
            $server->user_id != auth('api')->id() &&
            ! auth('api')
                ->user()
                ->isAdmin()
        ) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!',
            ], '', Response::HTTP_FORBIDDEN);
        }

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!',
            ], '', Response::HTTP_FORBIDDEN);
        }

        AuditLog::write(
            'server',
            'delete',
            [
                'server_id' => $server->id,
                'server_name' => $server->name,
            ],
            'SERVER_DELETE'
        );

        $server->delete();

        return response()->json([
            'message' => 'İşlem başarılı.',
        ]);
    }

    /**
     * Grant server certificate
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    private function grantPermissions(Server $server)
    {
        Permission::grant(auth('api')->user()->id, 'server', 'id', $server->id);

        // SSL Control
        if (in_array($server->control_port, knownPorts())) {
            $cert = Certificate::where([
                'server_hostname' => $server->ip_address,
                'origin' => $server->control_port,
            ])->first();
            if (! $cert) {
                [$flag, $message] = retrieveCertificate(
                    $server->ip_address,
                    $server->control_port,
                );
                if ($flag) {
                    addCertificate(
                        $server->ip_address,
                        $server->control_port,
                        $message['path']
                    );
                }
            }
        }

        AuditLog::write(
            'server',
            'create',
            [
                'server_id' => $server->id,
                'server_name' => $server->name,
            ],
            'SERVER_CREATE'
        );

        return response()->json([
            'message' => 'Sunucu başarıyla eklendi.',
        ]);
    }

    /**
     * Check if server is active
     *
     * @return JsonResponse|Response
     */
    public function checkAccess()
    {
        if (request('port') == -1) {
            return response()->json([
                'message' => 'Sunucuya başarıyla erişim sağlandı.',
            ]);
        }
        $status = @fsockopen(
            request('ip_address'),
            request('port'),
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status)) {
            return response()->json([
                'message' => 'Sunucuya başarıyla erişim sağlandı.',
            ]);
        } else {
            return response()->json(['ip_address' => 'Sunucuya erişim sağlanamadı.'], 500);
        }
    }

    /**
     * Check if server name is valid
     *
     * @return JsonResponse|Response
     */
    public function checkName()
    {
        if (strlen((string) request('name')) > 40) {
            return response()->json(['name' => 'Lütfen daha kısa bir sunucu adı girin.'], 422);
        }
        if (! Server::where('name', request('name'))->exists()) {
            return response()->json([
                'message' => 'İsim onaylandı.',
            ]);
        } else {
            return response()->json(['name' => 'Bu isimde zaten bir sunucu var.'], 422);
        }
    }

    /**
     * Check if server key is valid
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function checkConnection()
    {
        $connector = new GenericConnector();
        $output = $connector->verify(
            request('ip_address'),
            request('username'),
            request('password'),
            request('port'),
            request('key_type')
        );

        if ($output == 'ok') {
            return response()->json([
                'message' => 'Anahtarınız doğrulandı.',
            ]);
        } else {
            return response()->json([
                'username' => 'Kullanıcı adı ya da şifreniz yanlış olabilir.',
                'password' => 'Kullanıcı adı ya da şifreniz yanlış olabilir.',
            ], 422);
        }
    }
}
