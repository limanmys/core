<?php

use App\Models\UserSettings;
use Illuminate\Database\Migrations\Migration;
use mervick\aesEverywhere\AES256;

class ChangeEncryptionAlghoritm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $settings = UserSettings::all();
        foreach ($settings as $setting) {
            $key = env('APP_KEY').$setting->user_id.$setting->server_id;
            $decrypted = openssl_decrypt($setting->value, 'aes-256-cfb8', $key);
            $stringToDecode = substr($decrypted, 16);
            $password = base64_decode($stringToDecode);
            $setting->update([
                'value' => AES256::encrypt($password, $key),
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
