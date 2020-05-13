<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Server;
use App\ConnectorToken;

class AddKeyPortToServers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('key_port')->nullable();
        });

        //Update current server objects.
        $servers = Server::whereIn('type',['linux_ssh','linux_certificate','windows_powershell'])->get();
        foreach($servers as $server){
            if($server->key_port == null){
                if($server->type == "windows_powershell"){
                    $server->key_port = 5986;
                }else{
                    $server->key_port = 22;
                }
                $server->save();
            }
        }

        // Delete current tokens.
        ConnectorToken::truncate();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            //
        });
    }
}
