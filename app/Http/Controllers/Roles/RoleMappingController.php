<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\LdapRestriction;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleMapping;

class RoleMappingController extends Controller
{
    /**
     * @api {post} /rol/eslestirme_ekle Add Role Mapping
     * @apiName Add Role Mapping
     * @apiGroup Role Mapping
     *
     * @apiParam {String} dn DN to map with Role.
     * @apiParam {String} role_id Target Role Id.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function add()
    {
        validate([
            'dn' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        RoleMapping::create([
            "dn" => request('dn'),
            "role_id" => request('role_id'),
            "group_id" => md5(request('dn')),
        ]);

        return respond("Rol eşleştirmesi başarıyla eklendi.");
    }

    /**
     * @api {post} /rol/eslestirme_sil Remove Role Mapping
     * @apiName Remove Role Mapping
     * @apiGroup Role Mapping
     *
     * @apiParam {String} role_mapping_id Target Role Mapping Id.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function delete()
    {
        validate([
            'role_mapping_id' => ['required', 'exists:role_mappings,id'],
        ]);

        RoleMapping::find(request('role_mapping_id'))->delete();
        return respond("Rol eşleştirmesi başarıyla silindi.");
    }
}
