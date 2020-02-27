<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\LdapRestriction;
use Illuminate\Http\Request;
use App\Role;
use App\RoleMapping;
use Illuminate\Support\Facades\Validator;

class RoleMappingController extends Controller
{
    public function fetchDomainGroups()
    {
        $ldapUsername = request('ldapUsername');
        $ldapPassword = request('ldapPassword');

        $base_dn = config('ldap.ldap_base_dn');
        $domain = config('ldap.ldap_domain');

        try{
            $ldapConnection = ldap_connect("ldaps://" . config('ldap.ldap_host'));
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
                "id" => $group['distinguishedname'][0]
            ];
        }
        return respond($data);
    }

    public function fetchDomainUsers()
    {
        $ldapUsername = request('ldapUsername');
        $ldapPassword = request('ldapPassword');

        $base_dn = config('ldap.ldap_base_dn');
        $domain = config('ldap.ldap_domain');

        try{
            $ldapConnection = ldap_connect("ldaps://" . config('ldap.ldap_host'));
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConnection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS,0);
            ldap_bind($ldapConnection, $ldapUsername."@".$domain, $ldapPassword);
        }catch(\Exception $ex){
            return respond('LDAP Bağlantısı Kurulamadı', 201);
        }

        $sr = ldap_search($ldapConnection, $base_dn, '(objectClass=user)');
        $groups = ldap_get_entries($ldapConnection, $sr);
        unset($groups['count']);
        $data = [];
        foreach($groups as $group){
            $data[] = $group['samaccountname'][0];
        }
        return respond($data);
    }

    public function addLdapRestriction()
    {
        $flag = Validator::make(request()->all(), [
            'dn' => ['required', 'string'],
            'username' => ['required', 'string'],
            'type' => ['required', 'in:user,group'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Lütfen geçerli veri giriniz.",201);
        }

        LdapRestriction::firstOrCreate([
            "type" => request('type'),
            "name" => request('type') == "user" ? strtolower(request('username')) : request('dn'),
        ]);
        return respond("LDAP kısıtlaması başarıyla eklendi.");
    }

    public function deleteLdapRestriction()
    {
        $flag = Validator::make(request()->all(), [
            'ldap_restriction_id' => ['required', 'exists:ldap_restrictions,id'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Lütfen geçerli veri giriniz.",201);
        }

        LdapRestriction::find(request('ldap_restriction_id'))->delete();
        return respond("LDAP kısıtlaması başarıyla silindi.");
    }

    public function add()
    {
        $flag = Validator::make(request()->all(), [
            'dn' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Lütfen geçerli veri giriniz.",201);
        }

        RoleMapping::create([
            "dn" => request('dn'),
            "role_id" => request('role_id'),
            "group_id" => md5(request('dn')),
        ]);

        return respond("Rol eşleştirmesi başarıyla eklendi.");
    }


    public function delete()
    {
        $flag = Validator::make(request()->all(), [
            'role_mapping_id' => ['required', 'exists:role_mappings,id'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Lütfen geçerli veri giriniz.",201);
        }

        RoleMapping::find(request('role_mapping_id'))->delete();
        return respond("Rol eşleştirmesi başarıyla silindi.");
    }
}
