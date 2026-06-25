<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
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

        // Validation önce çalışsın; parola değişikliği ancak tüm validationlar geçerse uygulansın.
        validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth('api')->user()->id,
            'password' => ['nullable', 'string', new StrongPassword],
            'old_password' => ['required_with:password', 'string'],
        ]);

        $user = User::find(auth('api')->user()->id);

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

        $session_time = env('JWT_TTL', 120);
        if ($request->session_time == $session_time) {
            $session_time = -1;
        } else {
            $session_time = $request->session_time;
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'otp_enabled' => (bool) $request->otp_enabled,
            'session_time' => $session_time,
        ]);

        if (! (bool) $request->otp_enabled) {
            $user->update([
                'google2fa_secret' => null
            ]);
        }

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
