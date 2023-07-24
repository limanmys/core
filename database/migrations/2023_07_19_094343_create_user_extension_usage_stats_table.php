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
        Schema::create('user_extension_usage_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('user_id');

            $table->foreign('extension_id')->references('id')->on('extensions')->onDelete('cascade');
            $table->uuid('extension_id');

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->uuid('server_id');

            $table->unique(['user_id', 'extension_id', 'server_id']);

            $table->integer('usage')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_extension_usage_stats');
    }
};
