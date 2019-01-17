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
        if (!is_dir(storage_path('keys'))) {
            shell_exec("mkdir -p " . storage_path('keys'));
        }

        //Generate key and put it into keys folder, dont regenerate!
        if(!file_exists(storage_path('keys')  . DIRECTORY_SEPARATOR . $account_name)){
            shell_exec("ssh-keygen -t rsa -f " . storage_path('keys')  . DIRECTORY_SEPARATOR . $account_name ." -q -P ''");
        }

        //Check if server is already trusted or not.
        if(shell_exec("ssh-keygen -F " . $server_address . " 2>/dev/null") == null){
            // Trust Target Server
            shell_exec("ssh-keyscan -p " . $server_port . " -H ". $server_address . " >> ~/.ssh/known_hosts");
        }

        //Send Keys to target
        shell_exec("sshpass -p '" . $password . "' ssh-copy-id -i " . storage_path('keys')  . DIRECTORY_SEPARATOR . $account_name ." " . $username
            ."@" . $server_address ." 2>&1 -p " . $server_port);
    }

    public static function initWithKey($username,$key,$server_address,$server_port,$current_name, $new_name){
        //Create keys folder
        if (!is_dir(storage_path('keys'))) {
            shell_exec("mkdir -p " . storage_path('keys'));
        }

        //Generate key and put it into keys folder, dont regenerate!
        if(!file_exists(storage_path('keys')  . DIRECTORY_SEPARATOR . $new_name)){
            shell_exec("ssh-keygen -t rsa -f " . storage_path('keys')  . DIRECTORY_SEPARATOR . $new_name ." -q -P ''");
        }

        //Check if server is already trusted or not.
        if(shell_exec("ssh-keygen -F " . $server_address . " 2>/dev/null") == null){
            // Trust Target Server
            shell_exec("ssh-keyscan -p " . $server_port . " -H ". $server_address . " >> ~/.ssh/known_hosts");
        }

        //Send Keys to target
        shell_exec('cat ' . storage_path('keys')  . DIRECTORY_SEPARATOR . $new_name . ".pub | ssh -i " .
            storage_path('keys')  . DIRECTORY_SEPARATOR . $current_name .
            " $username@$server_address -p $server_port 'cat >> .ssh/authorized_keys'");
    }
}
