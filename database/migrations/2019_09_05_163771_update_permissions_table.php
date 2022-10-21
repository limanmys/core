<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add New Rows to the Table.
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'type')) {
                $table->string('type')->nullable();
            }
            if (! Schema::hasColumn('permissions', 'key')) {
                $table->string('key')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'value')) {
                $table->string('value')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'extra')) {
                $table->string('extra')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'blame')) {
                $table->string('blame')->nullable();
            }
        });

        // Copy Existing Values to The Table
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            // Convert Existing Datas in to Types
            if ($permission->server_id != null) {
                $permission->type = 'server';
                $permission->key = 'id';
                $permission->value = $permission->server_id;
            }

            if ($permission->extension_id != null) {
                $permission->type = 'extension';
                $permission->key = 'id';
                $permission->value = $permission->extension_id;
            }

            if ($permission->script_id != null) {
                $permission->type = 'script';
                $permission->key = 'id';
                $permission->value = $permission->script_id;
            }

            if ($permission->function != null) {
                $permission->type = 'function';
                $permission->key = 'name';
                // For old type, split the function name.
                strpos((string) $permission->function, '_');
                $permission->extra = substr(
                    (string) $permission->function,
                    strpos((string) $permission->function, '_') + 1
                );
                $permission->value = substr(
                    (string) $permission->function,
                    0,
                    strpos((string) $permission->function, '_')
                );
            }

            $permission->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
