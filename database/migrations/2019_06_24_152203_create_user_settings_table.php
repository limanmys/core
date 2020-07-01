<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("extension_id");
            $table
                ->foreign("extension_id")
                ->references("id")
                ->on("extensions")
                ->onDelete("cascade");
            $table->uuid("server_id");
            $table
                ->foreign("server_id")
                ->references("id")
                ->on("servers")
                ->onDelete("cascade");
            $table->uuid("user_id");
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
            $table->string("name");
            $table->string("value");
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
        Schema::dropIfExists('user_settings');
    }
}
