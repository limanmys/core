<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\UserSettings;
class UpdateOsInServers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $servers = Server::all();

        foreach ($servers as $server) {
            $os = "";
            $type = "";
            $user_ids = [];
            $settings = UserSettings::where(["server_id" => $server->id])
                ->where(["name" => "clientUsername"])
                ->orWhere(["name" => "clientPassword"])
                ->get();
            foreach ($settings as $setting) {
                $user_ids[$setting->user_id][$setting->name] = $setting->value;
                $setting->delete();
            }
            switch ($server->type) {
                case "linux_ssh":
                    $type = "ssh";
                    $os = "linux";
                    break;
                case "linux":
                    $os = "linux";
                    break;
                case "linux_certificate":
                    $type = "ssh_certificate";
                    $os = "linux";
                    break;
                case "windows_powershell":
                    $type = "winrm";
                    $os = "windows";
                    break;
                case "windows":
                    $os = "windows";
                    break;
            }
            if ($type != "") {
                foreach ($user_ids as $user_id => $data) {
                    if (
                        !array_key_exists("clientUsername", $data) ||
                        !array_key_exists("clientPassword", $data)
                    ) {
                        continue;
                    }
                    ServerKey::updateOrCreate(
                        ["server_id" => $server->id, "user_id" => $user_id],
                        ["type" => $type, "data" => json_encode($data)]
                    );
                }
            }
            if ($os == "") {
                continue;
            }
            $flag = $server->update([
                "os" => $os,
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
    }
}
