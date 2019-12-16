<?php

namespace App;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Permission
 *
 * @property-read mixed $id
 * @method static Builder|Permission newModelQuery()
 * @method static Builder|Permission newQuery()
 * @method static Builder|Permission query()
 * @method static where(string $string, $user_id)
 * @mixin Model
 */

class Permission extends Model
{
    use UsesUuid;

    protected $table = "permissions";    

    protected $fillable = [
        "morph_id", "morph_type", "type", "key", "value", "extra", "blame"
    ];

    public static function can($user_id, $type, $key, $value ,$extra = null)
    {
        $user = User::find($user_id);
        // Verify if user is admin.
        if($user->isAdmin()){
            return true;
        }

        $ids = $user->roles->pluck('role_id')->toArray();
        array_push($ids, $user_id);

        return Permission::whereIn('morph_id', $ids)
            ->where([
                "type" => $type,
                "key" => $key,
                "value" => $value,
                "extra" => $extra
            ])->exists();
    }

    public static function grant($morph_id, $type, $key, $value ,$extra = null, $morph_type="users")
    {
        $permission =  Permission::firstOrCreate([
            "morph_id" => $morph_id,
            "morph_type" => $morph_type,
            "type" => $type,
            "key" => $key,
            "value" => $value,
            "extra" => $extra,
            "blame" => user()->id
        ]);

        return $permission->save();
    }

    public static function revoke($morph_id, $type, $key, $value ,$extra = null)
    {
        $permission = Permission::where([
            "morph_id" => $morph_id,
            "type" => $type,
            "key" => $key,
            "value" => $value,
            "extra" => $extra
        ])->first();
        if($permission){
            return $permission->delete();
        }

        return false;
    }

    public function morph()
    {
        return $this->morphTo();
    }
}
