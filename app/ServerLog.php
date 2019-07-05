<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * @method static where(array $array)
 */
class ServerLog extends Eloquent
{
    use UsesUuid;
    
    protected $collection = 'server_log';
    protected $connection = 'mongodb';
    protected $fillable = ['command', 'server_id', 'user_id','output'];
    public static $dont_log = [
        "hostname", "df -h" , "whoami", "sudo systemctl is-failed "
    ];
    public static function new($command, $output, $server_id = null,$user_id = null)
    {
        foreach (self::$dont_log as $check){
            if(strpos($command, $check)){
                return false;
            }
        }
        if(in_array($command, self::$dont_log)){
            return false;
        }
        $log = new ServerLog([
           "command" => $command,
            "user_id" => ($user_id == null) ? auth()->user()->_id : $user_id,
            "server_id" => ($server_id == null) ? server()->_id : $server_id,
            "output" => $output
        ]);
        $log->save();
        return $log;
    }

    public static function retrieve($readable = false,$server_id = null)
    {
        // First, Retrieve Logs.
        $logs = ServerLog::where([
            "server_id" => ($server_id == null) ? server()->_id : $server_id
        ])->orderBy('updated_at', 'DESC')->get();

        // If it's not requested as readable, which means id's only without logic.
        if(!$readable){
            return $logs;
        }

        // First, convert user_id's into the user names.
        $users = User::all();
        $scripts = Script::all();

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
                        $log->command = $script->name . " (". substr($log->command, 56) .")";
                    }
                }
            }
        }
        return $logs;
    }

}
