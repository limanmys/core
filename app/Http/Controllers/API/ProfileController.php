<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Profile Controller
 *
 * Manages user controls
 */
class ProfileController extends Controller
{
    public function getInformation(Request $request)
    {
        return response()->json([
            'user' => auth()->user()
        ]);
    }

    public function setInformation(Request $request)
    {
        if (auth('api')->user()->auth_type == 'ldap' || auth('api')->user()->auth_type == 'keycloak') {
            return response()->json([
                'message' => 'Bu işlem için yetkiniz bulunmamaktadır.',
            ], 403);
        }

        if ($request->password) {
            if (
                ! auth()->attempt([
                    'email' => $user->email,
                    'password' => $request->old_password,
                ])
            ) {
                return response()->json([
                    'password' => 'Eski şifreniz yanlış.',
                ]);
            }
    
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth('api')->user()->id,
        ]);

        $user = User::find(auth('api')->user()->id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'Bilgiler başarıyla güncellendi.',
            'user' => $user,
        ]);
    }

    public function authLogs(Request $request)
    {
        return auth()->user()
            ->authLogs()
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
    }

    public function setLocale(Request $request)
    {
        validate([
            'locale' => 'required|string|in:tr,en,de',
        ]);

        $user = User::find(auth('api')->user()->id);
        $user->update([
            'locale' => $request->locale,
        ]);

        return response()->json([
            'message' => 'Dil başarıyla değiştirildi.',
            'user' => $user,
        ]);
    }
}
