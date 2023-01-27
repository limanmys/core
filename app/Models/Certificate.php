<?php

namespace App\Models;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;

/**
 * Certificate Model
 *
 * @extends Model
 */
class Certificate extends Model
{
    use UsesUuid;

    protected $fillable = ['server_hostname', 'origin'];

    /**
     * Add liman systemwide certificate to system
     *
     * @param $tmpCertLocation
     * @return void
     * @throws GuzzleException
     */
    public function addToSystem($tmpCertLocation)
    {
        rootSystem()->addCertificate($tmpCertLocation, 'liman-' . $this->server_hostname . '_' . $this->origin);
    }

    /**
     * Remove a certificate from system
     *
     * @return void
     * @throws GuzzleException
     */
    public function removeFromSystem()
    {
        rootSystem()->removeCertificate('liman-' . $this->server_hostname . '_' . $this->origin);
        $this->save();
    }
}
