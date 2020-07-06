<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Permission;
use App\Models\Extension;
use App\Models\RoleMapping;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * @api {get} /rol/{role} Get Role
     * @apiName Get Role
     * @apiGroup Role
     *
     * @apiParam {String} role ID of the Role
     *
     * @apiSuccess {Array} role Role details.
     * @apiSuccess {Array} servers Role' Servers.
     * @apiSuccess {Array} extensions Role' Extension.
     * @apiSuccess {Array} limanPermissions Role' Liman Permissions.
     */
    public function one(Role $role)
    {
        $limanPermissions = getLimanPermissions($role->id);
        return magicView('settings.role', [
            "role" => $role,
            "servers" => Server::find(
                $role->permissions
                    ->where('type', 'server')
                    ->pluck('value')
                    ->toArray()
            ),
            "extensions" => Extension::find(
                $role->permissions
                    ->where('type', 'extension')
                    ->pluck('value')
                    ->toArray()
            ),
            "limanPermissions" => $limanPermissions,
        ]);
    }

    /**
     * @api {post} /rol/liste All Roles List
     * @apiName All Roles List
     * @apiGroup Role
     *
     * @apiParam {String} role ID of the Role
     *
     * @apiSuccess {Array} value Role List.
     */
    public function list()
    {
        return magicView('table', [
            "value" => Role::all(),
            "title" => ["Rol Grubu Adı", "*hidden*"],
            "display" => ["name", "id:role_id"],
            "menu" => [
                "Sil" => [
                    "target" => "deleteRole",
                    "icon" => " context-menu-icon-delete",
                ],
            ],
            "onclick" => "roleDetails",
        ]);
    }

    /**
     * @api {post} /rol/ekle Add New Role
     * @apiName Add New Role
     * @apiGroup Role
     *
     * @apiParam {String} name Role Name
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function add()
    {
        hook('role_group_add_attempt', [
            "request" => request()->all(),
        ]);

        $flag = Validator::make(request()->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
        ]);

        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Lütfen geçerli veri giriniz.", 201);
        }

        $role = Role::create([
            "name" => request('name'),
        ]);

        hook('role_group_add_successful', [
            "role" => $role,
        ]);

        return respond("Rol grubu başarıyla eklendi.");
    }

    /**
     * @api {post} /rol/sil Remove Role
     * @apiName Remove Role
     * @apiGroup Role
     *
     * @apiParam {String} role_id Id of the role
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function remove()
    {
        Permission::where('morph_id', request('role_id'))->delete();

        RoleUser::where("role_id", request('role_id'))->delete();

        RoleMapping::where("role_id", request('role_id'))->delete();

        Role::where("id", request('role_id'))->delete();

        return respond("Rol grubu başarıyla silindi.");
    }

    /**
     * @api {post} /rol/kullanici_ekle Add Users to Role
     * @apiName Add Users to Role
     * @apiGroup Role
     *
     * @apiParam {String} role_id Id of the role
     * @apiParam {Array} users Only ids of target users.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function addRoleUsers()
    {
        foreach (json_decode(request('users')) as $user) {
            RoleUser::firstOrCreate([
                "user_id" => $user,
                "role_id" => request('role_id'),
            ]);
        }
        return respond(__("Grup üyeleri başarıyla eklendi."), 200);
    }

    /**
     * @api {post} /rol/rol_ekle Add Roles to User
     * @apiName Add Roles to User
     * @apiGroup Role
     *
     * @apiParam {String} user_id Id of the user.
     * @apiParam {Array} ids Only ids of target roles.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function addRolesToUser()
    {
        foreach (json_decode(request('ids')) as $role) {
            RoleUser::firstOrCreate([
                "user_id" => request('user_id'),
                "role_id" => $role,
            ]);
        }
        return respond(__("Rol grupları kullanıcıya başarıyla eklendi."), 200);
    }

    /**
     * @api {post} /rol/rol_sil Remove Roles from User
     * @apiName Remove Roles from User
     * @apiGroup Role
     *
     * @apiParam {String} user_id Id of the user.
     * @apiParam {Array} ids Only ids of target roles.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function removeRolesToUser()
    {
        RoleUser::whereIn("role_id", json_decode(request('ids')))
            ->where([
                "user_id" => request('user_id'),
            ])
            ->delete();
        return respond(__("Rol grupları başarıyla silindi."), 200);
    }

    /**
     * @api {post} /rol/kullanici_sil Remove Users from Role
     * @apiName Remove Users from Role
     * @apiGroup Role
     *
     * @apiParam {String} role_id Id of the role.
     * @apiParam {Array} users Only ids of target users.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function removeRoleUsers()
    {
        RoleUser::whereIn("user_id", json_decode(request('users')))
            ->where([
                "role_id" => request('role_id'),
            ])
            ->delete();
        return respond(__("Grup üyeleri başarıyla silindi."), 200);
    }

    /**
     * @api {post} /rol/yetki_listesi Get Permissions List for Role
     * @apiName Get Permissions List for Role
     * @apiGroup Role
     *
     * @apiParam {String} type server, extension, liman.
     * @apiParam {Array} users Only ids of target users.
     *
     * @apiSuccess {Array} value Results Array according to type.
     */
    public function getList()
    {
        $role = Role::find(request('role_id'));
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')) {
            case "server":
                $data = Server::whereNotIn(
                    'id',
                    $role->permissions
                        ->where('type', 'server')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ["*hidden*", "İsim", "Türü", "İp Adresi"];
                $display = ["id:id", "name", "type", "ip_address"];
                break;
            case "extension":
                $data = Extension::whereNotIn(
                    'id',
                    $role->permissions
                        ->where('type', 'extension')
                        ->pluck('value')
                        ->toArray()
                )->get();
                $title = ["*hidden*", "İsim"];
                $display = ["id:id", "name"];
                break;
            case "liman":
                $data = [
                    [
                        "id" => "view_logs",
                        "name" => "Sunucu Günlük Kayıtlarını Görüntüleme",
                    ],
                    [
                        "id" => "add_server",
                        "name" => "Sunucu Ekleme",
                    ],
                    [
                        "id" => "server_services",
                        "name" => "Sunucu Servislerini Görüntüleme",
                    ],
                ];
                $title = ["*hidden*", "İsim"];
                $display = ["id:id", "name"];
                break;
            default:
                abort(504, "Tip Bulunamadı");
        }
        return magicView('l.table', [
            "value" => $data,
            "title" => $title,
            "display" => $display,
        ]);
    }

    /**
     * @api {post} /rol/yetki_listesi/ekle Add Permission to Role
     * @apiName Add Permission to Role
     * @apiGroup Role
     *
     * @apiParam {String} type server, extension, liman.
     * @apiParam {String} role_id ID of the Role.
     * @apiParam {Array} ids Ids of the type.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function addList()
    {
        foreach (json_decode(request('ids'), true) as $id) {
            Permission::grant(
                request('role_id'),
                request('type'),
                "id",
                $id,
                null,
                "roles"
            );
        }
        return respond(__("Başarılı"), 200);
    }

    /**
     * @api {post} /rol/yetki_listesi/sil Remove Permission from Role
     * @apiName Remove Permission from Role
     * @apiGroup Role
     *
     * @apiParam {String} type server, extension, liman.
     * @apiParam {String} role_id ID of the Role.
     * @apiParam {Array} ids Ids of the type.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function removeFromList()
    {
        foreach (json_decode(request('ids'), true) as $id) {
            Permission::revoke(request('role_id'), request('type'), "id", $id);
        }
        return respond(__("Başarılı"), 200);
    }

    /**
     * @api {post} /rol/yetki_listesi/fonksiyon_ekle Add Function Permissions to Role
     * @apiName Add Function Permissions to Role
     * @apiGroup Role
     *
     * @apiParam {String} role_id ID of the Role.
     * @apiParam {Array} functions Name of the functions.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function addFunctionPermissions()
    {
        foreach (explode(",", request('functions')) as $function) {
            Permission::grant(
                request('role_id'),
                "function",
                "name",
                strtolower(extension()->name),
                $function,
                "roles"
            );
        }
        return respond(__("Başarılı"), 200);
    }

    /**
     * @api {post} /rol/yetki_listesi/fonksiyon_sil Remove Function Permissions to Role
     * @apiName Remove Function Permissions to Role
     * @apiGroup Role
     *
     * @apiParam {Array} functions Object ids with , delimeter.
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function removeFunctionPermissions()
    {
        foreach (explode(",", request('functions')) as $function) {
            Permission::find($function)->delete();
        }
        return respond(__("Başarılı"), 200);
    }
}
