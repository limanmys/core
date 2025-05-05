<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('notifications')
            ->where('level', 'information')
            ->orWhere('level', 'success')
            ->update(['level' => 'trivial']);

        DB::table('notifications')
            ->where('level', 'warning')
            ->update(['level' => 'medium']);

        DB::table('notifications')
            ->where('level', 'error')
            ->update(['level' => 'critical']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
