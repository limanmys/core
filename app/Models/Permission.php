<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use App\User;
use Illuminate\Database\Eloquent\Model;

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

    public static function can($user_id, $type, $key, $value, $extra = null)
    {
        $user = User::find($user_id);
        // Verify if user is admin.
        if ($user->isAdmin()) {
            return true;
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

    public static function grant(
        $morph_id,
        $type,
        $key,
        $value,
        $extra = null,
        $morph_type = 'users'
    ) {
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
        } catch (\Throwable $e) {
            return false;
        }
    }

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

    public function morph()
    {
        return $this->morphTo();
    }

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
                switch ($this->value) {
                    case 'view_logs':
                        $permValue = __('Sunucu Günlük Kayıtlarını Görüntüleme');
                        break;
                    case 'add_server':
                        $permValue = __('Sunucu Ekleme');
                        break;
                    case 'server_services':
                        $permValue = __('Sunucu Servislerini Görüntüleme');
                        break;
                    case 'server_details':
                        $permValue = __('Sunucu Detaylarını Görüntüleme');
                        break;
                    case 'update_server':
                        $permValue = __('Sunucu Detaylarını Güncelleme');
                        break;
                    default:
                        $permValue = '-';
                        break;
                }
                break;
            case 'function':
                $permType = __('Fonksiyon');
                $permValue = $this->value.' - '.$this->extra;
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
