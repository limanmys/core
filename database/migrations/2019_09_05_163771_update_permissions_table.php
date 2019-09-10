<?php

use App\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
        Schema::table('permissions',function (Blueprint $table){
            $table->string('type')->nullable();
            $table->string('key')->nullable();
            $table->string('value')->nullable();
            $table->string('extra')->nullable();
            $table->string('blame')->nullable();
        });

        // Copy Existing Values to The Table
        $permissions = Permission::all();
        foreach($permissions as $permission){
            // Convert Existing Datas in to Types
            if($permission->server_id != null){
                $permission->type = "server";
                $permission->key = "id";
                $permission->value = $permission->server_id;
            }

            if($permission->extension_id != null){
                $permission->type = "extension";
                $permission->key = "id";
                $permission->value = $permission->extension_id;
            }

            if($permission->script_id != null){
                $permission->type = "script";
                $permission->key = "id";
                $permission->value = $permission->script_id;
            }

            if($permission->function != null){
                $permission->type = "function";
                $permission->key = "name";
                // For old type, split the function name.
                strpos($permission->function,"_");
                $permission->extra = substr($permission->function,strpos($permission->function,"_") + 1);
                $permission->value = substr($permission->function,0,strpos($permission->function,"_"));
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
