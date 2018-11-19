<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Auth;

class Server extends Eloquent
{
    protected $collection = 'servers';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'ip_address' ,'port' ,'city'];

    public function run($command){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $command . " 2>&1";
        return shell_exec($query);
    }

    public function runScript($script,$parameters){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        //Copy script to target.
        $copy_file_query = 'scp -P ' . $this->port .' ' . storage_path('app/scripts/' . $script->_id) .' ' . $key->username .'@' . $this->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        //shell_exec('sudo chmod +x /tmp/' . $script->_id);
        $query = ($script->root == 1)? 'sudo ' : '';
        $query = $query . './tmp/' .$script->_id . " run".$parameters;
        dd($query);
        $output = shell_exec($query);
        return $output;
    }
}
