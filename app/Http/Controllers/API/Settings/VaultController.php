<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\UserSettings;
use Illuminate\Http\Request;
use mervick\aesEverywhere\AES256;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user_id != '') {
            if (! auth('api')->user()->isAdmin()) {
                return respond('Bu işlemi yapmak için yönetici olmalısınız!', 403);
            }

            $settings = UserSettings::where('user_id', $request->user_id)->get();
        } else {
            $settings = UserSettings::where('user_id', auth('api')->user()->id)->get();
        }

        // Retrieve User servers that has permission.
        $servers = servers();

        foreach ($settings as $setting) {
            $server = $servers->find($setting->server_id);
            $setting->server_name = $server
                ? $server->name
                : __('Sunucu Silinmiş.');
            $setting->type = 'setting';
        }

        $keys = auth('api')->user()->keys;

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
        if ($request->type == 'key') {
            $first = ServerKey::find($request->id);
        } else {
            $first = UserSettings::find($request->id);
        }

        if (! $first) {
            return response()->json(['status' => false], 404);
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

        $flag = $first->delete();

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
        $user_id = auth('api')->user()->id;
        if ($request->user_id != '' && auth('api')->user()->isAdmin()) {
            $user_id = $request->user_id;
        }

        $encKey = env('APP_KEY').$user_id.$request->server_id;
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

        $data = [
            'clientUsername' => AES256::encrypt($request->username, $encKey),
            'clientPassword' => AES256::encrypt($request->password, $encKey),
            'key_port' => $request->key_port,
        ];

        ServerKey::updateOrCreate(
            ['server_id' => $request->server_id, 'user_id' => $user_id],
            ['type' => $request->type, 'data' => json_encode($data)]
        );

        Server::where(['id' => $request->server_id])->update(
            ['shared_key' => $request->shared == 'true' ? 1 : 0]
        );

        return respond('Başarıyla eklendi.');
    }
}
