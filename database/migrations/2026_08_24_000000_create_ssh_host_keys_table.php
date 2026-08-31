<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssh_host_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('host');
            $table->unsignedInteger('port');
            $table->string('key_type');
            $table->text('public_key');
            $table->string('fingerprint');
            $table->uuid('approved_by')->nullable();
            $table->uuid('revoked_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['host', 'port', 'fingerprint']);
            $table->index(['host', 'port', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssh_host_keys');
    }
};
