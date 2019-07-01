<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWidgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('widgets',function (Blueprint $table){
            $table->string("extension_id")->nullable();
            $table->string("server_id")->nullable();
            $table->string("function")->nullable();
            $table->foreign("extension_id")->references("id")->on("extensions")->onDelete("cascade");
            $table->foreign("server_id")->references("id")->on("servers")->onDelete("cascade");
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string("text")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
