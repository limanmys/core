<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Token extends Model
{
    use UsesUuid;

    protected $fillable = ['token', 'user_id'];

    public static function create($user_id = null)
    {
        // Delete Old Tokens
        //        $old = Token::where('user_id',($user_id) ? $user_id : auth()->id())->get();
        // if($old) $old->destroy();

        $token = Str::random(32);
        while (Token::where('token', $token)->exists()) {
            $token = Str::random(32);
        }

        Token::firstOrCreate([
            "token" => $token,
            "user_id" => $user_id ? $user_id : auth()->id(),
        ]);

        return $token;
    }
}
