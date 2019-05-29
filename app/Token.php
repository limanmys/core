<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Str;

/**
 * @method static where(string $string, $param)
 */
class Token extends Eloquent
{
    protected $collection = 'token';
    protected $connection = 'mongodb';
    protected $fillable = ['token', 'user_id'];

    public static function create($user_id = null)
    {
        // Delete Old Tokens
//        $old = Token::where('user_id',($user_id) ? $user_id : auth()->id())->get();
        // if($old) $old->destroy();

        $token = $token = Str::random(32);
        while(Token::where('token',$token)->exists()){
            $token = $token = Str::random(32);
        }

        $obj = new Token();
        $obj->token = $token;
        $obj->user_id = ($user_id) ? $user_id : auth()->id();
        $obj->save();
        return $token;
    }
}
