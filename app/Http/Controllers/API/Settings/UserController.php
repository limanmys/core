<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RoleUser;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Creates a new user
     *
     * @return JsonResponse|Response
     */
    public function create()
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
            'name' => request('name'),
            'email' => strtolower((string) request('email')),
            'password' => Hash::make((string) request('password')),
            'status' => request('status'),
            'forceChange' => true,
        ];

        if (request('username')) {
            $data['username'] = request('username');
        }

        User::create($data);
        return response()->json(
            'Kullanıcı başarıyla oluşturuldu.'
        );
    }

    /**
     * Delete user from system
     *
     * @return JsonResponse|Response
     */
    public function delete()
    {
        // Delete Permissions
        Permission::where('morph_id', request('user_id'))->delete();

        //Delete user roles
        RoleUser::where('user_id', request('user_id'))->delete();

        // Delete User
        User::where('id', request('user_id'))->delete();

        return response()->json('Kullanıcı başarıyla silindi.');
    }
}
