<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Role Controller
 *
 * Manages user role settings
 */
class RoleController extends Controller
{
    /**
     * Returns user roles
     *
     * @return mixed
     */
    public function index()
    {
        return Role::orderBy('updated_at', 'DESC')->get();
    }

    /**
     * Show a role
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            'views' => $role->permissions->where('type', 'view')->count(),
        ];

        return $role;
    }

    /**
     * Create a new role
     *
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        $role = Role::create([
            'name' => $request->name,
        ]);

        AuditLog::write(
            'role',
            'create',
            [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
            "ROLE_CREATE"
        );

        return response()->json($role, Response::HTTP_OK); 
    }

    /**
     * Delete role
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        $role = Role::where('id', $request->role_id)->first();
        $role->delete();

        AuditLog::write(
            'role',
            'delete',
            [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
            "ROLE_DELETE"
        );

        return response()->json([
            'message' => 'Rol başarıyla silindi.'
        ]);
    }

    /**
     * Return role users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request)
    {
        $users = User::all();
        $selected = Role::where('id', $request->role_id)->first()->users;

        return response()->json([
            'users' => $users,
            'selected' => $selected,
        ]);
    }

    /**
     * Set role users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setUsers(Request $request)
    {
        // Delete all users on role
        RoleUser::where('role_id', $request->role_id)->delete();

        // Add new users
        $role = Role::find($request->role_id);
        foreach ($request->users as $user) {
            RoleUser::firstOrCreate([
                'user_id' => $user,
                'role_id' => $request->role_id,
            ]);

            $user = User::find($user);
            AuditLog::write(
                'role',
                'users',
                [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ],
                "ROLE_USERS"
            );
        }

        return response()->json([
            'message' => 'Kullanıcılar başarıyla güncellendi.'
        ]);
    }

    /**
     * Return role servers
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Set role servers
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->servers ?? []),
                'type' => 'servers',
                'array' => $request->servers
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Sunucular başarıyla güncellendi.'
        ]);
    }

    /**
     * List role extensions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function extensions(Request $request)
    {
        $extensions = Extension::all();
        $roles = Role::find($request->role_id)
            ->permissions
            ->where('type', 'extension')
            ->pluck('value')
            ->toArray();
        $selected = $extensions->filter(function ($extension) use ($roles) {
            return in_array($extension->id, $roles);
        })->values();

        if ($request->variable_selector) {
            $selected = [
                ...$selected,
                [
                    'id' => 'default',
                    'name' => 'Default KV',
                ],
            ];
        }

        return response()->json([
            'extensions' => $extensions,
            'selected' => $selected,
        ]);
    }

    /**
     * Set role extensions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setExtensions(Request $request)
    {
        $extensions = Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'extension',
            'key' => 'id',
        ]);

        $role = Role::find($request->role_id);
        $oldExtensions = $role->permissions->where('type', 'extension')->pluck('value')->toArray();

        $extensions->delete();

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

        // Detect removed extensions and remove their functions
        $newExtensions = $request->extensions;
        $removedExtensions = array_diff($oldExtensions, $newExtensions);
        foreach ($removedExtensions as $extension) {
            Permission::where([
                'morph_id' => $request->role_id,
                'type' => 'function',
                'value' => Extension::find($extension)->name,
            ])->delete();
        }

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->extensions ?? []),
                'type' => 'extensions',
                'array' => $request->extensions
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Eklentiler başarıyla güncellendi.'
        ]);
    }

    /**
     * Get liman permissions
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Set Liman permissions
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->limanPermissions ?? []),
                'type' => 'liman_permissions',
                'array' => $request->limanPermissions
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Liman yetkileri başarıyla güncellendi.'
        ]);
    }

    /**
     * Get role extension functions
     *
     * @param Request $request
     * @return JsonResponse
     */
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
                $cleanFunctions[] = [
                    'name' => $functions[$i]['name'],
                    'description' => $description,
                ];
            }
        }

        return response()->json($cleanFunctions);
    }

    /**
     * Set role extension functions
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->functions ?? []),
                'type' => 'functions',
                'array' => $request->functions
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Fonksiyonlar başarıyla güncellendi.'
        ]);
    }

    /**
     * Delete extension role functions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFunctions(Request $request)
    {
        Permission::whereIn('id', $request->permission_ids)->delete();

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->permission_ids ?? []),
                'type' => 'functions',
                'array' => $request->permission_ids
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Fonksiyonlar başarıyla silindi.'
        ]);
    }

    /**
     * Get role variables
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function variables(Request $request)
    {
        $permissions = Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'variable',
        ])->get();

        return response()->json($permissions);
    }

    /**
     * Get extension variables from db.json
     */
    public function getExtensionVariables(Request $request)
    {
        $extension = Extension::find($request->extension_id);
        $extJson = getExtensionJson($extension->name);

        return response()->json($extJson['variables'] ?? []);
    }

    /**
     * Add variable
     *
     * @return JsonResponse|Response
     */
    public function setVariables(Request $request)
    {
        switch ($request->type) {
            case 'multiselect':
                $value = json_encode($request->value);
                break;
            default:
                $value = $request->value;
                break;
        }

        Permission::grant(
            $request->role_id,
            'variable',
            $request->key,
            $value,
            $request->type ?? 'string',
            'roles'
        );

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => 1,
                'type' => 'variables',
                'array' => [
                    'role_id' => $request->role_id,
                    'key' => $request->key,
                    'value' => $request->value,
                    'type' => $request->type ?? null,
                ]
            ],
            "ROLE_EDIT"
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

        return response()->json('Veri başarıyla silindi.');
    }

    /**
     * Role based system layout view settings
     */
    public function views(Request $request)
    {
        // View permission roles guide
        // Options:
        // - Sidebar: Shows server list / shows extension list that user has access
        // - Sidebar [string]: servers, extensions
        // - Sidebar [default_value]: servers
        // - Dashboard [string[]]: Server count, extension count, user count, version, most used extensions, most used servers
        // - Dashboard [string[]]: servers, extensions, users, version, most_used_extensions, most_used_servers 
        // - Dashboard [default_value]: servers, extensions, users, version, most_used_extensions, most_used_servers
        // If sidebar has extensions, dashboard must have extensions
        // If sidebar has servers, dashboard must have servers and extensions both

        $permissions = Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'view',
        ])->get();

        $viewSettings = [
            ...config('liman.default_views'),
        ];

        $permissions->map(function ($item) use (&$viewSettings) {
            try {
                $viewSettings[$item->key] = json_decode($item->value, false, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $viewSettings[$item->key] = $item->value;
            }
        });

        return response()->json($viewSettings);
    }

    /**
     * Set role views
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setViews(Request $request)
    {
        Permission::where([
            'morph_id' => $request->role_id,
            'type' => 'view',
        ])->delete();

        foreach ($request->views as $setting => $value) {
            if ($value === '') {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            Permission::grant(
                $request->role_id,
                'view',
                $setting,
                $value,
                null,
                'roles'
            );
        }

        AuditLog::write(
            'role',
            'edit',
            [
                'changed_count' => count($request->views ?? []),
                'type' => 'views',
                'array' => $request->views
            ],
            "ROLE_EDIT"
        );

        return response()->json([
            'message' => 'Görünüm ayarları güncellendi.'
        ]);
    }

    /**
     * Retrieve all roles
     *
     * @return JsonResponse|Response
     */
    public function detailedList()
    {
        $data = $this->generateRoleData();

        return response()->json($data);
    }

    /**
     * Export detailed role list as CSV format
     *
     * @return StreamedResponse
     */
    public function exportDetailedListAsCsv()
    {
        $data = $this->generateRoleData();

        $fileName = 'detailed_roles_list_'.date('d-m-Y_H-i-s').'.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename='.$fileName,
            'Content-Transfer-Encoding' => 'utf-8',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Kullanıcı Adı',
            'Rol Adı',
            'İzin Türü',
            'İzin Değeri',
        ];

        $writer = Writer::createFromPath("php://temp", "r+");
        $writer->insertOne($columns);

        foreach ($data as $row) {
            $writer->insertOne([
                $row['username'],
                $row['role_name'],
                $row['perm_type'],
                $row['perm_value'],
            ]);
        }

        $flushThreshold = 1000;
        $callback = function () use ($writer, $flushThreshold) {
            foreach ($writer->chunk(1024) as $offset => $chunk) {
                echo $chunk;
                if ($offset % $flushThreshold === 0) {
                    flush();
                }
            }
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate role data
     *
     * @return array
     */
    private function generateRoleData()
    {
        $data = [];

        $permissionData =
            Permission::with('morph')
                ->get()->each(function ($row) {
                    $row->details = $row->getRelatedObject();
                    if ($row->morph_type == 'roles') {
                        if (null !== $row->morph) {
                            $row->users = $row->morph->users()->get();
                        }
                    }
                });

        foreach ($permissionData as $row) {
            if (null === $row->morph || $row->details['value'] == '-' || $row->details['type'] == '-') {
                continue;
            }

            $insert = [
                'id' => $row->morph->id,
                'morph_type' => $row->morph_type,
                'perm_type' => $row->details['type'],
                'perm_value' => $row->details['value'],
            ];

            if ($row->morph_type == 'users') {
                $data[] = array_merge($insert, [
                    'username' => $row->morph->name,
                    'role_name' => __('Rol yok'),
                ]);
            } elseif ($row->morph_type == 'roles') {
                foreach ($row->users as $user) {
                    $data[] = array_merge($insert, [
                        'username' => $user->name,
                        'role_name' => $row->morph->name,
                    ]);
                }
            }
        }

        return $data;
    }
}
