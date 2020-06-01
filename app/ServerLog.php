<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerLog extends Model
{
    use UsesUuid;

    protected $fillable = ['command', 'server_id', 'user_id', 'output'];

    public static function new(
        $command,
        $output,
        $server_id = null,
        $user_id = null
    ) {
        return ServerLog::create([
            "command" => $command,
            "user_id" => $user_id == null ? auth()->user()->id : $user_id,
            "server_id" => $server_id == null ? server()->id : $server_id,
            "output" => $output,
        ]);
    }

    public static function retrieve($readable = false, $searchQuery = null,$server_id = null)
    {
        // First, Retrieve Logs.
        $query = ServerLog::where([
            "server_id" => $server_id == null ? server()->id : $server_id,
        ]);
        if($searchQuery != null){
            $query = $query->where('command', 'LIKE', '%'.$searchQuery.'%')->orWhere('output', 'LIKE', '%'.$searchQuery.'%');
        }
        $query2 = clone $query;
        $logs = $query->orderBy('updated_at', 'DESC')->paginate(10);

        // If it's not requested as readable, which means id's only without logic.
        if (!$readable) {
            return $logs;
        }
        $count = $query2->count();

        // First, convert user_id's into the user names.
        $users = User::all();

        foreach ($logs as $log) {
            $user = $users->where('id', $log->user_id)->first();
            if (!$user) {
                continue;
            }
            $log->username = $user->name;
            $log->output = substr($log->output,0,100);
        }
        return [$logs,$count];
    }
}
