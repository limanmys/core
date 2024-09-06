<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('oauth2_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('oauth2_tokens', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // ON DELETE CASCADE ekleniyor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('oauth2_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');
        });
    }
};
