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
        Schema::create('notification_users', function (Blueprint $table) {
            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications');
            $table->uuid('notification_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            $table->uuid('user_id');

            $table->timestamp('read_at')->nullable();
            $table->timestamp('seen_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_users');
    }
};
