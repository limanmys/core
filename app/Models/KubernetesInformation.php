<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class KubernetesInformation extends Model
{
    protected $primaryKey = 'server_id';

    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'kubeconfig',
        'namespace',
        'deployment'
    ];

    protected $hidden = [
        'kubeconfig'
    ];    
    
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Encrypt the kubeconfig before storing in database
     */
    public function setKubeconfigAttribute($value)
    {
        if ($value !== null && $value !== '') {
            $this->attributes['kubeconfig'] = Crypt::encryptString($value);
        } else {
            $this->attributes['kubeconfig'] = $value;
        }
    }

    /**
     * Decrypt the kubeconfig when retrieving from database
     */
    public function getKubeconfigAttribute($value)
    {
        if ($value !== null && $value !== '') {
            try {
                return base64_encode(Crypt::decryptString($value));
            } catch (\Exception $e) {
                // If decryption fails, return null or handle error as needed
                return null;
            }
        }
        return $value;
    }
}
