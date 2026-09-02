<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\UserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use mervick\aesEverywhere\AES256;

/**
 * Vault Controller
 *
 * Manages user keys
 */
class VaultController extends Controller
{
    /**
     * User key list
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function index(Request $request)
    {
        $targetUserId = auth('api')->user()->id;
        if ($request->user_id != '') {
            if (! auth('api')->user()->isAdmin()) {
                return response()->json([
                    'message' => 'Bu işlemi yapmak için yönetici olmalısınız!'
                ], Response::HTTP_FORBIDDEN);
            }

            $targetUserId = $request->user_id;
        }

        $settings = UserSettings::where('user_id', $targetUserId)->get();

        // Retrieve User servers that has permission.
        $servers = auth('api')->user()->servers();

        foreach ($settings as $setting) {
            $server = $servers->find($setting->server_id);
            $setting->server_name = $server
                ? $server->name
                : __('Sunucu Silinmiş.');
            $setting->type = 'setting';
        }

        $keys = ServerKey::where('user_id', $targetUserId)->get();

        foreach ($keys as $key) {
            $server = $servers->find($key->server_id);
            $key->server_name = $server
                ? $server->name
                : __('Sunucu Silinmiş.');
            $key->name = 'Sunucu Anahtarı';
            $key->type = 'key';
        }

        return response()->json($settings->merge($keys));
    }

    /**
     * Create a new key inside of vault
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function create(Request $request)
    {
        $user_id = auth('api')->user()->id;
        if ($request->user_id != '' && auth('api')->user()->isAdmin()) {
            $user_id = $request->user_id;
        }

        // Yetki kontrolü: kullanıcı hedef sunucuya erişim iznine sahip olmalı.
        if (! Permission::can(auth('api')->user()->id, 'server', 'id', $request->server_id)) {
            return response()->json([
                'message' => 'Bu sunucu üzerinde anahtar oluşturma yetkiniz bulunmamaktadır!'
            ], Response::HTTP_FORBIDDEN);
        }

        $key = env('APP_KEY').$user_id.$request->server_id;
        $encrypted = AES256::encrypt($request->value, $key);

        $flag = UserSettings::updateOrCreate([
            'server_id' => $request->server_id,
            'user_id' => $user_id,
            'name' => $request->name,
        ], [
            'value' => $encrypted,
        ]);

        return response()->json([
            'status' => $flag,
        ], $flag ? 200 : 500);
    }

    /**
     * Update a key from vault
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function update(Request $request)
    {
        $setting = UserSettings::where('id', $request->setting_id)->first();
        if (! $setting) {
            return respond('Ayar bulunamadı!', 201);
        }

        if (! auth('api')->user()->isAdmin() && auth('api')->user()->id != $setting->user_id) {
            return respond('Güncellenemedi', 201);
        }

        if (
            $setting->name == 'clientUsername' ||
            $setting->name == 'clientPassword'
        ) {
            $server = Server::find($setting->server_id);

            if ($server) {
                $ip_address = 'cn_'.str_replace('.', '_', (string) $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        $key = env('APP_KEY').$setting->user_id.$setting->server_id;
        $encrypted = AES256::encrypt($request->value, $key);

        $flag = $setting->update([
            'value' => $encrypted,
        ]);

        return response()->json(['status' => $flag], $flag ? 200 : 500);
    }

    /**
     * Delete vault key
     *
     * @return JsonResponse|Response
     */
    public function delete(Request $request)
    {
        $isServerKey = $request->type == 'key';
        if ($isServerKey) {
            $first = ServerKey::find($request->id);
        } else {
            $first = UserSettings::find($request->id);
        }

        if (! $first) {
            return response()->json(['status' => false], 404);
        }

        // Ownership check: only admins or the owner can delete
        if (! auth('api')->user()->isAdmin() && auth('api')->user()->id != $first->user_id) {
            return response()->json(['status' => false, 'message' => 'Bu kayıt üzerinde yetkiniz bulunmamaktadır.'], 403);
        }

        if (
            $first->name == 'clientUsername' ||
            $first->name == 'clientPassword'
        ) {
            $server = Server::find($first->server_id);

            if ($server) {
                $ip_address = 'cn_'.str_replace('.', '_', (string) $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        if ($isServerKey) {
            $flag = DB::transaction(function () use ($first) {
                $server = DB::table('servers')->where('id', $first->server_id)->lockForUpdate()->first();
                $key = DB::table('server_keys')
                    ->where('id', $first->id)
                    ->where('server_id', $first->server_id)
                    ->lockForUpdate()
                    ->first();
                if (! $key) {
                    return false;
                }

                $wasShared = (bool) $key->shared;
                $flag = $first->delete();

                if ($flag && $wasShared && $server) {
                    DB::table('servers')->where('id', $server->id)->update(['shared_key' => 0]);
                }

                return $flag;
            });

            if ($flag) {
                AuditLog::write(
                    'server_key',
                    'delete',
                    [
                        'server_id' => $first->server_id,
                        'key_id' => $first->id,
                        'key_owner_id' => $first->user_id,
                        'was_shared' => (bool) $first->shared,
                    ],
                    'SERVER_KEY_DELETE'
                );
            }
        } else {
            $flag = $first->delete();
        }

        return response()->json(['status' => $flag], $flag ? 200 : 500);
    }

    /**
     * Create a key inside of vault
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function createKey(Request $request)
    {
        if ($request->exists('shared')) {
            $request->merge(['shared' => $request->boolean('shared')]);
        }
        validate([
            'server_id' => 'required|uuid',
            'user_id' => 'nullable|uuid',
            'type' => 'required|in:ssh,ssh_certificate,winrm,winrm_insecure,no_key',
            'username' => 'nullable|string|max:125',
            'password' => 'nullable|string|max:2500',
            'key_port' => 'required|integer|min:1|max:65535',
            'shared' => 'sometimes|boolean',
        ]);

        $user = auth('api')->user();
        $user_id = $user->id;
        if ($request->user_id != '' && $user->isAdmin()) {
            $user_id = $request->user_id;
        }

        // Yetki kontrolü: kullanıcı hedef sunucuya erişim iznine sahip olmalı.
        if (! Permission::can($user->id, 'server', 'id', $request->server_id)) {
            return response()->json([
                'message' => 'Bu sunucu üzerinde anahtar oluşturma yetkiniz bulunmamaktadır!',
            ], Response::HTTP_FORBIDDEN);
        }

        $server = Server::find($request->server_id);
        if (! $server) {
            return response()->json([
                'message' => 'Sunucu bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        $shared = $request->type !== 'no_key' && $request->boolean('shared');
        if ($shared && $request->input('sharing_scope') !== 'key') {
            return response()->json([
                'message' => 'Anahtar paylaşımı için güncel istemcide açık onay verilmelidir.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (
            $shared
            && (
                $user_id !== $user->id
                || ! Permission::can($user->id, 'liman', 'id', 'share_server_key')
            )
        ) {
            return response()->json([
                'message' => 'Yalnızca kendi bağlantı anahtarınızı paylaşabilirsiniz ve paylaşma iznine sahip olmalısınız.',
            ], Response::HTTP_FORBIDDEN);
        }

        $wasShared = DB::table('server_keys')
            ->where('server_id', $request->server_id)
            ->where('user_id', $user_id)
            ->where('shared', true)
            ->exists();
        $encKey = env('APP_KEY').$user_id.$request->server_id;
        $data = [
            'clientUsername' => AES256::encrypt($request->username, $encKey),
            'clientPassword' => AES256::encrypt($request->password, $encKey),
            'key_port' => $request->key_port,
        ];

        $result = DB::transaction(function () use ($request, $server, $shared, $user_id, $data) {
            $lockedServer = DB::table('servers')->where('id', $server->id)->lockForUpdate()->first();
            if (! $lockedServer) {
                return 'missing';
            }

            if (
                $shared
                && DB::table('server_keys')
                    ->where('server_id', $lockedServer->id)
                    ->where('shared', true)
                    ->where('user_id', '!=', $user_id)
                    ->exists()
            ) {
                return 'conflict';
            }

            UserSettings::where([
                'server_id' => $request->server_id,
                'user_id' => $user_id,
                'name' => 'clientUsername',
            ])->delete();
            UserSettings::where([
                'server_id' => $request->server_id,
                'user_id' => $user_id,
                'name' => 'clientPassword',
            ])->delete();

            ServerKey::where([
                'server_id' => $request->server_id,
                'user_id' => $user_id,
            ])->update(['shared' => false]);

            ServerKey::updateOrCreate(
                ['server_id' => $request->server_id, 'user_id' => $user_id],
                [
                    'type' => $request->type,
                    'data' => json_encode($data),
                    'shared' => $shared,
                ]
            );

            $sharedKeyExists = DB::table('server_keys')
                ->where('server_id', $lockedServer->id)
                ->where('shared', true)
                ->exists();
            DB::table('servers')->where('id', $lockedServer->id)->update([
                'shared_key' => $sharedKeyExists ? 1 : 0,
            ]);

            return 'saved';
        });

        if ($result === 'conflict') {
            return response()->json([
                'message' => 'Bu sunucu için başka bir bağlantı anahtarı zaten paylaşılmış.',
            ], Response::HTTP_CONFLICT);
        }
        if ($result === 'missing') {
            return response()->json([
                'message' => 'Sunucu bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($wasShared !== $shared) {
            $key = ServerKey::where([
                'server_id' => $request->server_id,
                'user_id' => $user_id,
            ])->orderByDesc('updated_at')->first();
            AuditLog::write(
                'server_key',
                $shared ? 'share' : 'unshare',
                [
                    'server_id' => $request->server_id,
                    'key_id' => $key?->id,
                    'key_owner_id' => $user_id,
                    'shared' => $shared,
                ],
                $shared ? 'SERVER_KEY_SHARE' : 'SERVER_KEY_UNSHARE'
            );
        }

        return respond('Başarıyla eklendi.');
    }

    public function updateKeySharing(Request $request, string $key_id)
    {
        if (! $request->exists('shared')) {
            validate(['shared' => 'required|boolean']);
        }
        $request->merge(['shared' => $request->boolean('shared')]);
        validate(['shared' => 'required|boolean']);

        $user = auth('api')->user();
        $shared = $request->boolean('shared');
        $key = ServerKey::find($key_id);
        if (! $key) {
            return response()->json([
                'message' => 'Anahtar bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        $isOwner = $key->user_id === $user->id;
        if ($shared) {
            if (
                ! $isOwner
                || ! Permission::can($user->id, 'server', 'id', $key->server_id)
                || ! Permission::can($user->id, 'liman', 'id', 'share_server_key')
            ) {
                return response()->json([
                    'message' => 'Bu bağlantı anahtarını paylaşma yetkiniz bulunmamaktadır.',
                ], Response::HTTP_FORBIDDEN);
            }
        } elseif (
            ! $isOwner
            && (
                ! Permission::can($user->id, 'server', 'id', $key->server_id)
                || ! Permission::can($user->id, 'liman', 'id', 'update_server')
                || ! Permission::can($user->id, 'liman', 'id', 'server_details')
            )
        ) {
            return response()->json([
                'message' => 'Bu bağlantı anahtarının paylaşımını kaldırma yetkiniz bulunmamaktadır.',
            ], Response::HTTP_FORBIDDEN);
        }

        $serverId = $key->server_id;
        $result = DB::transaction(function () use ($key_id, $serverId, $shared) {
            $server = DB::table('servers')->where('id', $serverId)->lockForUpdate()->first();
            if (! $server) {
                return 'missing';
            }

            $key = DB::table('server_keys')
                ->where('id', $key_id)
                ->where('server_id', $server->id)
                ->lockForUpdate()
                ->first();
            if (! $key) {
                return 'missing';
            }

            if (
                $shared
                && DB::table('server_keys')
                    ->where('server_id', $key->server_id)
                    ->where('shared', true)
                    ->where('id', '!=', $key->id)
                    ->exists()
            ) {
                return 'conflict';
            }

            DB::table('server_keys')->where('id', $key->id)->update([
                'shared' => $shared,
                'updated_at' => now(),
            ]);
            $sharedKeyExists = DB::table('server_keys')
                ->where('server_id', $server->id)
                ->where('shared', true)
                ->exists();
            DB::table('servers')->where('id', $server->id)->update([
                'shared_key' => $sharedKeyExists ? 1 : 0,
            ]);

            return 'saved';
        });

        if ($result === 'conflict') {
            return response()->json([
                'message' => 'Bu sunucu için başka bir bağlantı anahtarı zaten paylaşılmış.',
            ], Response::HTTP_CONFLICT);
        }
        if ($result === 'missing') {
            return response()->json([
                'message' => 'Anahtar veya sunucu bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        AuditLog::write(
            'server_key',
            $shared ? 'share' : 'unshare',
            [
                'server_id' => $key->server_id,
                'key_id' => $key->id,
                'key_owner_id' => $key->user_id,
                'shared' => $shared,
            ],
            $shared ? 'SERVER_KEY_SHARE' : 'SERVER_KEY_UNSHARE'
        );

        return respond($shared ? 'Anahtar paylaşıldı.' : 'Anahtar paylaşımı kaldırıldı.');
    }
}
