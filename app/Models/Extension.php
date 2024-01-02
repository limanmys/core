<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Extension Model
 *
 * @extends Model
 */
class Extension extends Model
{
    use UsesUuid, CacheQueryBuilder;

    /**
     * Fillable fields in model
     *
     * @var array
     */
    protected $fillable = [
        'display_name',
        'name',
        'version',
        'icon',
        'service',
        'sslPorts',
        'issuer',
        'language',
        'support',
        'displays',
        'require_key',
        'status',
        'license_type',
        'ldap_support',
    ];

    protected $casts = [
        'displays' => 'array',
    ];

    /**
     * @return mixed
     */
    public static function one($id)
    {
        // Find Object
        $extension = Extension::find($id);

        // If object is not found, abort
        if ($extension == null) {
            abort(504, 'Eklenti Bulunamadı');
        }

        return $extension;
    }

    /**
     * License object
     *
     * @return mixed
     */
    public function license()
    {
        return $this->hasOne('App\Models\License', 'extension_id');
    }

    /**
     * Boot model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('extensions', function (
            \Illuminate\Database\Eloquent\Builder $builder
        ) {
            $builder->orderBy('order');
        });
    }

    /**
     * Get extension servers
     *
     * @return mixed
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_extensions');
    }

    /**
     * Get user's all extensions
     *
     * @return mixed
     */
    public static function getAll()
    {
        return user()->extensions();
    }

    /**
     * Get extension display name
     *
     * @return mixed|string
     */
    public function getDisplayNameAttribute($value)
    {
        if (empty($this->attributes['display_name'])) {
            return Str::title(str_replace('-', ' ', (string) $this->name));
        }

        $displayName = json_decode($this->attributes['display_name'], true);
        if (is_array($displayName)) {
            return $displayName[auth('api')->user()->language] ?? $displayName[app()->getLocale()] ?? $this->attributes['display_name'];
        }

        return str_replace('"', '', json_decode($this->attributes['display_name']) ?? $this->attributes['display_name']);
    }

    /**
     * Get updated at attribute
     *
     * @return string
     */
    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->isoFormat('LLLL');
    }

    /**
     * Set name attribute
     *
     * @return void
     */
    public function setNameAttribute($value)
    {
        if ($this->name) {
            return;
        }

        $this->attributes['name'] = $value;
    }
}
