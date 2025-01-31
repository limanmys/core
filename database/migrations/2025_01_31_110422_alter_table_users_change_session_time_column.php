<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('session_time')->default(-1)->change();

            // Change all session_time values that is 120 to -1
            DB::table('users')->where('session_time', 120)->update(['session_time' => -1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('session_time')->default(120)->change();

            // Change all session_time values that is -1 to 120
            DB::table('users')->where('session_time', -1)->update(['session_time' => 120]);
        });
    }
};
