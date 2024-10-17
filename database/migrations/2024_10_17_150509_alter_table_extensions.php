<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('displays');
            $table->dropColumn('support');
            $table->dropColumn('language');
            $table->dropColumn('issuer');
            $table->dropColumn('service');
            $table->dropColumn('order');
            // require_key defaults to false
            // first convert existing data to boolean
            DB::statement("ALTER TABLE extensions 
                ALTER COLUMN require_key DROP DEFAULT,
                ALTER COLUMN require_key TYPE BOOLEAN USING require_key::BOOLEAN,
                ALTER COLUMN require_key SET DEFAULT FALSE;");
            // add version_code column
            $table->string('version_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
