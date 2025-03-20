<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Model
 *
 * @extends Model
 */
class Permission extends Model
{
    use UsesUuid, CacheQueryBuilder;

    protected $table = 'permissions';

    protected $fillable = [
        'morph_id',
        'morph_type',
        'type',
        'key',
        'value',
        'extra',
        'blame',
    ];

    /**
     * Determine if user is eligible to do this type of event
     *
     * @param $user_id
     * @param $type
     * @param $key
     * @param $value
     * @param $extra
     * @return true
     */
    public static function can($user_id, $type, $key, $value, $extra = null)
    {
        $user = User::find($user_id);
        // Verify if user is admin.
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->auth_type === 'keycloak') {
            $cachedRoles = Cache::get(
                sprintf("kc_roles:%s", $user->id)
            );

            if ($cachedRoles) {
                $roles = json_decode($cachedRoles);

                if (in_array($extra, $roles)) {
                    return true;
                }
            }
        }

        $ids = $user->roles->pluck('id')->toArray();
        array_push($ids, $user_id);

        return Permission::whereIn('morph_id', $ids)
            ->where([
                'type' => $type,
                'key' => $key,
                'value' => $value,
                'extra' => $extra,
            ])
            ->exists();
    }

    /**
     * Grant user a permission
     *
     * @param $morph_id
     * @param $type
     * @param $key
     * @param $value
     * @param $extra
     * @param $morph_type
     * @return false
     */
    public static function grant(
        $morph_id,
        $type,
        $key,
        $value,
        $extra = null,
        $morph_type = 'roles'
    )
    {
        try {
            return Permission::firstOrCreate([
                'morph_id' => $morph_id,
                'morph_type' => $morph_type,
                'type' => $type,
                'key' => $key,
                'value' => $value,
                'extra' => $extra,
                'blame' => user()->id,
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Revoke permission from user
     *
     * @param $morph_id
     * @param $type
     * @param $key
     * @param $value
     * @param $extra
     * @return false
     */
    public static function revoke($morph_id, $type, $key, $value, $extra = null)
    {
        $permission = Permission::where([
            'morph_id' => $morph_id,
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'extra' => $extra,
        ])->first();
        if ($permission) {
            return $permission->delete();
        }

        return false;
    }

    /**
     * @return MorphTo
     */
    public function morph()
    {
        return $this->morphTo();
    }

    /**
     * @return array
     */
    public function getRelatedObject()
    {
        switch ($this->type) {
            case 'server':
                $permType = __('Sunucu');
                $server = Server::find($this->value);
                $permValue = $server ? $server->name : '-';
                break;
            case 'extension':
                $permType = __('Eklenti');
                $extension = Extension::find($this->value);
                $permValue = $extension ? $extension->name : '-';
                break;
            case 'liman':
                $permType = __('Liman');
                $permValue = match ($this->value) {
                    'view_logs' => __('Sunucu Günlük Kayıtlarını Görüntüleme'),
                    'add_server' => __('Sunucu Ekleme'),
                    'server_services' => __('Sunucu Servislerini Görüntüleme'),
                    'server_details' => __('Sunucu Detaylarını Görüntüleme'),
                    'update_server' => __('Sunucu Detaylarını Güncelleme'),
                    default => '-',
                };
                break;
            case 'function':
                $permType = __('Fonksiyon');
                $permValue = $this->value . ' - ' . $this->extra;
                break;
            default:
                $permType = '-';
                $permValue = '-';
                break;
        }

        return [
            'type' => $permType,
            'value' => $permValue,
        ];
    }
}
