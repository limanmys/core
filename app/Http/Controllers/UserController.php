<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\Permission;
use App\Models\RoleUser;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\UserSettings;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

/**
 * User Controller
 *
 * @extends Controller
 */
class UserController extends Controller
{
    /**
     * Creates a new user
     *
     * @return JsonResponse|Response
     */
    public function add()
    {
        hook('user_add_attempt', [
            'request' => request()->all(),
        ]);
        request()->request->add(['email' => strtolower((string) request('email'))]);

        validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
            ],
        ]);

        // Generate Password
        do {
            $pool = str_shuffle(
                'abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&'
            );
            $password = substr($pool, 0, 10);
        } while (
            ! preg_match(
                "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\!\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/",
                $password
            )
        );

        $data = [
            'name' => request('name'),
            'email' => strtolower((string) request('email')),
            'password' => Hash::make($password),
            'status' => request('type') == 'administrator' ? '1' : '0',
            'forceChange' => true,
        ];

        if (request('username')) {
            $data['username'] = request('username');
        }

        // Create And Fill User Data
        $user = User::create($data);

        hook('user_add_successful', [
            'user' => $user,
        ]);

        // Respond
        return respond(
            __('Kullanıcı Başarıyla Eklendi. Parola : ') . $password,
            200
        );
    }

    /**
     * Reset password of user
     *
     * @return JsonResponse|Response
     */
    public function passwordReset()
    {
        hook('user_password_reset_attempt', [
            'user' => request('user_id'),
        ]);

        $user = User::find(request('user_id'));

        if ($user->auth_type == 'ldap' || $user->auth_type == 'keycloak') {
            return respond('Bu kullanıcı tipinin şifresi sıfırlanamaz', 201);
        }

        // Generate Password
        do {
            $pool = str_shuffle(
                'abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&'
            );
            $password = substr($pool, 0, 10);
        } while (
            ! preg_match(
                "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\!\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/",
                $password
            )
        );

        $user->update([
            'password' => Hash::make($password),
            'forceChange' => true,
        ]);

        hook('user_password_reset_successful', [
            'user' => $user,
            'password' => $password,
        ]);

        return respond(__('Yeni Parola: ') . $password, 200);
    }

    /**
     * User password change
     *
     * @return JsonResponse|Response
     */
    public function selfUpdate()
    {
        if (user()->auth_type == 'ldap' || user()->auth_type == 'keycloak') {
            return respond(
                'Bu kullanıcı tipinin bilgileri değiştirilemez!',
                201
            );
        }

        if (
            ! auth()->attempt([
                'email' => user()->email,
                'password' => request('old_password'),
            ])
        ) {
            return respond('Eski Parolanız geçerli değil.', 201);
        }

        validate([
            'name' => 'required|string|max:255',
        ]);

        if (! empty(request()->password)) {
            validate([
                'password' => [
                    'string',
                    'min:10',
                    'max:32',
                    'confirmed',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\!\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/',
                ],
            ], [
                'password.regex' => 'Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.',
            ]);

            auth()
                ->user()
                ->update([
                    'name' => request('name'),
                    'password' => Hash::make(request('password')),
                ]);

            auth()->logout();
            session()->flush();

            return respond(
                'Kullanıcı Başarıyla Güncellendi, lütfen tekrar giriş yapın.',
                200
            );
        }

        auth()
            ->user()
            ->update([
                'name' => request('name'),
            ]);

        return respond('Kullanıcı Başarıyla Güncellendi', 200);
    }

    /**
     * Admin update user
     *
     * @return JsonResponse|Response
     */
    public function adminUpdate()
    {
        $validations = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
        $user = User::where('id', request('user_id'))->first();

        if ($user->auth_type == 'ldap' || $user->auth_type == 'keycloak') {
            unset($validations['name']);
            unset($validations['username']);
        }

        validate($validations);

        $data = [
            'name' => request('name'),
            'email' => request('email'),
            'status' => request('status'),
        ];

        if (request('username')) {
            $data['username'] = request('username');
        }

        if ($user->auth_type == 'ldap' || $user->auth_type == 'keycloak') {
            unset($data['name']);
            unset($data['username']);
        }

        $user->update($data);

        return respond('Kullanıcı Güncellendi.', 200);
    }

    /**
     * Delete vault key
     *
     * @return JsonResponse|Response
     */
    public function removeSetting()
    {
        if (request('type') == 'key') {
            $first = ServerKey::find(request('id'));
        } else {
            $first = UserSettings::find(request('id'));
        }

        if (! $first) {
            return respond('Ayar bulunamadi', 201);
        }
        if (
            $first->name == 'clientUsername' ||
            $first->name == 'clientPassword'
        ) {
            $server = Server::find($first->server_id);

            if ($server) {
                $ip_address = 'cn_' . str_replace('.', '_', (string) $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        $flag = $first->delete();

        if ($flag) {
            return respond('Başarıyla silindi', 200);
        } else {
            return respond('Silinemedi', 201);
        }
    }

    /**
     * Remove user from system
     *
     * @return JsonResponse|Response
     */
    public function remove()
    {
        hook('user_delete_attempt', [
            'user' => request('user_id'),
        ]);

        // Delete Permissions
        Permission::where('morph_id', request('user_id'))->delete();

        //Delete user roles
        RoleUser::where('user_id', request('user_id'))->delete();

        // Delete User
        User::where('id', request('user_id'))->delete();

        hook('user_delete_successful', [
            'user' => request('user_id'),
        ]);

        // Respond
        return respond('Kullanıcı Başarıyla Silindi!', 200);
    }

    /**
     * Create a new key inside of vault
     *
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function createSetting()
    {
        $user_id = user()->id;
        if (request('user_id') != "" && user()->isAdmin()) {
            $user_id = request('user_id');
        }

        $key = env('APP_KEY') . $user_id . request('server_id');
        $encrypted = AES256::encrypt(request('setting_value'), $key);

        $ok = UserSettings::create([
            'server_id' => request('server_id'),
            'user_id' => $user_id,
            'name' => request('setting_name'),
            'value' => $encrypted
        ]);

        if ($ok) {
            return respond("Başarılı");
        } else {
            return respond("Eklenirken hata oluştu.", 201);
        }
    }

    /**
     * Update a key from vault
     *
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function updateSetting()
    {
        $setting = UserSettings::where('id', request('setting_id'))->first();
        if (! $setting) {
            return respond('Ayar bulunamadı!', 201);
        }

        if (!user()->isAdmin() && user()->id != $setting->user_id) {
            return respond('Güncellenemedi', 201);
        }

        if (
            $setting->name == 'clientUsername' ||
            $setting->name == 'clientPassword'
        ) {
            $server = Server::find($setting->server_id);

            if ($server) {
                $ip_address = 'cn_' . str_replace('.', '_', (string) $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        $key = env('APP_KEY') . $setting->user_id . $setting->server_id;
        $encrypted = AES256::encrypt(request('new_value'), $key);

        $flag = $setting->update([
            'value' => $encrypted,
        ]);
        if ($flag) {
            return respond('Başarıyla Güncellendi', 200);
        } else {
            return respond('Güncellenemedi', 201);
        }
    }

    /**
     * Reset user password
     *
     * @return RedirectResponse
     */
    public function forcePasswordChange()
    {
        if (
            ! auth()->attempt([
                'email' => user()->email,
                'password' => request('old_password'),
            ])
        ) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    'message' => 'Mevcut parolanız geçerli değil.',
                ]);
        }

        if (request('old_password') == request('password')) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    'message' => 'Yeni parolanız mevcut parolanıza eşit olamaz.',
                ]);
        }

        $flag = Validator::make(request()->all(), [
            'password' => [
                'required',
                'string',
                'min:10',
                'max:32',
                'confirmed',
                'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\!\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/',
            ],
        ]);

        try {
            $flag->validate();
        } catch (\Exception) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    'message' => 'Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.',
                ]);
        }

        auth()
            ->user()
            ->update([
                'password' => Hash::make(request('password')),
                'forceChange' => false,
            ]);

        return redirect()->route('home');
    }

    /**
     * Retrieve user vault keys
     *
     * @return JsonResponse|Response
     */
    public function userKeyList()
    {
        if (request('user_id') != "") {
            if (!user()->isAdmin()) {
                return respond("Bu işlemi yapmak için yönetici olmalısınız!", 403);
            }

            $settings = UserSettings::where('user_id', request('user_id'))->get();
        } else {
            $settings = UserSettings::where('user_id', user()->id)->get();
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

        $keys = user()->keys;

        foreach ($keys as $key) {
            $server = $servers->find($key->server_id);
            $key->server_name = $server
                ? $server->name
                : __('Sunucu Silinmiş.');
            $key->name = 'Sunucu Anahtarı';
            $key->type = 'key';
        }

        return magicView('keys.index', [
            'settings' => json_decode(
                json_encode(
                    array_merge($settings->toArray(), $keys->toArray())
                ),
                true
            ),
            'users' => user()->isAdmin() ? User::all() : [],
            'selected_user' => request('user_id') != "" ? request('user_id') : user()->id
        ]);
    }

    /**
     * Create a key inside of vault
     *
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function addKey()
    {
        $user_id = user()->id;
        if (request('user_id') != "" && user()->isAdmin()) {
            $user_id = request('user_id');
        }

        $encKey = env('APP_KEY') . $user_id . server()->id;
        UserSettings::where([
            'server_id' => server()->id,
            'user_id' => $user_id,
            'name' => 'clientUsername',
        ])->delete();
        UserSettings::where([
            'server_id' => server()->id,
            'user_id' => $user_id,
            'name' => 'clientPassword',
        ])->delete();

        $data = [
            'clientUsername' => AES256::encrypt(request('username'), $encKey),
            'clientPassword' => AES256::encrypt(request('password'), $encKey),
            'key_port' => request('key_port'),
        ];

        ServerKey::updateOrCreate(
            ['server_id' => server()->id, 'user_id' => $user_id],
            ['type' => request('type'), 'data' => json_encode($data)]
        );

        Server::where(['id' => server()->id])->update(
            ['shared_key' => request()->shared == 'true' ? 1 : 0]
        );

        return respond('Başarıyla eklendi.');
    }

    /**
     * Get access tokens
     *
     * @return JsonResponse|Response
     */
    public function myAccessTokens()
    {
        return magicView('user.keys', [
            'access_tokens' => user()
                ->accessTokens()
                ->get(),
        ]);
    }

    /**
     * Create access tokens
     *
     * @return JsonResponse|Response
     */
    public function createAccessToken()
    {
        $token = Str::random(64);
        AccessToken::create([
            'user_id' => user()->id,
            'name' => request('name'),
            'token' => $token,
            'ip_range' => request('ip_range'),
        ]);

        return respond('Anahtar Başarıyla Oluşturuldu.');
    }

    /**
     * Revoke access tokens
     *
     * @return JsonResponse|Response
     */
    public function revokeAccessToken()
    {
        $token = AccessToken::find(request('token_id'));
        if (! $token || $token->user_id != user()->id) {
            return respond('Anahtar Bulunamadı!', 201);
        }
        $token->delete();

        return respond('Anahtar Başarıyla Silindi');
    }

    /**
     * Set users google secret
     *
     * @return Application|Factory|View|RedirectResponse|Redirector
     */
    public function setGoogleSecret()
    {
        if (! env('OTP_ENABLED')) {
            return redirect(route('home'));
        }

        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey();

        $QR_Image = $google2fa->getQRCodeInline(
            "Liman",
            auth()->user()->email,
            $secret
        );

        return view('google2fa.register', ['QR_Image' => $QR_Image, 'secret' => $secret]);
    }
}
