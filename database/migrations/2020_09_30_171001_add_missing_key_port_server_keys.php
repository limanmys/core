<?php

use App\Models\ServerKey;
use Illuminate\Database\Migrations\Migration;

class AddMissingKeyPortServerKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $keys = ServerKey::all();

        foreach ($keys as $key) {
            $temp = $key->data;
            $arr = json_decode($temp, true);

            if (! array_key_exists('key_port', $arr)) {
                $type = $key->type;
                $port = null;
                switch($type) {
                    case 'ssh':
                    case 'ssh_certificate':
                        $port = '22';
                        break;
                    case 'winrm':
                    case 'winrm_certificate':
                        $port = '5986';
                        break;
                    case 'snmp':
                        $port = '161';
                        break;
                }

                if ($port == null) {
                    continue;
                }

                $arr['key_port'] = $port;
                $foo = json_encode($arr);
                $key->update([
                    'data' => $foo,
                ]);
                $key->save();
            }
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
