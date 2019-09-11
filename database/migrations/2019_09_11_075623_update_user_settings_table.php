<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, retrieve all data.
        $settings = DB::table('user_settings')->get()->toArray();

        // Since SQLite doesnt support dropping foreign keys, drop Existing Database if exists.
        Schema::dropIfExists('user_settings');

        // Now, Recreate Database
        Schema::create('user_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("server_id")->nullable();
            $table->foreign("server_id")->references("id")->on("servers");
            $table->uuid("user_id");
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string("name");
            $table->string("value");
            $table->timestamps();
        });

        //Move Data.
        foreach($settings as $setting){

            // Decrypt data since since we dont have extension_id anymore, we need to use another salt.
            $key = env('APP_KEY') . $setting->user_id . $setting->extension_id . $setting->server_id;
            $decrypted = openssl_decrypt($setting->value,'aes-256-cfb8',$key);
            $stringToDecode = substr($decrypted,16);
            $value = base64_decode($stringToDecode);

            // Now Encrypt Again.
            $key = env('APP_KEY') . $setting->user_id;
            $encrypted = openssl_encrypt(Str::random(16) . base64_encode($value),
                'aes-256-cfb8',$key,0,Str::random(16));

            // Fill data, we don't use model here, since we may delete it in the future.
            DB::table('user_settings')->insert([
                "id" => $setting->id,
                "server_id" => $setting->server_id,
                "user_id" => $setting->user_id,
                "name" => $setting->name,
                "value" => $encrypted,
                "created_at" => $setting->created_at,
                "updated_at" => $setting->updated_at
            ]);
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
