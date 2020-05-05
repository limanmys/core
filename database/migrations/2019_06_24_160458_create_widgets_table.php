<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWidgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("name");
            $table->string("title");
            $table->uuid("user_id");
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
            $table->string("type");
            $table->uuid("extension_id")->nullable();
            $table
                ->foreign("extension_id")
                ->references("id")
                ->on("extensions")
                ->onDelete("cascade");
            $table->uuid("server_id")->nullable();
            $table
                ->foreign("server_id")
                ->references("id")
                ->on("servers")
                ->onDelete("cascade");
            $table->string("function")->nullable();
            $table->string("text")->nullable();
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
        Schema::dropIfExists('widgets');
    }
}
