<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AuthLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

/**
 * User Controller
 */
class UserController extends Controller
{
    /**
     * Get all users
     *
     * @return Collection
     */
    public function index()
    {
        return User::orderBy('last_login_at', 'desc')
            ->get();
    }

    /**
     * Creates a new user
     *
     * @return JsonResponse|Response
     */
    public function create(Request $request)
    {
        validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
            ],
            'password' => ['string', 'min:8'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => strtolower((string) $request->email),
            'password' => Hash::make((string) $request->password),
            'status' => $request->status,
            'forceChange' => true,
        ];

        if ($request->username) {
            $data['username'] = $request->username;
        }

        $user = User::create($data);

        AuditLog::write(
            'user',
            'create',
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            "USER_CREATED"
        );

        return response()->json([
            'message' => 'Kullanıcı başarıyla oluşturuldu.'
        ]);
    }

    /**
     * Get all roles list with selected ones
     * 
     * @return JsonResponse
     */
    public function roles(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $roles = $user->roles->pluck('id')->toArray();

        return response()->json(Role::all()->map(function ($role) use ($roles) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'selected' => in_array($role->id, $roles),
            ];
        }));
    }

    /**
     * Update user information
     *
     * @return JsonResponse|Response
     */
    public function update(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'session_time' => ['required', 'integer', 'min:15', 'max:999999'],
        ]);

        $session_time = env('JWT_TTL', 120);
        if ($request->session_time == $session_time) {
            $session_time = -1;
        } else {
            $session_time = $request->session_time;
        }

        $data = [
            'name' => $request->name,
            'status' => $request->status,
            'session_time' => $session_time,
        ];

        if ($user->auth_type !== 'ldap') {
            $data['email'] = strtolower((string) $request->email);     
            
            if ($request->username) {
                $data['username'] = $request->username;
            }
    
            if ($request->password) {
                $data['password'] = Hash::make((string) $request->password);
                if (auth('api')->user()->id !== $user->id) {
                    $data['forceChange'] = true;
                }
            }
        }

        $user->update($data);

        $rolesToSync = [];
        foreach ($request->roles as $role) {
            $rolesToSync[$role] = [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $user->roles()->sync($rolesToSync);

        AuditLog::write(
            'user',
            'update',
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            "USER_UPDATED"
        );

        return response()->json([
            'message' => 'Kullanıcı başarıyla güncellendi.'
        ]);
    }

    /**
     * Delete user from system
     *
     * @return JsonResponse|Response
     */
    public function delete(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();

        // If user type is not local, return error
        if ($user->auth_type !== 'local') {
            return response()->json([
                'message' => 'LDAP kullanıcıları silinemez.'
            ], 400);
        }

        // Delete Permissions
        Permission::where('morph_id', $request->user_id)->delete();

        // Delete user roles
        RoleUser::where('user_id', $request->user_id)->delete();

        // Delete User
        $user->delete();

        AuditLog::write(
            'user',
            'delete',
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
            "USER_DELETED"
        );

        return response()->json([
            'message' => 'Kullanıcı başarıyla silindi.'
        ]);
    }

    /**
     * Authentication logs
     * 
     * @return JsonResponse
     */
    public function authLogs(Request $request)
    {
        if ($request->user_id) {
            return User::find($request->user_id)
                ->authLogs()
                ->orderBy('created_at', 'desc')
                ->take(500)
                ->get();
        }

        return AuthLog::orderBy('created_at', 'desc')
            ->with('user')
            ->take(500)
            ->get();
    }
}
