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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('ip_address');
            $table->string('action');
            $table->string('type');
            $table->jsonb('details');
            $table->jsonb('request');
            $table->text('message')->nullable();

            $table->timestamps();
            $table->dropColumn('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
