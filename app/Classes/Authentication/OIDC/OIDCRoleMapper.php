<?php

namespace App\Classes\Authentication\OIDC;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * OIDC permission claim'lerini Liman rollerine eşler.
 *
 * auto=true ile atanmış rolleri önce temizler, ardından OIDC'den gelen
 * permission isimleriyle eşen rolleri auto=true ile atar. Böylece OIDC'den
 * kaldırılan bir rol kullanıcıdan da kalkar. Elle (auto=false) atanmış roller
 * dokunulmaz.
 */
class OIDCRoleMapper
{
    public function assignByPermissions(User $user, array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }

        try {
            $this->removeAutoRoles($user);

            $matchingRoles = Role::whereIn('name', $permissions)->get();

            if ($matchingRoles->isEmpty()) {
                Log::info('No matching roles found for user permissions', [
                    'user_id' => $user->id,
                    'permissions' => $permissions,
                ]);

                return;
            }

            $assignedRoles = [];
            foreach ($matchingRoles as $role) {
                if ($this->hasManualAssignment($user, $role)) {
                    Log::info('User already has manual role assignment, skipping auto-assignment', [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                    ]);

                    continue;
                }

                RoleUser::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'auto' => true,
                ]);

                $assignedRoles[] = $role->name;

                AuditLog::write(
                    'role',
                    'users',
                    [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'source' => 'oidc_permission_mapping',
                        'auto' => true,
                    ],
                    'ROLE_USERS',
                    [],
                    $user->id,
                );
            }

            if (! empty($assignedRoles)) {
                Log::info('User assigned to roles via OIDC permission mapping', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'assigned_roles' => $assignedRoles,
                    'permissions' => $permissions,
                    'auto' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to assign user to roles by permissions: '.$e->getMessage(), [
                'user_id' => $user->id,
                'permissions' => $permissions,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function removeAutoRoles(User $user): void
    {
        $removedAutoRoles = RoleUser::where('user_id', $user->id)
            ->where('auto', true)
            ->get();

        if ($removedAutoRoles->isEmpty()) {
            return;
        }

        $removedRoleNames = [];
        foreach ($removedAutoRoles as $roleUser) {
            $role = Role::find($roleUser->role_id);
            if ($role) {
                $removedRoleNames[] = $role->name;
            }
        }

        RoleUser::where('user_id', $user->id)
            ->where('auto', true)
            ->delete();

        Log::info('Removed auto-assigned roles from user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'removed_roles' => $removedRoleNames,
        ]);
    }

    private function hasManualAssignment(User $user, Role $role): bool
    {
        return RoleUser::where([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'auto' => false,
        ])->exists();
    }
}
