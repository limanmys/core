<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CurrentUserDetailsController extends Controller
{
    /**
     * Return authorization details for the authenticated user only.
     */
    public function __invoke(): JsonResponse
    {
        $user = auth('api')->user();

        $roleAssignments = $user->roles()
            ->withPivot(['type', 'auto'])
            ->with('permissions')
            ->orderBy('roles.name')
            ->get();

        $roles = $roleAssignments
            ->groupBy('id')
            ->map(function (Collection $assignments): array {
                /** @var Role $role */
                $role = $assignments->first();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'assignments' => $assignments
                        ->map(fn (Role $assignedRole): array => [
                            'type' => $assignedRole->pivot->type,
                            'automatic' => (bool) $assignedRole->pivot->auto,
                        ])
                        ->unique(fn (array $assignment): string => serialize($assignment))
                        ->values(),
                    'permissions' => $this->serializePermissions($role->permissions),
                ];
            })
            ->values();

        $directPermissions = $this->serializePermissions(
            $user->permissions()->get()
        );

        $assignedPermissions = $roles
            ->flatMap(fn (array $role) => $role['permissions'])
            ->merge($directPermissions)
            ->unique(fn (array $permission): string => $this->permissionKey($permission))
            ->sortBy(fn (array $permission): string => $this->permissionKey($permission))
            ->values();

        $externalRoles = $this->externalRoles($user->id, $user->auth_type);

        return response()
            ->json([
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'auth_type' => $user->auth_type,
                'locale' => $user->locale,
                'access' => [
                    'is_admin' => $user->isAdmin(),
                    'mode' => $user->isAdmin() ? 'unrestricted' : 'explicit',
                    'role_count' => $roles->count(),
                    'external_role_count' => $externalRoles->count(),
                    'assigned_permission_count' => $assignedPermissions->count(),
                ],
                'roles' => $roles,
                'external_roles' => $externalRoles,
                'direct_permissions' => $directPermissions,
                'assigned_permissions' => $assignedPermissions,
            ])
            ->header('Cache-Control', 'private, no-store')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Serialize only the permission attributes that are useful to the user.
     *
     * Internal ownership, audit and timestamp fields are intentionally omitted.
     *
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<int, array{type: ?string, key: ?string, value: ?string, extra: ?string}>
     */
    private function serializePermissions(Collection $permissions): Collection
    {
        return $permissions
            ->map(fn (Permission $permission): array => [
                'type' => $permission->type,
                'key' => $permission->key,
                'value' => $permission->value,
                'extra' => $permission->extra,
            ])
            ->sortBy(fn (array $permission): string => $this->permissionKey($permission))
            ->values();
    }

    /**
     * Build a stable key for sorting and de-duplicating permission records.
     *
     * @param  array{type: ?string, key: ?string, value: ?string, extra: ?string}  $permission
     */
    private function permissionKey(array $permission): string
    {
        return serialize([
            $permission['type'],
            $permission['key'],
            $permission['value'],
            $permission['extra'],
        ]);
    }

    /**
     * Return Keycloak realm roles used by Permission::can for extra matching.
     *
     * These are kept separate from persisted Liman roles because they have
     * different authorization semantics.
     *
     * @return Collection<int, string>
     */
    private function externalRoles(string $userId, ?string $authType): Collection
    {
        if ($authType !== 'keycloak') {
            return collect();
        }

        $cachedRoles = Cache::get(sprintf('kc_roles:%s', $userId));
        $roles = is_string($cachedRoles)
            ? json_decode($cachedRoles, true)
            : $cachedRoles;

        if (! is_array($roles)) {
            return collect();
        }

        return collect($roles)
            ->filter(fn ($role): bool => is_string($role) && $role !== '')
            ->unique()
            ->sort()
            ->values();
    }
}
