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
    public function add()
    {
        $flag = Validator::make(request()->all(), [
            'dn' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Lütfen geçerli veri giriniz.", 201);
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

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Lütfen geçerli veri giriniz.", 201);
        }

        RoleMapping::find(request('role_mapping_id'))->delete();
        return respond("Rol eşleştirmesi başarıyla silindi.");
    }
}
