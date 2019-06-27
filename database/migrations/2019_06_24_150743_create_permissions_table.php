<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("user_id");
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string("server_id")->nullable();
            $table->foreign("server_id")->references("id")->on("servers")->onDelete("cascade");
            $table->string("extension_id")->nullable();
            $table->foreign("extension_id")->references("id")->on("extensions")->onDelete("cascade");
            $table->string("script_id")->nullable();
            $table->foreign("script_id")->references("id")->on("scripts")->onDelete("cascade");
            $table->string("function")->nullable();
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
        Schema::dropIfExists('permissions');
    }
}
