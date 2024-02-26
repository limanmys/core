<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('title');
            $table->string('type');
            $table->string('message');
            $table->uuid('server_id')->nullable();
            $table
                ->foreign('server_id')
                ->references('id')
                ->on('servers')
                ->onDelete('cascade');
            $table->uuid('extension_id')->nullable();
            $table
                ->foreign('extension_id')
                ->references('id')
                ->on('extensions')
                ->onDelete('cascade');
            $table->integer('level');
            $table->boolean('read')->default(false);
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
        Schema::dropIfExists('notifications');
    }
};
