<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUserSettingsAddIndexField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function(Blueprint $table)
        {
            $table->index('user_id');
            $table->index('server_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_settings', function(Blueprint $table)
        {
            $table->dropIndex('user_id');
            $table->dropIndex('server_id');
        });
    }
}
