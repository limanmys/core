<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('monitor_servers')) {
            Schema::create('monitor_servers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('ip_address');
                $table->integer('port');
                $table->boolean('online');
                $table->timestamp('last_checked');
                $table->timestamps();
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_servers');
    }
}
