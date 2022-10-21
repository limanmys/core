<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use UsesUuid;

    protected $fillable = ['server_hostname', 'origin'];

    public function addToSystem($tmpCertLocation)
    {
        rootSystem()->addCertificate($tmpCertLocation, 'liman-'.$this->server_hostname.'_'.$this->origin);
    }

    public function removeFromSystem()
    {
        rootSystem()->removeCertificate('liman-'.$this->server_hostname.'_'.$this->origin);
        $this->save();
    }
}
