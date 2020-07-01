<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_data', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid("permission_id");
            $table
                ->foreign("permission_id")
                ->references("id")
                ->on("permissions")
                ->onDelete("cascade");
            $table->text('data');
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
        Schema::dropIfExists('permission_data');
    }
}
