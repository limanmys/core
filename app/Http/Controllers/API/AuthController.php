<?php

namespace App\Http\Controllers\API;

use App\Classes\Ldap;
use App\Classes\LDAPSearchOptions;
use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\LdapRestriction;
use App\Models\Oauth2Token;
use App\Models\RoleMapping;
use App\Models\RoleMappingQueue;
use App\Models\RoleUser;
use App\Models\Server;
use App\Models\UserSettings;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'forceChangePassword']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', strtolower($request->email))
            ->orWhere('username', strtolower($request->email))
            ->first();

        if (! $user) {
            // Try keycloak authentication to create user from Keycloak
            if (env('KEYCLOAK_ACTIVE', false)) {
                $token = $this->authWithKeycloak($request, true);
                if ($token->status() === 200) {
                    return $token;
                }
            }

            // Try ldap authentication to create user from LDAP
            if ((bool) env('LDAP_STATUS', false)) {
                $token = $this->authWithLdap($request, true);
                if ($token->status() === 200) {
                    return $token;
                }
            }
        } else {
            // If User type keycloak
            if ($user->auth_type === 'keycloak' && env('KEYCLOAK_ACTIVE', false)) {
                return $this->authWithKeycloak($request);
            }

            // If User type LDAP
            if ($user->auth_type === 'ldap' && (bool) env('LDAP_STATUS', false)) {
                return $this->authWithLdap($request);
            }

            // Otherwise continue...
        }

        $token = auth('api')->attempt($validator->validated());
        if (! $token) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        if (auth('api')->user()->forceChange) {
            return response()->json(['message' => 'Şifrenizi değiştirmeniz gerekmektedir.'], 405);
        }

        return $this->createNewToken($token, $request);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Force change password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $token = auth('api')->attempt($validator->validated());
        if (! $token) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        $user = auth('api')->user();
        $user->forceChange = false;
        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Şifreniz başarıyla değiştirildi.']);
    }

    /**
     * Authenticate using Keycloak
     */
    private function authWithKeycloak(Request $request, bool $create = false)
    {
        $client = new Client([
            'verify' => false,
        ]);

        try {
            $r = $client->post(
                env('KEYCLOAK_BASE_URL').'/realms/'.env('KEYCLOAK_REALM').'/protocol/openid-connect/token',
                [
                    'form_params' => [
                        'client_id' => env('KEYCLOAK_CLIENT_ID'),
                        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
                        'username' => $request->email,
                        'password' => $request->password,
                        'grant_type' => 'password',
                        'scope' => 'openid',
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Keycloak authentication failed. '.$e->getMessage());

            return $this->returnLoginError($request->email);
        }

        $response = json_decode($r->getBody()->getContents(), true);
        if (! isset($response['access_token'])) {
            Log::error('Keycloak authentication failed. Access token is missing.');

            return $this->returnLoginError($request->email);
        }
        $details = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $response['access_token'])[1]))));

        if ($create) {
            $user = User::create([
                'id' => $details->sub,
                'name' => $details->name,
                'email' => $details->email,
                'username' => $details->preferred_username,
                'auth_type' => 'keycloak',
                'password' => Hash::make(Str::random(16)),
                'forceChange' => false,
            ]);
        } else {
            $user = User::where('id', $details->sub)->first();
        }

        Oauth2Token::updateOrCreate([
            'user_id' => $details->sub,
            'token_type' => $response['token_type'],
        ], [
            'user_id' => $details->sub,
            'token_type' => $response['token_type'],
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_in' => (int) $response['expires_in'],
            'refresh_expires_in' => (int) $response['refresh_expires_in'],
        ]);

        return $this->createNewToken(
            auth('api')->login($user),
            $request
        );
    }

    /**
     * Authenticate using LDAP
     */
    private function authWithLdap(Request $request, bool $create = false)
    {
        if (! env('LDAP_DOMAIN', false)) {
            setBaseDn();
        }

        $guidColumn = env('LDAP_GUID_COLUMN', 'objectguid');
        $mailColumn = env('LDAP_MAIL_COLUMN', 'mail');
        $domain = env('LDAP_DOMAIN', false);

        try {
            $ldap = new Ldap(
                env('LDAP_HOST'),
                $request->email,
                $request->password
            );
        } catch (\Throwable $e) {
            Log::error('LDAP authentication failed. '.$e->getMessage());

            return $this->returnLoginError($request->email);
        }

        $ldap_restrictions = LdapRestriction::all();
        $restrictedUsers = $ldap_restrictions->where('type', 'user')->pluck('name')->all();
        $restrictedGroups = $ldap_restrictions->where('type', 'group')->pluck('name')->all();

        $options = new LDAPSearchOptions(1, 1, [
            $guidColumn,
            $mailColumn,
            'samaccountname',
            'memberof',
            'givenname',
            'sn',
        ]);
        $results = $ldap->search('(&(objectClass=user)(sAMAccountName='.$request->email.'))', $options);

        if (count($results) == 0) {
            Log::error('LDAP authentication failed. User not found.');

            return $this->returnLoginError($request->email);
        }

        $ldapUser = $results[0];

        if (! isset($ldapUser[$guidColumn])) {
            Log::error('LDAP authentication failed. User guid not found.');

            return $this->returnLoginError($request->email);
        }
        $objectguid = bin2hex($ldapUser[$guidColumn]);

        $userGroups = $ldapUser['memberof'] ?? [];

        $user = User::where('objectguid', $objectguid)->first();
        if ($user) {
            RoleUser::where('user_id', $user->id)->delete();
        }

        if (! (((bool) $restrictedGroups) == false && ((bool) $restrictedUsers) == false)) {
            $groupCheck = (bool) $restrictedGroups;
            $userCheck = (bool) $restrictedUsers;
            if ($restrictedGroups && count(array_intersect($userGroups, $restrictedGroups)) === 0) {
                $groupCheck = false;
            }

            if ($restrictedUsers && ! in_array(strtolower($request->email), $restrictedUsers)) {
                $userCheck = false;
            }

            if ($groupCheck === false && $userCheck === false) {
                return $this->returnLoginError($request->email);
            }
        }

        if (! isset($ldapUser[$mailColumn])) {
            $mail = strtolower($request->email).'@'.$domain;
        } else {
            $mail = $ldapUser[$mailColumn];
        }

        if (isset($ldapUser['givenname'])) {
            $name = $ldapUser['givenname'];
            if (isset($ldapUser['sn'])) {
                $name .= ' '.$ldapUser['sn'];
            }
        } else {
            $name = $request->email;
        }

        if ($create) {
            $user = User::create([
                'objectguid' => $objectguid,
                'name' => $name,
                'email' => $mail,
                'username' => strtolower($ldapUser['samaccountname']),
                'auth_type' => 'ldap',
                'password' => Hash::make(Str::random(16)),
                'forceChange' => false,
            ]);
        } else {
            if ($user->email != $mail) {
                $temp = User::where('email', $mail)->first();
                if (! $temp) {
                    $user->update([
                        'email' => $mail,
                    ]);
                }
            }
            $user->update([
                'name' => $name,
                'auth_type' => 'ldap',
            ]);
        }

        RoleUser::where('user_id', $user->id)->where('type', 'ldap')->delete();
        if (isset($ldapUser['memberof'])) {
            foreach ($ldapUser['memberof'] as $row) {
                RoleMapping::where('group_id', md5($row))->get()->map(function ($item) use ($user) {
                    RoleUser::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id' => $item->role_id,
                        'type' => 'ldap',
                    ]);
                });
            }
        }

        $roleQueues = RoleMappingQueue::where('objectguid', $objectguid)->get();
        foreach ($roleQueues as $roleQueue) {
            RoleUser::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $roleQueue->role_id,
                'type' => 'ldap',
            ]);
        }

        foreach (Server::where('ip_address', trim(env('LDAP_HOST')))->get() as $server) {
            $encKey = env('APP_KEY').$user->id.$server->id;
            $encrypted = AES256::encrypt($request->email, $encKey);
            UserSettings::firstOrCreate([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'name' => 'clientUsername',
            ], [
                'value' => $encrypted,
            ]);

            $encrypted = AES256::encrypt($request->password, $encKey);

            UserSettings::firstOrCreate([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'name' => 'clientPassword',
            ], [
                'value' => $encrypted,
            ]);
        }

        return $this->createNewToken(
            auth('api')->login($user),
            $request
        );
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, Request $request = null)
    {
        User::find(auth('api')->user()->id)->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->ip(),
        ]);

        AuthLog::create([
            'user_id' => auth('api')->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'expired_at' => (auth('api')->factory()->getTTL() * 60 + time()) * 1000,
            'user' => User::find(auth('api')->user()->id),
        ]);
    }

    /**
     * Return login error
     *
     * @param  string  $email
     * @return JsonResponse
     */
    private function returnLoginError($email = '')
    {
        Log::info('Login attempt failed. '.$email);

        return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
    }
}
