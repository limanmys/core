<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ServerLog extends Eloquent
{
    protected $collection = 'server_log';
    protected $connection = 'mongodb';
    protected $fillable = ['command', 'server_id', 'user_id'];
    public static $dont_log = [
        "hostname", "sudo systemctl is-failed bind9", "df -h" , "whoami"
    ];
    public static function new($command, $server_id = null,$user_id = null)
    {
        if(in_array($command, ServerLog::$dont_log)){
            return false;
        }
        $log = new ServerLog([
           "command" => $command,
            "user_id" => ($user_id == null) ? auth()->user()->_id : $user_id,
            "server_id" => ($server_id == null) ? server()->_id : $server_id
        ]);
        $log->save();
        return $log;
    }

    public static function retrieve($readable = false,$server_id = null)
    {
        // First, Retrieve Logs.
        $logs = ServerLog::where([
            "server_id" => ($server_id == null) ? server()->_id : $server_id
        ])->get();

        // If it's not requested as readable, which means id's only without logic.
        if(!$readable){
            return $logs;
        }

        // First, convert user_id's into the user names.
        $users = \App\User::all();
        $scripts = \App\Script::all();

        foreach ($logs as $log){
            $user = $users->where('_id', $log->user_id)->first();
            if(!$user){
                continue;
            }
            $log->username = $user->name;
            if(strpos($log->command, "sudo /usr/bin/env python3 /tmp/") == 0){
                if (preg_match('/\/tmp\/([a-zA-Z0-9_]*) /', $log->command, $script_id) == 1) {
                    $script = $scripts->find($script_id[1]);
                    if($script) {
                        $log->command = $script->name . " (". substr($log->command, 60) .")";
                    }
                }
            }
        }
        return $logs;
    }

}
