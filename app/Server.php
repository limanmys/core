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
            'server_id' => $this->id
        ])->first();
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $command . " 2>&1";
        return shell_exec($query);
    }
}
