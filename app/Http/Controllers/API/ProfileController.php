<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function setLocale(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|in:tr,en,de',
        ]);

        $user = User::find(auth('api')->user()->id);
        $user->update([
            'locale' => $request->locale,
        ]);

        return response()->json([
            'status' => true,
            'user' => $user,
        ]);
    }
}
