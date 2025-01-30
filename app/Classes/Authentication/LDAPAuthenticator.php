<?php

namespace App\Classes\Authentication;

use App\Classes\Ldap;
use App\Classes\LDAPSearchOptions;
use App\Models\Extension;
use App\Models\LdapRestriction;
use App\Models\Permission;
use App\Models\RoleMapping;
use App\Models\RoleMappingQueue;
use App\Models\RoleUser;
use App\Models\Server;
use App\Models\UserSettings;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

class LDAPAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        if (! env('LDAP_DOMAIN', false)) {
            setBaseDn();
        }

        $guidColumn = env('LDAP_GUID_COLUMN', 'objectguid');
        $mailColumn = env('LDAP_MAIL_COLUMN', 'mail');
        $domain = env('LDAP_DOMAIN', false);

        $create = User::where('email', strtolower($request->email))
            ->orWhere('username', strtolower($request->email))
            ->first();

        try {
            // Check if email contains @ symbol if not, just write the email
            if (! strpos($request->email, '@')) {
                $email = strtolower($request->email);
            } else {
                $email = explode("@", strtolower($request->email))[0];
            }
            $ldap = new Ldap(
                env('LDAP_HOST'),
                $email,
                $request->password
            );
        } catch (\Throwable $e) {
            Log::error('LDAP authentication failed. '.$e->getMessage());

            return Authenticator::returnLoginError($request->email);
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
            'userprincipalname'
        ]);
        $results = $ldap->search(sprintf('(|(sAMAccountName=%s)(%s=%s)(userPrincipalName=%s))', $request->email, $mailColumn, $request->email, $request->email), $options);

        if (count($results) == 0) {
            Log::error('LDAP authentication failed. User not found.');

            return Authenticator::returnLoginError($request->email);
        }

        $ldapUser = $results[0];

        if (! isset($ldapUser[$guidColumn])) {
            Log::error('LDAP authentication failed. User guid not found.');

            return Authenticator::returnLoginError($request->email);
        }
        $objectguid = bin2hex($ldapUser[$guidColumn]);

        $userGroups = $ldapUser['memberof'] ?? [];
        if (is_string($userGroups)) {
            $userGroups = [$userGroups];
        }
        $userGroups = array_map(function ($item) {
            // Convert CN=a,OU=b,DC=A,DC=B format to a
            return explode(',', explode('=', $item)[1])[0];
        }, $userGroups);

        $user = User::where('objectguid', $objectguid)->first();

        if (! (((bool) $restrictedGroups) == false && ((bool) $restrictedUsers) == false)) {
            $groupCheck = (bool) $restrictedGroups;
            $userCheck = (bool) $restrictedUsers;
            if ($restrictedGroups && count(array_intersect($userGroups, $restrictedGroups)) === 0) {
                $groupCheck = false;
            }

            if ($restrictedUsers && ! in_array(strtolower($ldapUser['samaccountname']), $restrictedUsers)) {
                $userCheck = false;
            }

            if ($groupCheck === false && $userCheck === false) {
                return Authenticator::returnLoginError($request->email);
            }
        }

        if (!isset($ldapUser[$mailColumn])) {
            if (!isset($ldapUser['userprincipalname'])) {
                $mail = strtolower($request->email) . '@' . $domain;
            } else {
                $mail = $ldapUser['userprincipalname'];
            }
        } else {
            $mail = $ldapUser[$mailColumn];
        }

        if (isset($ldapUser['givenname'])) {
            $name = $ldapUser['givenname'];
            if (isset($ldapUser['sn'])) {
                $name .= ' '.$ldapUser['sn'];
            }
        } else {
            $name = $ldapUser['samaccountname'];
        }

        if (! $create) {
            try {
                $user = User::create([
                    'objectguid' => $objectguid,
                    'name' => $name,
                    'email' => $mail,
                    'username' => strtolower($ldapUser['samaccountname']),
                    'auth_type' => 'ldap',
                    'password' => Hash::make(Str::random(16)),
                    'forceChange' => false,
                ]);
            } catch (\Throwable $e) {
                Log::error('LDAP authentication failed. '.$e->getMessage());

                return Authenticator::returnLoginError($request->email);
            }
        } else {
            if (! $user) {
                // Return error if user already exists
                Log::error('LDAP authentication failed. User already exists on system.');
                return Authenticator::returnLoginError($request->email);
            }
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
        if (isset($userGroups) && is_array($userGroups) && count($userGroups) > 0) {
            foreach ($userGroups as $row) {
                RoleMapping::where('group_id', $row)->get()->map(function ($item) use ($user) {
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

        $extensionWithLdap = Extension::where('ldap_support', true)->get();
        $serverList = [];
        $keys = [];
        foreach ($extensionWithLdap as $extension) {
            $extensionJson = getExtensionJson($extension->name);
            $extensionServers = $extension->servers()->get()->filter(function ($server) use ($user) {
                return Permission::can($user->id, 'server', 'id', $server->id);
            })->toArray();
            foreach ($extensionServers as $server) {
                if (! isset($extensionJson['ldap_support_fields'])) {
                    $keys[$server['id']] = [
                        'username' => 'clientUsername',
                        'password' => 'clientPassword',
                    ];
                } else {
                    $keys[$server['id']] = $extensionJson['ldap_support_fields'];
                }
            }
            $serverList = array_merge($serverList, $extensionServers);
        }
        $serverList = [
            ...$serverList,
            ...Server::where('ip_address', trim(env('LDAP_HOST')))->get(),
        ];
        // Check if server list is unique by id
        $serverList = collect($serverList)->filter(function ($server) use ($user) {
            return Permission::can($user->id, 'server', 'id', $server['id']);
        })->unique('id')->values();

        foreach ($serverList as $server) {
            $encKey = env('APP_KEY').$user->id.$server['id'];
            UserSettings::updateOrCreate([
                'user_id' => $user->id,
                'server_id' => $server['id'],
                'name' => $keys[$server['id']]['username'] ?? 'clientUsername',
            ], [
                'value' => AES256::encrypt($request->email, $encKey),
            ]);

            UserSettings::updateOrCreate([
                'user_id' => $user->id,
                'server_id' => $server['id'],
                'name' => $keys[$server['id']]['password'] ?? 'clientPassword',
            ], [
                'value' => AES256::encrypt($request->password, $encKey),
            ]);
        }

        // Set user preference of session time
        auth('api')->factory()->setTTL($user->session_time);

        return Authenticator::createNewToken(
            auth('api')->login($user),
            $request
        );
    }
}
