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
use App\Models\SshHostKey;
use App\Support\SshHostKeyDecision;
use App\Support\SshHostKeyScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use mervick\aesEverywhere\AES256;
use Throwable;

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

        if (
            in_array($request->key_type, ['ssh', 'ssh_certificate'], true)
            && ! SshHostKey::activeForEndpoint($request->ip_address, (int) $request->port)->exists()
        ) {
            return response()->json([
                'code' => 'SSH_HOST_KEY_UNKNOWN',
                'message' => 'SSH sunucu kimliği doğrulanmadan sunucu oluşturulamaz.',
            ], 409);
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
        ]);

        // Add Server to request object to use it later.
        request()->request->add(['server' => $server]);

        if ($request->os_type === 'kubernetes' && $request->has('kubeconfig')) {
            $server->kubernetesInformation()->create([
                'kubeconfig' => $request->kubeconfig,
                'namespace' => $request->namespace,
                'deployment' => $request->deployment,
            ]);
        }

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
        validate([
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:-1|max:65535',
        ]);

        $ip = request('ip_address');
        $port = (int) request('port');

        // Port -1 means no port check needed (portless server)
        if ($port === -1) {
            return response()->json([
                'message' => 'Sunucuya başarıyla erişim sağlandı.',
            ]);
        }

        // Validate port range for actual connections
        if ($port < 1) {
            return response()->json(['port' => 'Geçersiz port numarası.'], 422);
        }

        // Resolve hostname to IP for validation
        $resolvedIp = $ip;
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $resolvedIp = gethostbyname($ip);
            if ($resolvedIp === $ip) {
                return response()->json(['ip_address' => 'Sunucu adresi çözümlenemedi.'], 422);
            }
        }

        // Block metadata endpoints and cloud-internal addresses (169.254.x.x link-local)
        if (filter_var($resolvedIp, FILTER_VALIDATE_IP) && str_starts_with($resolvedIp, '169.254.')) {
            return response()->json(['ip_address' => 'Link-local adresleri kullanılamaz.'], 422);
        }

        // Restrict to safe port range — block well-known internal service ports
        $blockedPorts = [6379, 11211, 27017, 9200, 9300, 2379, 5432, 3306];
        if (in_array($port, $blockedPorts)) {
            return response()->json(['port' => 'Bu port numarası güvenlik nedeniyle engellenmiştir.'], 422);
        }

        $status = @fsockopen(
            $ip,
            $port,
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status)) {
            fclose($status);

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
        validate([
            'ip_address' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'key_type' => 'required|in:ssh,ssh_certificate,winrm,winrm_insecure',
            'approve_host_key' => 'sometimes|boolean',
            'replace_host_key' => 'sometimes|boolean',
            'host_key_fingerprint' => 'sometimes|string|max:128',
        ]);

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'add_server')) {
            return response()->json([
                'message' => 'Bu işlemi yapmak için izniniz yok.',
            ], 403);
        }

        $connector = new GenericConnector;

        if (in_array(request('key_type'), ['ssh', 'ssh_certificate'], true)) {
            $hostKey = $this->reconcileSshHostKey(
                request(),
                request('ip_address'),
                (int) request('port'),
                true,
            );

            if ($hostKey instanceof JsonResponse) {
                return $hostKey;
            }
        }

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

    /**
     * Discover and approve the current host key of an existing SSH server.
     */
    public function sshHostKey(Request $request): JsonResponse
    {
        validate([
            'approve_host_key' => 'sometimes|boolean',
            'replace_host_key' => 'sometimes|boolean',
            'host_key_fingerprint' => 'sometimes|string|max:128',
        ]);

        $isApprovalRequest = $request->boolean('approve_host_key')
            || $request->boolean('replace_host_key');
        $canApprove = Permission::can(auth('api')->user()->id, 'liman', 'id', 'update_server');

        if (
            $isApprovalRequest
            && ! $canApprove
        ) {
            return response()->json([
                'message' => 'Bu işlemi yapmak için izniniz yok.',
            ], 403);
        }

        $server = Server::find($request->route('server_id'));
        if (! $server) {
            return response()->json(['message' => 'Sunucu bulunamadı.'], 404);
        }

        if (! in_array($server->type, ['ssh', 'ssh_certificate'], true)) {
            return response()->json([
                'status' => 'not_applicable',
            ]);
        }

        $hostKey = $this->reconcileSshHostKey(
            $request,
            $server->ip_address,
            (int) $server->key_port,
            true,
        );

        if ($hostKey instanceof JsonResponse) {
            if ($hostKey->getStatusCode() === 409) {
                $challenge = $hostKey->getData(true);
                $challenge['can_approve'] = $canApprove;

                return response()->json($challenge, 409);
            }

            return $hostKey;
        }

        return response()->json([
            'status' => 'trusted',
            'host' => $hostKey['host'],
            'port' => $hostKey['port'],
            'key_type' => $hostKey['key_type'],
            'fingerprint' => $hostKey['fingerprint'],
        ]);
    }

    /**
     * Discover and approve an SSH endpoint used directly by an extension or tunnel.
     */
    public function sshHostKeyForEndpoint(Request $request): JsonResponse
    {
        validate([
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'approve_host_key' => 'sometimes|boolean',
            'replace_host_key' => 'sometimes|boolean',
            'host_key_fingerprint' => 'sometimes|string|max:128',
        ]);

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'add_server')) {
            return response()->json([
                'message' => 'Bu işlemi yapmak için izniniz yok.',
            ], 403);
        }

        $hostKey = $this->reconcileSshHostKey(
            $request,
            $request->string('ip_address')->toString(),
            $request->integer('port'),
            true,
        );

        if ($hostKey instanceof JsonResponse) {
            return $hostKey;
        }

        return response()->json([
            'status' => 'trusted',
            'host' => $hostKey['host'],
            'port' => $hostKey['port'],
            'key_type' => $hostKey['key_type'],
            'fingerprint' => $hostKey['fingerprint'],
        ]);
    }

    /**
     * @return array{host: string, port: int, key_type: string, public_key: string, fingerprint: string}|JsonResponse
     */
    private function reconcileSshHostKey(
        Request $request,
        string $host,
        int $port,
        bool $replacementAllowed,
    ): array|JsonResponse {
        try {
            $discovered = (new SshHostKeyScanner)->discover($host, $port);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'code' => 'SSH_HOST_KEY_UNAVAILABLE',
                'message' => 'SSH sunucu kimliği alınamadı.',
            ], 422);
        }
        $normalizedHost = SshHostKey::normalizeHost($host);

        $trustedKeys = SshHostKey::activeForEndpoint($normalizedHost, $port)->get();
        $decision = SshHostKeyDecision::decide(
            $trustedKeys->pluck('public_key')->all(),
            $discovered['public_key'],
            $discovered['fingerprint'],
            $request->input('host_key_fingerprint'),
            $request->boolean('approve_host_key'),
            $replacementAllowed,
            $request->boolean('replace_host_key'),
        );

        if ($decision === SshHostKeyDecision::TRUSTED) {
            return $discovered;
        }

        $isMismatch = $trustedKeys->isNotEmpty();
        $code = $isMismatch ? 'SSH_HOST_KEY_MISMATCH' : 'SSH_HOST_KEY_UNKNOWN';

        if (in_array($decision, [
            SshHostKeyDecision::CHALLENGE_UNKNOWN,
            SshHostKeyDecision::CHALLENGE_MISMATCH,
        ], true)) {
            return response()->json([
                'code' => $code,
                'host' => $normalizedHost,
                'port' => $port,
                'key_type' => $discovered['key_type'],
                'fingerprint' => $discovered['fingerprint'],
                'trusted_fingerprints' => $trustedKeys->pluck('fingerprint')->values(),
                'message' => $isMismatch
                    ? 'SSH sunucusunun kimlik anahtarı değişmiş. Güvenilir bir kanaldan doğrulamadan değiştirmeyin.'
                    : 'SSH sunucusunun kimlik anahtarı henüz onaylanmamış.',
            ], 409);
        }

        DB::transaction(function () use ($normalizedHost, $port, $discovered, $decision): void {
            if ($decision === SshHostKeyDecision::REPLACE) {
                SshHostKey::activeForEndpoint($normalizedHost, $port)->update([
                    'revoked_at' => now(),
                    'revoked_by' => auth('api')->id(),
                ]);
            }

            SshHostKey::updateOrCreate(
                [
                    'host' => $normalizedHost,
                    'port' => $port,
                    'fingerprint' => $discovered['fingerprint'],
                ],
                [
                    'key_type' => $discovered['key_type'],
                    'public_key' => $discovered['public_key'],
                    'approved_by' => auth('api')->id(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                ],
            );
        });

        AuditLog::write(
            'ssh_host_key',
            $decision === SshHostKeyDecision::REPLACE ? 'replace' : 'approve',
            [
                'host' => $normalizedHost,
                'port' => $port,
                'fingerprint' => $discovered['fingerprint'],
            ],
            $decision === SshHostKeyDecision::REPLACE ? 'SSH_HOST_KEY_REPLACED' : 'SSH_HOST_KEY_APPROVED',
        );

        return $discovered;
    }
}
