<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kubernetes_information', function (Blueprint $table) {
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->uuid('server_id')->primary();

            $table->longText('kubeconfig');
            $table->string('namespace');
            $table->string('deployment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kubernetes_information');
    }
};
