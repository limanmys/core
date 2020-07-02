<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TunnelToken extends Model
{
    use UsesUuid;

    protected $fillable = [
        "token",
        "remote_host",
        "remote_port",
        "local_port",
        "user_id",
        "extension_id",
    ];

    public static function get($remote_host, $remote_port)
    {
        return TunnelToken::where([
            "user_id" => user()->id,
            "extension_id" => extension()->id,
            "remote_host" => $remote_host,
            "remote_port" => $remote_port,
        ]);
    }

    public static function set($token, $local_port, $remote_host, $remote_port)
    {
        if ($token == null) {
            abort(504, "Tünel açılırken bir hata oluştu.");
        }
        //Delete Old Ones
        TunnelToken::where([
            "user_id" => user()->id,
            "extension_id" => extension()->id,
            "remote_host" => $remote_host,
            "remote_port" => $remote_port,
        ])->delete();

        return TunnelToken::create([
            "user_id" => user()->id,
            "extension_id" => extension()->id,
            "remote_host" => $remote_host,
            "remote_port" => $remote_port,
            "token" => $token,
            "local_port" => $local_port,
        ]);
    }

    public static function remove($token)
    {
        return TunnelToken::where([
            "user_id" => user()->id,
            "extension_id" => extension()->id,
            "token" => $token,
        ])->delete();
    }
}
