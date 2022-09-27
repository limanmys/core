<?php

use App\Models\Extension;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateExtensionSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create Encryption Keys for existing Extensions.
        $extensions = Extension::all();
        foreach ($extensions as $extension) {
            $passPath = '/liman/keys/'.DIRECTORY_SEPARATOR.$extension->id;
            file_put_contents($passPath, Str::random(32));
        }

        // Encrypt Values in Database
        $settings = DB::table('user_settings')->get();
        foreach ($settings as $setting) {
            $key =
                env('APP_KEY').
                $setting->user_id.
                $setting->extension_id.
                $setting->server_id;
            // First check if it's encrypted or not.
            $decrypted = openssl_decrypt($setting->value, 'aes-256-cfb8', $key);
            $stringToDecode = substr($decrypted, 16);
            // Check if we actually decrypted the input.
            if (base64_decode($stringToDecode)) {
                continue;
            }
            // If not, encrypt and update table value.
            $encrypted = openssl_encrypt(
                Str::random(16).base64_encode($setting->value),
                'aes-256-cfb8',
                $key,
                0,
                Str::random(16)
            );
            DB::table('user_settings')
                ->where('id', $setting->id)
                ->update([
                    'value' => $encrypted,
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
