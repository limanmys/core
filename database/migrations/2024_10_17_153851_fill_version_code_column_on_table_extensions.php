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
        $extensions = \App\Models\Extension::all();
        foreach ($extensions as $extension) {
            $extJson = getExtensionJson($extension->name);
            if (!$extJson) {
                continue;
            }
            $extension->version_code = $extJson['version_code'];
            $extension->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
