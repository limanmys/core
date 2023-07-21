<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleMapping;
use App\Models\RoleUser;
use App\Models\Server;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Role Controller
 *
 * @extends Controller
 */
class RoleController extends Controller
{

    /**
     * Get role view
     *
     * @param Role $role
     * @return JsonResponse|Response
     */
    public function one(Role $role)
    {
        $limanPermissions = getLimanPermissions($role->id);

        return magicView('settings.role', [
            'role' => $role,
            'servers' => Server::find(
                $role->permissions
                    ->where('type', 'server')
                    ->pluck('value')
                    ->toArray()
            ),
            'extensions' => Extension::find(
                $role->permissions
                    ->where('type', 'extension')
                    ->pluck('value')
                    ->toArray()
            ),
            'limanPermissions' => $limanPermissions,
            'variablesPermissions' => $role->permissions->where('type', 'variable'),
        ]);
    }

    /**
     * Get role list
     *
     * @return JsonResponse|Response
     */
    public function list()
    {
        return magicView('table', [
            'value' => Role::all(),
            'title' => ['Rol Grubu Adı', '*hidden*'],
            'display' => ['name', 'id:role_id'],
            'menu' => [
                'Yeniden Adlandır' => [
                    'target' => 'editRole',
                    'icon' => ' context-menu-icon-edit',
                ],
                'Sil' => [
                    'target' => 'deleteRole',
                    'icon' => ' context-menu-icon-delete',
                ],
            ],
            'onclick' => 'roleDetails',
        ]);
    }


    /**
     * Create new role
     *
     * @return JsonResponse|Response
     */
    public function add()
    {
        validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
        ]);

        $role = Role::create([
            'name' => request('name'),
        ]);

        return respond('Rol grubu başarıyla eklendi.');
    }

    /**
     * Rename role
     *
     * @return JsonResponse|Response
     */
    public function rename()
    {
        $count = Role::where('id', request('role_id'))->update([
            'name' => request('name'),
        ]);

        if ($count) {
            return respond('Rol grubu başarıyla düzenlendi.');
        } else {
            return respond('Rol grubu düzenlenemedi!', 201);
        }
    }

    /**
     * Delete role
     *
     * @return JsonResponse|Response
     */
    public function remove()
    {
        Permission::where('morph_id', request('role_id'))->delete();

        RoleUser::where('role_id', request('role_id'))->delete();

        RoleMapping::where('role_id', request('role_id'))->delete();

        Role::where('id', request('role_id'))->delete();

        return respond('Rol grubu başarıyla silindi.');
    }

    /**
     * Assign users to role
     *
     * @return JsonResponse|Response
     */
    public function addRoleUsers()
    {
        foreach (json_decode((string) request('users')) as $user) {
            RoleUser::firstOrCreate([
                'user_id' => User::where('email', $user)->first()->id,
                'role_id' => request('role_id'),
            ]);
        }

        return respond(__('Grup üyeleri başarıyla eklendi.'), 200);
    }


    /**
     * Assign roles to user
     *
     * @return JsonResponse|Response
     */
    public function addRolesToUser()
    {
        foreach (json_decode((string) request('ids')) as $role) {
            RoleUser::firstOrCreate([
                'user_id' => request('user_id'),
                'role_id' => $role,
            ]);
        }

        return respond(__('Rol grupları kullanıcıya başarıyla eklendi.'), 200);
    }


    /**
     * Unassign roles from user
     *
     * @return JsonResponse|Response
     */
    public function removeRolesToUser()
    {
        $ids = json_decode((string) request('ids'));
        if (count($ids) == 0) {
            return respond(__('Rol grubu silinemedi.'), 201);
        }

        RoleUser::whereIn('role_id', $ids)
            ->where([
                'user_id' => request('user_id'),
            ])
            ->delete();

        return respond(__('Rol grupları başarıyla silindi.'), 200);
    }


    /**
     * Unassign users from role
     *
     * @return JsonResponse|Response
     */
    public function removeRoleUsers()
    {
        RoleUser::whereIn('user_id', json_decode((string) request('users')))
            ->where([
                'role_id' => request('role_id'),
            ])
            ->delete();

        return respond(__('Grup üyeleri başarıyla silindi.'), 200);
    }


    /**
     * Get role permission list
     *
     * @return JsonResponse|Response
     */
    public function getList()
    {
        $role = Role::find(request('role_id'));
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')) {
            case 'server':
                $data = Server::whereNotIn(
                    'id',
                    $role->permissions
                        ->where('type', 'server')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ['*hidden*', 'İsim', 'Türü', 'İp Adresi'];
                $display = ['id:id', 'name', 'type', 'ip_address'];
                break;
            case 'extension':
                $data = Extension::whereNotIn(
                    'id',
                    $role->permissions
                        ->where('type', 'extension')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ['*hidden*', 'İsim'];
                $display = ['id:id', 'name'];
                break;
            case 'liman':
                $usedPermissions = Permission::where([
                    'type' => 'liman',
                    'morph_id' => request('role_id'),
                ])
                    ->get()
                    ->groupBy('value');

                $data = [
                    [
                        'id' => 'view_logs',
                        'name' => 'Sunucu Günlük Kayıtlarını Görüntüleme',
                    ],
                    [
                        'id' => 'add_server',
                        'name' => 'Sunucu Ekleme',
                    ],
                    [
                        'id' => 'server_services',
                        'name' => 'Sunucu Servislerini Görüntüleme',
                    ],
                    [
                        'id' => 'server_details',
                        'name' => 'Sunucu Detaylarını Görüntüleme',
                    ],
                    [
                        'id' => 'update_server',
                        'name' => 'Sunucu Detaylarını Güncelleme',
                    ],
                ];

                foreach ($usedPermissions as $permission => $values) {
                    foreach ($data as $k => $v) {
                        if ($v['id'] == $permission) {
                            unset($data[$k]);
                        }
                    }
                }

                $title = ['*hidden*', 'İsim'];
                $display = ['id:id', 'name'];
                break;
            default:
                abort(504, 'Tip Bulunamadı');
        }

        return magicView('table', [
            'value' => $data,
            'title' => $title,
            'display' => $display,
        ]);
    }

    /**
     * Add permission to role
     *
     * @return JsonResponse|Response
     */
    public function addList()
    {
        foreach (json_decode((string) request('ids'), true) as $id) {
            Permission::grant(
                request('role_id'),
                request('type'),
                'id',
                $id,
                null,
                'roles'
            );
        }

        return respond(__('Başarılı'), 200);
    }

    /**
     * Remove permission from role
     *
     * @return JsonResponse|Response
     */
    public function removeFromList()
    {
        foreach (json_decode((string) request('ids'), true) as $id) {
            Permission::revoke(request('role_id'), request('type'), 'id', $id);
        }

        return respond(__('Başarılı'), 200);
    }

    /**
     * Add function permissions to role
     *
     * @return JsonResponse|Response
     */
    public function addFunctionPermissions()
    {
        foreach (explode(',', (string) request('functions')) as $function) {
            Permission::grant(
                request('role_id'),
                'function',
                'name',
                strtolower((string) extension()->name),
                $function,
                'roles'
            );
        }

        return respond(__('Başarılı'), 200);
    }

    /**
     * Remove function permission from role
     *
     * @return JsonResponse|Response
     */
    public function removeFunctionPermissions()
    {
        foreach (explode(',', (string) request('functions')) as $function) {
            Permission::find($function)->delete();
        }

        return respond(__('Başarılı'), 200);
    }
}
