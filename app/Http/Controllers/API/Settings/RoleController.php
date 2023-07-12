<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Server;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('updated_at', 'DESC')->get();

        return response()->json($roles);
    }

    public function show(Request $request)
    {
        $role = Role::where('id', $request->role_id)->first();
        $role->counts = [
            'users' => $role->users->count(),
            'servers' => $role->permissions->where('type', 'server')->count(),
            'extensions' => $role->permissions->where('type', 'extension')->count(),
            'liman' => $role->permissions->where('type', 'liman')->count(),
            'functions' => $role->permissions->where('type', 'function')->count(),
            'variables' => $role->permissions->where('type', 'variable')->count(),
        ];

        return response()->json($role);
    }

    public function create(Request $request)
    {
        $role = Role::create([
            'name' => $request->name,
        ]);

        return response()->json($role);
    }

    public function delete(Request $request)
    {
        Role::where('id', $request->role_id)->delete();

        return response()->json('Rol başarıyla silindi.');
    }

    public function users(Request $request)
    {
        $users = User::all();
        $selected = Role::where('id', $request->role_id)->first()->users;

        return response()->json([
            'users' => $users,
            'selected' => $selected,
        ]);
    }

    public function setUsers(Request $request)
    {
        // Delete all users on role
        RoleUser::where('role_id', $request->role_id)->delete();

        // Add new users
        foreach ($request->users as $user) {
            RoleUser::firstOrCreate([
                'user_id' => $user,
                'role_id' => $request->role_id,
            ]);
        }

        return response()->json('Kullanıcılar başarıyla güncellendi.');
    }

    public function servers(Request $request)
    {
        $servers = Server::all();
        $selected = Server::find(
            Role::find($request->role_id)
                ->permissions
                ->where('type', 'server')
                ->pluck('value')
                ->toArray()
        );

        return response()->json([
            'servers' => $servers,
            'selected' => $selected,
        ]);
    }

    public function setServers(Request $request)
    {
        Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'server',
            'key' => 'id',
        ])->delete();

        foreach ($request->servers as $server) {
            Permission::grant(
                $request->role_id,
                'server',
                'id',
                $server,
                null,
                'roles'
            );
        }

        return response()->json('Sunucular başarıyla güncellendi.');
    }

    public function extensions(Request $request)
    {
        $extensions = Extension::all();
        $selected = Extension::find(
            Role::find($request->role_id)
                ->permissions
                ->where('type', 'extension')
                ->pluck('value')
                ->toArray()
        );

        return response()->json([
            'extensions' => $extensions,
            'selected' => $selected,
        ]);
    }

    public function setExtensions(Request $request)
    {
        Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'extension',
            'key' => 'id',
        ])->delete();

        foreach ($request->extensions as $extension) {
            Permission::grant(
                $request->role_id,
                'extension',
                'id',
                $extension,
                null,
                'roles'
            );
        }

        return response()->json('Eklentiler başarıyla güncellendi.');
    }

    public function limanPermissions(Request $request)
    {
        $permissions = [
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
        $selected = getLimanPermissions($request->role_id);

        return response()->json([
            'permissions' => $permissions,
            'selected' => $selected,
        ]);
    }

    public function setLimanPermissions(Request $request)
    {
        Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'liman',
        ])->delete();

        foreach ($request->limanPermissions as $permission) {
            Permission::grant(
                $request->role_id,
                'liman',
                'id',
                $permission,
                null,
                'roles'
            );
        }

        return response()->json('Liman yetkileri başarıyla güncellendi.');
    }

    public function functions(Request $request)
    {
        $display_names = Extension::all()->map(function ($item) {
            return [
                'name' => strtolower($item->name),
                'display_name' => $item->display_name,
            ];
        })->collect();

        $permissions = Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'function',
        ])->get()->map(function ($item) use ($display_names) {
            $functions = getExtensionFunctions($item->value);
            if ($functions != []) {
                $function = $functions->where('name', $item->extra)->first();
            } else {
                return $item;
            }

            $item->display_name = $display_names->filter(function ($value) use ($item) {
                return $value['name'] == $item->value;
            })->first()['display_name'] ?? $item->value;

            $item->description = isset($function['description']) ? extensionTranslate($function['description'], $item->value) : '';

            return $item;
        });

        return response()->json($permissions);
    }

    /**
     * Get extension functions as of human readable format
     *
     * @return JsonResponse
     */
    public function getExtensionFunctions(Request $request)
    {
        $ext = Extension::find($request->extension_id);
        $extension = json_decode(
            file_get_contents(
                '/liman/extensions/'.
                strtolower((string) $ext->name).
                DIRECTORY_SEPARATOR.
                'db.json'
            ),
            true
        );
        $functions = array_key_exists('functions', $extension)
            ? $extension['functions']
            : [];
        $lang = session('locale') ?? 'tr';
        $file =
            '/liman/extensions/'.
            strtolower((string) $ext->name).
            '/lang/'.
            $lang.
            '.json';

        //Translate Items.
        $cleanFunctions = [];
        if (is_file($file)) {
            $json = json_decode(file_get_contents($file), true);
            for ($i = 0; $i < count($functions); $i++) {
                if (
                    array_key_exists('isActive', $functions[$i]) &&
                    $functions[$i]['isActive'] == 'false'
                ) {
                    continue;
                }
                $description = array_key_exists(
                    $functions[$i]['description'],
                    $json
                )
                    ? $json[$functions[$i]['description']]
                    : $functions[$i]['description'];
                array_push($cleanFunctions, [
                    'name' => $functions[$i]['name'],
                    'description' => $description,
                ]);
            }
        }

        return response()->json($cleanFunctions);
    }

    public function setFunctions(Request $request)
    {
        $extension = Extension::find($request->extension_id);

        foreach ($request->functions as $function) {
            Permission::grant(
                $request->role_id,
                'function',
                'name',
                strtolower((string) $extension->name),
                $function,
                'roles'
            );
        }

        return response()->json('Fonksiyonlar başarıyla güncellendi.');
    }

    public function deleteFunctions(Request $request)
    {
        Permission::whereIn('id', $request->permission_ids)->delete();

        return response()->json('Fonksiyonlar başarıyla silindi.');
    }

    public function variables(Request $request)
    {
        $permissions = Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'variable',
        ])->get();

        return response()->json($permissions);
    }

    /**
     * Add variable
     *
     * @return JsonResponse|Response
     */
    public function setVariables(Request $request)
    {
        Permission::grant(
            $request->role_id,
            'variable',
            $request->key,
            $request->value,
            null,
            'roles'
        );

        return response()->json('Veri başarıyla eklendi!');
    }

    /**
     * Remove variable
     *
     * @return JsonResponse|Response
     */
    public function deleteVariables(Request $request)
    {
        Permission::whereIn('id', $request->permission_ids)->delete();

        return response()->json('Fonksiyonlar başarıyla silindi.');
    }
}
