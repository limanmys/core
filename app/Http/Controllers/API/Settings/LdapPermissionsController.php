<?php

namespace App\Http\Controllers\API\Settings;

use App\Classes\Ldap;
use App\Classes\LDAPException;
use App\Classes\LDAPSearchOptions;
use App\Http\Controllers\Controller;
use App\Models\LdapRestriction;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * LDAP Permissions Controller
 *
 * Settings of authorized LDAP users.
 */
class LdapPermissionsController extends Controller
{
    /**
     * Get users on LDAP host
     *
     * @param Request $request
     * @return JsonResponse
     * @throws LDAPException
     */
    public function getUsers(Request $request)
    {
        $ldap = new Ldap(
            env('LDAP_HOST'),
            $request->username,
            $request->password
        );

        $query = $request->search_query;
        $users = collect($ldap->search("(&(sAMAccountName=$query*)(sAMAccountType=805306368))", new LDAPSearchOptions(
            1,
            100,
            ['samaccountname']
        )))->map(function ($item) {
            return strtolower($item);
        });

        return response()->json([
            'items' => $users,
            'selected' => LdapRestriction::where('type', 'user')->get()->pluck('name'),
        ]);
    }

    /**
     * Set authorized LDAP users to log in
     *
     * @param Request $request
     * @return JsonResponse|Response
     * @throws LDAPException
     */
    public function setUsers(Request $request)
    {
        $users = $request->users;
        $ldapRestrictions = LdapRestriction::where('type', 'user')->get();

        foreach ($ldapRestrictions as $ldapRestriction) {
            if (! in_array($ldapRestriction->name, $users)) {
                $ldapRestriction->delete();
            }
        }

        $ldap = new Ldap(
            env('LDAP_HOST'),
            $request->username,
            $request->password
        );

        $guidColumn = env('LDAP_GUID_COLUMN', 'objectguid');
        $mailColumn = env('LDAP_MAIL_COLUMN', 'mail');
        $domain = env('LDAP_DOMAIN');

        foreach ($users as $user) {
            $ldapUser = $ldap->search('(&(objectClass=user)(sAMAccountName='.$user.'))', new LDAPSearchOptions(
                1,
                1,
                [
                    $guidColumn,
                    $mailColumn,
                    'samaccountname',
                    'memberof',
                    'givenname',
                    'sn',
                ]
            ));

            if (! $ldapRestrictions->contains('name', $user)) {
                LdapRestriction::create([
                    'name' => $user,
                    'type' => 'user',
                ]);
            }

            $ldapUser = $ldapUser[0];
            if (! isset($ldapUser[strtolower($guidColumn)])) {
                return respond('Kullanıcının GUID columnu bulunamadı!', 201);
            }

            if (! isset($ldapUser[$mailColumn])) {
                $mail = $user.'@'.$domain;
            } else {
                $mail = $ldapUser[$mailColumn][0];
            }

            if (isset($ldapUser['givenname'])) {
                $name = $ldapUser['givenname'];
                if (isset($ldapUser['sn'])) {
                    $name .= ' '.$ldapUser['sn'];
                }
            } else {
                $name = $user;
            }

            $objecthex = bin2hex($ldapUser[strtolower($guidColumn)]);
            $user = User::where('objectguid', $objecthex)->first();
            if (! $user) {
                if (User::where('email', $mail)->exists()) {
                    return respond('Kullanıcı zaten mevcut!', 201);
                }
                $user = User::create([
                    'name' => $name,
                    'username' => strtolower($user),
                    'email' => $mail,
                    'password' => Hash::make(str_random('16')),
                    'objectguid' => $objecthex,
                    'auth_type' => 'ldap',
                ]);
            }
        }

        return response()->json([
            'message' => 'Kullanıcılar başarıyla güncellendi.',
        ]);
    }

    /**
     * Get existing group list
     *
     * @param Request $request
     * @return JsonResponse
     * @throws LDAPException
     */
    public function getGroups(Request $request)
    {
        $ldap = new Ldap(
            env('LDAP_HOST'),
            $request->username,
            $request->password
        );

        $query = $request->search_query;
        $groups = $ldap->search("(&(sAMAccountName=$query*)(objectCategory=group))", new LDAPSearchOptions(
            1,
            100,
            ['samaccountname']
        ));

        return response()->json([
            'items' => $groups,
            'selected' => LdapRestriction::where('type', 'group')->get()->pluck('name'),
        ]);
    }

    /**
     * Set authorized groups to log in
     *
     * @param Request $request
     * @return JsonResponse
     * @throws LDAPException
     */
    public function setGroups(Request $request)
    {
        $ldap = new Ldap(
            env('LDAP_HOST'),
            $request->username,
            $request->password
        );

        $groups = $request->groups;
        $ldapRestrictions = LdapRestriction::where('type', 'group')->get();

        foreach ($ldapRestrictions as $ldapRestriction) {
            if (! in_array($ldapRestriction->name, $groups)) {
                $ldapRestriction->delete();
            }
        }

        foreach ($groups as $group) {
            if (! $ldapRestrictions->contains('name', $group)) {
                LdapRestriction::create([
                    'name' => $group,
                    'type' => 'group',
                ]);
            }
        }

        return response()->json([
            'message' => 'Gruplar başarıyla güncellendi.',
        ]);
    }
}
