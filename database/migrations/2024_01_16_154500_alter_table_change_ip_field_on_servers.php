<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            'ALTER TABLE servers ALTER COLUMN ip_address TYPE varchar(255) USING ip_address::varchar'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement(
            'ALTER TABLE servers ALTER COLUMN ip_address TYPE inet USING ip_address::inet'
        );
    }
};
