<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtensionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extension_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('log_id');
            $table
                ->foreign('log_id')
                ->references('id')
                ->on('server_logs')
                ->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('message');
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
        Schema::dropIfExists('extension_logs');
    }
}
