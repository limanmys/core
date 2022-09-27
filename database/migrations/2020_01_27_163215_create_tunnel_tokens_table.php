<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTunnelTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tunnel_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token');
            $table->string('remote_host');
            $table->string('remote_port', 5);
            $table->string('local_port', 5);
            $table->uuid('user_id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->uuid('extension_id');
            $table
                ->foreign('extension_id')
                ->references('id')
                ->on('extensions')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tunnel_tokens');
    }
}
