<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Role;
use App\RoleUser;
use App\Permission;
use App\Extension;
use App\RoleMapping;
use App\Server;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function one(Role $role)
    {
        return view('settings.role', [
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
        ]);
    }

    public function list()
    {
        return view('table', [
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

    public function remove()
    {
        Permission::where('morph_id', request('role_id'))->delete();

        RoleUser::where("role_id", request('role_id'))->delete();

        RoleMapping::where("role_id", request('role_id'))->delete();

        Role::where("id", request('role_id'))->delete();

        return respond("Rol grubu başarıyla silindi.");
    }

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

    public function removeRolesToUser()
    {
        RoleUser::whereIn("role_id", json_decode(request('ids')))
            ->where([
                "user_id" => request('user_id'),
            ])
            ->delete();
        return respond(__("Rol grupları başarıyla silindi."), 200);
    }

    public function removeRoleUsers()
    {
        RoleUser::whereIn("user_id", json_decode(request('users')))
            ->where([
                "role_id" => request('role_id'),
            ])
            ->delete();
        return respond(__("Grup üyeleri başarıyla silindi."), 200);
    }

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
                        "name" => "Sunucu Günlük Kayıtlarını Görüntüleme"
                    ],
                    [
                        "id" => "add_server",
                        "name" => "Sunucu Ekleme"
                    ],
                    [
                        "id" => "server_services",
                        "name" => "Sunucu Servislerini Görüntüleme"
                    ]
                ];
                $title = ["*hidden*", "İsim"];
                $display = ["id:id", "name"];
                break;
            default:
                abort(504, "Tip Bulunamadı");
        }
        return view('l.table', [
            "value" => $data,
            "title" => $title,
            "display" => $display,
        ]);
    }

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

    public function removeFromList()
    {
        foreach (json_decode(request('ids'), true) as $id) {
            Permission::revoke(request('role_id'), request('type'), "id", $id);
        }
        return respond(__("Başarılı"), 200);
    }

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

    public function removeFunctionPermissions()
    {
        foreach (explode(",", request('functions')) as $function) {
            Permission::find($function)->delete();
        }
        return respond(__("Başarılı"), 200);
    }
}
