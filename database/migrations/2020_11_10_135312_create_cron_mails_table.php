<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCronMailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cron_mails', function (Blueprint $table) {
            $table->uuid('id');
            $table->string("server_id")->nullable();
            $table->string("extension_id")->nullable();
            $table->string("user_id");
            $table->string("type")->default("extension");
            $table->string("cron_type")->default("weekly");
            $table->string("target");
            $table->string("to");
            $table->timestamp("last");
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
        Schema::dropIfExists('cron_mails');
    }
}
