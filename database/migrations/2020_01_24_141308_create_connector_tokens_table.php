<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConnectorTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('connector_tokens', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('server_id');
            $table->foreign("server_id")->references("id")->on("servers")->onDelete("cascade");
            $table->uuid('user_id');
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string('token');
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
        Schema::dropIfExists('connector_tokens');
    }
}
