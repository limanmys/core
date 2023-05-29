<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\RoleMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Role Mapping Controller
 *
 * @extends Controller
 */
class RoleMappingController extends Controller
{
    /**
     * Add role mapping
     *
     * @return JsonResponse|Response
     */
    public function add()
    {
        validate([
            'dn' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        RoleMapping::create([
            'dn' => request('dn'),
            'role_id' => request('role_id'),
            'group_id' => md5((string) request('dn')),
        ]);

        return respond('Rol eşleştirmesi başarıyla eklendi.');
    }

    /**
     * Remove role mapping
     *
     * @return JsonResponse|Response
     */
    public function delete()
    {
        validate([
            'role_mapping_id' => ['required', 'exists:role_mappings,id'],
        ]);

        RoleMapping::find(request('role_mapping_id'))->delete();

        return respond('Rol eşleştirmesi başarıyla silindi.');
    }
}
