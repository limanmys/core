<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Key extends Eloquent
{
    protected $collection = 'keys';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'username' ,'server_id'];

    public static function init($username,$password,$server_address,$server_port,$account_name){
        //Create keys folder
        if (!is_dir(__DIR__ . "/../keys")) {
            shell_exec("mkdir -p " . __DIR__ . "/../keys");
        }
        //Generate key and put it into keys folder, dont regenerate!
        if(!file_exists(__DIR__ . "/../keys/" . $account_name)){
            shell_exec("ssh-keygen -t rsa -f " . __DIR__ . "/../keys/" . $account_name ." -q -P ''");
        }
        //Trust target server
        shell_exec("ssh-keyscan -p " . $server_port . " -H ". $server_address . " >> ~/.ssh/known_hosts");
        //Send Keys to target
        return shell_exec("sshpass -p '" . $password . "' ssh-copy-id -i " . __DIR__ . "/../keys/" . $account_name ." " . $username
            ."@" . $server_address ." 2>&1 -p " . $server_port);
    }
}
