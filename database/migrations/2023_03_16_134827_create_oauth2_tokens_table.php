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
        Schema::create('oauth2_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('user_id');

            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('token_type');
            $table->integer('expires_in');
            $table->integer('refresh_expires_in');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oauth2_tokens');
    }
};
