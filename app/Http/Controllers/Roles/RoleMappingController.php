<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Role;
use App\RoleMapping;

class RoleMappingController extends Controller
{
    public function fetchDomainGroups()
    {
        $ldapUsername = request('ldapUsername');
        $ldapPassword = request('ldapPassword');

        $base_dn = config('ldap.ldap_base_dn');
        $domain = config('ldap.ldap_domain');

        try{
            $ldapConnection = ldap_connect("ldap://" . config('ldap.ldap_host'));
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConnection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS,0);
            ldap_bind($ldapConnection, $ldapUsername."@".$domain, $ldapPassword);
        }catch(\Exception $ex){
            return respond('LDAP Bağlantısı Kurulamadı', 201);
        }

        $sr = ldap_search($ldapConnection, $base_dn, '(objectClass=group)');
        $groups = ldap_get_entries($ldapConnection, $sr);
        unset($groups['count']);
        $data = [];
        foreach($groups as $group){
            $data[] = [
                "dn" => $group['distinguishedname'][0],
                "id" => md5($group['distinguishedname'][0])
            ];
        }
        return respond($data);
    }
}
