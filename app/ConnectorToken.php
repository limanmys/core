<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConnectorToken extends Model
{
    use UsesUuid;

    protected $fillable = [
        "user_id", "server_id", "token"
    ];

    public static function get($server_id)
    {
        return ConnectorToken::where([
            "user_id" => user()->id,
            "server_id" => $server_id
        ]);
    }

    public static function set($token, $server_id)
    {
        if ($token == null) {
            abort(504, "Lütfen kasa üzerinden yeni bir anahtar ekleyin.");
        }
        //Delete Old Ones
        ConnectorToken::where([
            "user_id" => user()->id,
            "server_id" => $server_id
        ])->delete();

        return ConnectorToken::create([
            "user_id" => user()->id,
            "server_id" => $server_id,
            "token" => $token
        ]);
    }

    public static function clear()
    {
        //Delete Old Ones
        return ConnectorToken::where([
            "user_id" => user()->id
        ])->delete();
    }
}
