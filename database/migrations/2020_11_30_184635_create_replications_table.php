<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('replications')) {
            Schema::create('replications', function (Blueprint $table) {
                $table->uuid('id');
                $table->uuid('liman_id');
                $table->string('key');
                $table->integer('status')->default(0);
                $table->string('output', 9999);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('replications');
    }
}
