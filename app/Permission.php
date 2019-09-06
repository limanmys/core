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
        "user_id", "type", "key", "value", "extra"
    ];

    public static function can($user_id, $type, $key, $value ,$extra = null)
    {
        $user = User::find($user_id);
        // Verify if user is admin.
        if($user->isAdmin()){
            return true;
        }

        return Permission::where([
            "user_id" => $user_id,
            "type" => $type,
            "key" => $key,
            "value" => $value,
            "extra" => $extra
        ])->exists();
    }

    public static function grant($user_id, $type, $key, $value ,$extra = null)
    {
        $permission =  Permission::firstOrCreate([
            "user_id" => $user_id,
            "type" => $type,
            "key" => $key,
            "value" => $value,
            "extra" => $extra
        ]);

        return $permission->save();
    }

    public static function revoke($user_id, $type, $key, $value ,$extra = null)
    {
        $permission = Permission::where([
            "user_id" => $user_id,
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
}
