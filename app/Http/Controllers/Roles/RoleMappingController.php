<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\LdapRestriction;
use Illuminate\Http\Request;
use App\Role;
use App\RoleMapping;
use Illuminate\Support\Facades\Validator;
use App\Classes\Connector\LdapConnection;

class RoleMappingController extends Controller
{
    public function fetchDomainGroups()
    {
        try{
            $ldap = new LdapConnection(config('ldap.ldap_host'), request('ldapUsername'), request('ldapPassword'));
        }catch(\Exception $ex){
            return respond('LDAP Bağlantısı Kurulamadı', 201);
        }
        $query = request('query');

        list($size,$uglyGroups) = $ldap->search("(&(sAMAccountName=$query*)(sAMAccountType=268435456))",[
            "attributeList" => [
                "distinguishedname"
            ]
        ]);
        unset($uglyGroups["count"]);
    
        $domainGroups = array_map(function($value){
            return([
                "id" => $value['distinguishedname'][0],
                "text" => $value['distinguishedname'][0],
            ]);
        }, $uglyGroups);

        return respond($domainGroups);
    }

    public function fetchDomainUsers()
    {
        try{
            $ldap = new LdapConnection(config('ldap.ldap_host'), request('ldapUsername'), request('ldapPassword'));
        }catch(\Exception $ex){
            return respond('LDAP Bağlantısı Kurulamadı', 201);
        }
        $query = request('query');

        list($size,$uglyUsers) = $ldap->search("(&(sAMAccountName=$query*)(sAMAccountType=805306368))",[
            "attributeList" => [
                "samaccountname"
            ]
        ]);
        unset($uglyUsers["count"]);
    
        $domainUsers = array_map(function($value){
            return([
                "id" => $value['samaccountname'][0],
                "text" => $value['samaccountname'][0],
            ]);
        }, $uglyUsers);
        return respond($domainUsers);
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
