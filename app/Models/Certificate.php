<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use UsesUuid;

    protected $fillable = ["server_hostname", "origin"];

    public function addToSystem($tmpCertLocation)
    {
        $file =
            "liman-" . $this->server_hostname . "_" . $this->origin . ".crt";
        $contents = file_get_contents($tmpCertLocation);
        shell_exec(
            "echo '$contents'| sudo tee /usr/local/share/ca-certificates/" .
                strtolower($file)
        );
        shell_exec("sudo update-ca-certificates");
    }

    public function removeFromSystem()
    {
        $file =
            "liman-" . $this->server_hostname . "_" . $this->origin . ".crt";
        shell_exec('sudo rm /usr/local/share/ca-certificates/ ' . $file);
        shell_exec("sudo update-ca-certificates -f");
        $this->save();
    }
}
