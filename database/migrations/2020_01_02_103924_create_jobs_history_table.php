<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
            $table->uuid("server_id")->nullable();
            $table
                ->foreign("server_id")
                ->references("id")
                ->on("servers")
                ->onDelete("cascade");
            $table->uuid("extension_id")->nullable();
            $table
                ->foreign("extension_id")
                ->references("id")
                ->on("extensions")
                ->onDelete("cascade");
            $table->string('job', 40);
            $table->integer('job_id')->nullable();
            $table->integer('status')->default(0);
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('jobs_history');
        Schema::enableForeignKeyConstraints();
    }
}
