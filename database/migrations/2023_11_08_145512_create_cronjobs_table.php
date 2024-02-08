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
        Schema::create('cronjobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->integer('day');

            $table->string('base_url');
            $table->text('payload');
            $table->string('time');
            $table->string('message');
            $table->string('status');
            $table->string('target');
            $table->text('output');
            
            $table->foreign('extension_id')->references('id')->on('extensions')->onDelete('cascade');
            $table->uuid('extension_id');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('user_id');
            
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->uuid('server_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cronjobs');
    }
};