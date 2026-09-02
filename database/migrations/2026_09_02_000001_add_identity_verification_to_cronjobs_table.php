<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronjobs', function (Blueprint $table) {
            $table->boolean('identity_verified')->default(false);
        });

        // Creator identity was not authenticated when legacy rows were stored.
        DB::table('cronjobs')->update([
            'status' => 'failed',
            'message' => 'Disabled: creator identity could not be verified after security upgrade.',
        ]);
    }

    public function down(): void
    {
        // Rows without verified provenance must not become executable after rollback.
        DB::table('cronjobs')->where('identity_verified', false)->delete();

        Schema::table('cronjobs', function (Blueprint $table) {
            $table->dropColumn('identity_verified');
        });
    }
};
