<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\ServerKey;
use App\Models\RoleUser;
use App\User;
use App\Models\UserSettings;
use App\Models\AccessToken;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\ConnectorToken;
use mervick\aesEverywhere\AES256;

class UserController extends Controller
{
    /**
     * @api {post} /kullanici/ekle Add Liman User
     * @apiName Add Liman User
     * @apiGroup Users
     *
     * @apiParam {String} name User name
     * @apiParam {String} email User Email
     *
     * @apiSuccess {JSON} message Message with randomly created user password.
     */
    public function add()
    {
        hook('user_add_attempt', [
            "request" => request()->all(),
        ]);
        request()->request->add(['email' => strtolower(request('email'))]);

        validate([
            'name' => ['required', 'string', 'max:255'],
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
            !preg_match(
                "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/",
                $password
            )
        );

        // Create And Fill User Data
        $user = User::create([
            'name' => request('name'),
            'email' => strtolower(request('email')),
            'password' => Hash::make($password),
            'status' => request('type') == "administrator" ? "1" : "0",
            'forceChange' => true,
        ]);

        hook('user_add_successful', [
            "user" => $user,
        ]);

        // Respond
        return respond(
            "Kullanıcı Başarıyla Eklendi. Parola : " . $password,
            200
        );
    }

    /**
     * @api {post} /kullanici/sil Remove Liman User
     * @apiName Remove Liman User
     * @apiGroup Users
     *
     * @apiParam {String} user_id User's ID
     *
     * @apiSuccess {JSON} message
     */
    public function remove()
    {
        hook('user_delete_attempt', [
            "user" => request('user_id'),
        ]);

        // Delete Permissions
        Permission::where('morph_id', request('user_id'))->delete();

        //Delete user roles
        RoleUser::where("user_id", request('user_id'))->delete();

        // Delete User
        User::where("id", request('user_id'))->delete();

        hook('user_delete_successful', [
            "user" => request('user_id'),
        ]);

        // Respond
        return respond("Kullanıcı Başarıyla Silindi!", 200);
    }

    /**
     * @api {post} /kullanici/parola/sifirla Reset Liman User' Password
     * @apiName Reset Liman User' Password
     * @apiGroup Users
     *
     * @apiParam {String} user_id User's ID
     *
     * @apiSuccess {JSON} message Message with new random password.
     */
    public function passwordReset()
    {
        hook('user_password_reset_attempt', [
            "user" => request('user_id'),
        ]);

        $user = User::find(request('user_id'));

        if ($user->auth_type == "ldap") {
            return respond("Bu kullanıcı tipinin şifresi sıfırlanamaz", 201);
        }

        // Generate Password
        do {
            $pool = str_shuffle(
                'abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&'
            );
            $password = substr($pool, 0, 10);
        } while (
            !preg_match(
                "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/",
                $password
            )
        );

        $user->update([
            "password" => Hash::make($password),
            "forceChange" => true,
        ]);

        hook('user_password_reset_successful', [
            "user" => $user,
            "password" => $password,
        ]);

        return respond("Yeni Parola : " . $password, 200);
    }

    /**
     * @api {post} /profil Update Self Password
     * @apiName Update Self Password
     * @apiGroup Users
     *
     * @apiParam {String} old_password User' old password
     * @apiParam {String} password New Password
     * @apiParam {String} password_confirmation New Password Confirmation
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function selfUpdate()
    {
        if (user()->auth_type == "ldap") {
            return respond(
                "Bu kullanıcı tipinin bilgileri değiştirilemez!",
                201
            );
        }

        if (
            !auth()->attempt([
                "email" => user()->email,
                "password" => request("old_password"),
            ])
        ) {
            return respond("Eski Parolanız geçerli değil.", 201);
        }

        validate([
            'name' => 'required|string|max:255' 
        ]);

        if (!empty(request()->password)) {
            validate([
                'password' => [
                    'string',
                    'min:10',
                    'max:32',
                    'confirmed',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/',
                ]
            ], [
                'password.regex' => 'Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.'
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
     * @api {post} /user/update Update User Data
     * @apiName Update User Data
     * @apiGroup Users
     *
     * @apiParam {String} username User' new username
     * @apiParam {String} email User' new email
     * @apiParam {String} status User' new status, 0 for regular, 1 for administrator.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function adminUpdate()
    {
        $validations = [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
        $user = User::where("id", request('user_id'))->first();

        if ($user->auth_type == "ldap") {
            unset($validations['username']);
        }

        validate($validations);

        $data = [
            'name' => request('username'),
            'email' => request('email'),
            'status' => request('status'),
        ];

        if ($user->auth_type == "ldap") {
            unset($data['name']);
        }

        $user->update($data);

        return respond('Kullanıcı Güncellendi.', 200);
    }

    /**
     * @api {post} /user/setting/delete Delete Vault Key
     * @apiName Delete Vault Key
     * @apiGroup Vault
     *
     * @apiParam {String} setting_id Target setting to delete.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function removeSetting()
    {
        if (request('type') == "key") {
            $first = ServerKey::find(request('id'));
        } else {
            $first = UserSettings::find(request('id'));
        }

        if (!$first) {
            return respond("Ayar bulunamadi", 201);
        }
        if (
            $first->name == "clientUsername" ||
            $first->name == "clientPassword"
        ) {
            $server = Server::find($first->server_id);

            if ($server) {
                $ip_address = "cn_" . str_replace(".", "_", $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        $flag = $first->delete();

        if ($flag) {
            return respond("Başarıyla silindi", 200);
        } else {
            return respond("Silinemedi", 201);
        }
    }

    /**
     * @api {post} /user/setting/update Update Vault Key
     * @apiName Update Vault Key
     * @apiGroup Vault
     *
     * @apiParam {String} setting_id Target setting to update.
     * @apiParam {String} new_value New Value of the setting.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function updateSetting()
    {
        $setting = UserSettings::where("id", request("setting_id"))->first();
        if (!$setting) {
            return respond("Ayar bulunamadi", 201);
        }

        if (
            $setting->name == "clientUsername" ||
            $setting->name == "clientPassword"
        ) {
            $server = Server::find($setting->server_id);

            if ($server) {
                $ip_address = "cn_" . str_replace(".", "_", $server->server_id);
                if (session($ip_address)) {
                    session()->remove($ip_address);
                }
            }
        }

        $key = env('APP_KEY') . $setting->user_id . $setting->server_id;
        $encrypted = AES256::encrypt(request('new_value'), $key);

        $flag = $setting->update([
            "value" => $encrypted,
        ]);
        if ($flag) {
            return respond("Başarıyla Güncellendi", 200);
        } else {
            return respond("Güncellenemedi", 201);
        }
    }

    public function forcePasswordChange()
    {
        if (
            !auth()->attempt([
                "email" => user()->email,
                "password" => request("old_password"),
            ])
        ) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    "message" => "Mevcut parolanız geçerli değil.",
                ]);
        }

        if(request("old_password") == request("password")) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    "message" => "Yeni parolanız mevcut parolanıza eşit olamaz.",
                ]);
        }
        
        $flag = Validator::make(request()->all(), [
            'password' => [
                'required',
                'string',
                'min:10',
                'max:32',
                'confirmed',
                'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/',
            ],
        ]);

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return redirect()
                ->route('password_change')
                ->withErrors([
                    "message" =>
                        "Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.",
                ]);
        }

        auth()
            ->user()
            ->update([
                'password' => Hash::make(request('password')),
                'forceChange' => false,
            ]);

        auth()->logout();
        session()->flush();
        return redirect()
            ->route('login')
            ->with(
                'status',
                "Kullanıcı Başarıyla Güncellendi, lütfen tekrar giriş yapın."
            );
    }

    /**
     * @api {get} /kasa Get Vault Keys
     * @apiName Get Vault Keys
     * @apiGroup Vault
     *
     * @apiSuccess {Array} settings User's Settings/Keys
     * @apiSuccess {Array} servers User's granted servers list.
     */
    public function userKeyList()
    {
        $settings = UserSettings::where("user_id", user()->id)->get();

        // Retrieve User servers that has permission.
        $servers = servers();

        foreach ($settings as $setting) {
            $server = $servers->find($setting->server_id);
            $setting->server_name = $server
                ? $server->name
                : __("Sunucu Silinmiş.");
            $setting->type = "setting";
        }

        $keys = user()->keys;

        foreach ($keys as $key) {
            $server = $servers->find($key->server_id);
            $key->server_name = $server
                ? $server->name
                : __("Sunucu Silinmiş.");
            $key->name = "Sunucu Anahtarı";
            $key->type = "key";
        }

        return magicView('keys.index', [
            "settings" => json_decode(
                json_encode(
                    array_merge($settings->toArray(), $keys->toArray())
                ),
                true
            ),
        ]);
    }

    /**
     * @api {post} /kasa/ekle Add Key
     * @apiName Add Key
     * @apiGroup Vault
     *
     * @apiParam {String} username Key Username.
     * @apiParam {String} password New Password.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function addKey()
    {
        $encKey = env('APP_KEY') . user()->id . server()->id;
        UserSettings::where([
            "server_id" => server()->id,
            "user_id" => user()->id,
            "name" => "clientUsername",
        ])->delete();
        UserSettings::where([
            "server_id" => server()->id,
            "user_id" => user()->id,
            "name" => "clientPassword",
        ])->delete();

        $data = [
            "clientUsername" => AES256::encrypt(request('username'), $encKey),
            "clientPassword" => AES256::encrypt(request('password'), $encKey),
            "key_port" => request('key_port'),
        ];

        ServerKey::updateOrCreate(
            ["server_id" => server()->id, "user_id" => user()->id],
            ["type" => request('type'), "data" => json_encode($data)]
        );

        ConnectorToken::clear();
        return respond("Başarıyla eklendi.");
    }

    public function cleanSessions()
    {
        ConnectorToken::clear();
        return respond("Önbellek temizlendi.");
    }

    /**
     * @api {get} /profil/anahtarlarim User' Access Tokens
     * @apiName User' Access Tokens
     * @apiGroup Access Tokens
     *
     * @apiSuccess {Array} access_tokens User's access tokens.
     */
    public function myAccessTokens()
    {
        return magicView('user.keys', [
            "access_tokens" => user()
                ->accessTokens()
                ->get(),
        ]);
    }

    /**
     * @api {post} /profil/anahtarlarim/ekle Create Access Tokens
     * @apiName Create Access Tokens
     * @apiGroup Access Tokens
     *
     * @apiParam {String} name Name of the access token.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function createAccessToken()
    {
        $token = Str::random(64);
        AccessToken::create([
            "user_id" => user()->id,
            "name" => request("name"),
            "token" => $token,
            "ip_range" => request("ip_range")
        ]);
        return respond("Anahtar Başarıyla Oluşturuldu.");
    }

    /**
     * @api {post} /profil/anahtarlarim/sil Delete Access Tokens
     * @apiName Delete Access Tokens
     * @apiGroup Access Tokens
     *
     * @apiParam {String} token_id ID of the token.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function revokeAccessToken()
    {
        $token = AccessToken::find(request("token_id"));
        if (!$token || $token->user_id != user()->id) {
            return respond("Anahtar Bulunamadı!", 201);
        }
        $token->delete();
        return respond("Anahtar Başarıyla Silindi");
    }
}
