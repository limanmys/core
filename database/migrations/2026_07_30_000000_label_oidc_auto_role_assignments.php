<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Label existing automatic OIDC role assignments with their real source.
     */
    public function up(): void
    {
        DB::table('role_users')
            ->where('auto', true)
            ->where('type', 'local')
            ->update(['type' => 'oidc']);
    }

    public function down(): void
    {
        DB::table('role_users')
            ->where('auto', true)
            ->where('type', 'oidc')
            ->update(['type' => 'local']);
    }
};
