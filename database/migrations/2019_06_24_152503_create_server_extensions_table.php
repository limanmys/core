<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServerExtensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_extensions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("server_id");
            $table->foreign("server_id")->references("id")->on("servers")->onDelete("cascade")->nullable();
            $table->string("extension_id");
            $table->foreign("extension_id")->references("id")->on("extensions")->onDelete("cascade")->nullable();
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
        Schema::dropIfExists('server_extensions');
    }
}
