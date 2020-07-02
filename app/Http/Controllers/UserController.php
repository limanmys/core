<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\RoleUser;
use App\Models\User;
use App\Models\UserSettings;
use App\Models\AccessToken;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\ConnectorToken;

class UserController extends Controller
{
    public function one()
    {
        $user = User::where('id', auth()->id())->first();
        return view('users.one', [
            "user" => $user,
        ]);
    }

    public function add()
    {
        hook('user_add_attempt', [
            "request" => request()->all(),
        ]);

        $flag = Validator::make(request()->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
            ],
        ]);

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Lütfen geçerli veri giriniz.", 201);
        }

        // Check If user already exists.
        if (User::where('email', request('email'))->exists()) {
            return respond(
                "Bu email adresi ile ekli bir kullanıcı zaten var.",
                201
            );
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

        // Create And Fill User Data
        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
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
        $flag = Validator::make(request()->all(), [
            'password' => [
                'string',
                'min:10',
                'max:32',
                'confirmed',
                'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/',
            ],
        ]);

        if (!empty(request()->password)) {
            try {
                $flag->validate();
            } catch (\Exception $exception) {
                return respond(
                    "Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.",
                    201
                );
            }

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

        $flag = Validator::make(request()->all(), $validations);

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Girilen veri geçerli değil.", 201);
        }

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

    public function removeSetting()
    {
        $first = UserSettings::where([
            'user_id' => user()->id,
            'id' => request('setting_id'),
        ])->first();

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

        $flag = UserSettings::where([
            'user_id' => auth()->user()->id,
            'id' => request('setting_id'),
        ])->delete();

        if ($flag) {
            return respond("Başarıyla silindi", 200);
        } else {
            return respond("Silinemedi", 201);
        }
    }

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

        $encKey = env('APP_KEY') . $setting->user_id . $setting->server_id;
        $encrypted = openssl_encrypt(
            Str::random(16) . base64_encode(request('new_value')),
            'aes-256-cfb8',
            $encKey,
            0,
            Str::random(16)
        );
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
                    "message" => "Eski Parolanız geçerli değil.",
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

    public function userKeyList()
    {
        $settings = UserSettings::where("user_id", user()->id)->get();

        // Retrieve User servers that has permission.
        $servers = servers();

        foreach ($settings as $setting) {
            $server = $servers->find($setting->server_id);
            if ($setting->name == "clientUsername") {
                $setting->name = __("Anahtar - Kullanıcı Adı");
            }
            if ($setting->name == "clientPassword") {
                $setting->name = __("Anahtar - Şifre");
            }
            $setting->server_name = $server
                ? $server->name
                : __("Sunucu Silinmiş.");
        }

        return view('keys.index', [
            "servers" => objectToArray($servers, "name", "id"),
            "settings" => json_decode(json_encode($settings), true),
        ]);
    }

    public function addKey()
    {
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

        $encKey = env('APP_KEY') . user()->id . server()->id;
        $encryptedUsername = openssl_encrypt(
            Str::random(16) . base64_encode(request('username')),
            'aes-256-cfb8',
            $encKey,
            0,
            Str::random(16)
        );
        $encryptedPassword = openssl_encrypt(
            Str::random(16) . base64_encode(request('password')),
            'aes-256-cfb8',
            $encKey,
            0,
            Str::random(16)
        );
        UserSettings::create([
            "server_id" => server()->id,
            "user_id" => user()->id,
            "name" => "clientUsername",
            "value" => $encryptedUsername,
        ]);
        UserSettings::create([
            "server_id" => server()->id,
            "user_id" => user()->id,
            "name" => "clientPassword",
            "value" => $encryptedPassword,
        ]);
        ConnectorToken::clear();
        return respond("Başarıyla eklendi.");
    }

    public function cleanSessions()
    {
        ConnectorToken::clear();
        return respond("Önbellek temizlendi.");
    }

    public function myAccessTokens()
    {
        return view("user.keys");
    }

    public function createAccessToken()
    {
        $token = Str::random(64);
        AccessToken::create([
            "user_id" => user()->id,
            "name" => request("name"),
            "token" => $token,
        ]);
        return respond("Anahtar Başarıyla Oluşturuldu.");
    }

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
