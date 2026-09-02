<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_keys', function (Blueprint $table) {
            $table->boolean('shared')->default(false);
        });

        DB::statement(
            'CREATE UNIQUE INDEX server_keys_one_shared_per_server '
            .'ON server_keys (server_id) WHERE shared = true'
        );

        // The legacy server flag does not identify which key was deliberately shared.
        // Require an explicit key-level sharing decision after this migration.
        DB::table('servers')->where('shared_key', 1)->update(['shared_key' => 0]);
    }

    public function down(): void
    {
        // Never reconstruct the legacy ambiguous server-wide sharing state.
        DB::table('servers')->where('shared_key', 1)->update(['shared_key' => 0]);
        DB::statement('DROP INDEX IF EXISTS server_keys_one_shared_per_server');

        Schema::table('server_keys', function (Blueprint $table) {
            $table->dropColumn('shared');
        });
    }
};
