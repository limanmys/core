<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
    protected $table = "permissions";

    public static function grant($user_id, $type, $id)
    {
        $database = DB::table("permissions");
        if($database->where([
            $type => $id,
            "user_id" => $user_id
        ])->exists()){
            return true;
        }

        return $database->insert([
            "user_id" => $user_id,
            $type => $id,
            "created_at" =>  Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
    }

    public static function revoke($user_id, $type, $id)
    {
        $database = DB::table("permissions");
        if($database->where([
            $type => $id,
            "user_id" => $user_id
        ])->exists()){
            $database->where([
                $type => $id,
                "user_id" => $user_id
            ])->delete();
            return true;
        }
        return false;
    }

    public static function get($user_id, $type = null)
    {
        $database = DB::table("permissions");
        return $database->where([
            "user_id" => $user_id
        ])->whereNotNull($type)->get();
    }

    public static function getUsersofType($id, $type)
    {
        // Get User Id's as array
        $users = Permission::where($type, 'like', $id)->pluck('user_id')->all();

        // Retrieve objects using that array.
        return User::findMany($users);
    }

    public static function can($user_id, $type, $id)
    {
        if(User::find($user_id)->isAdmin()){
            return true;
        }

        if($type != "function"){
            $type = $type . "_id";
        }
        $database = DB::table("permissions");
        return $database->where([
            "user_id" => $user_id,
            $type => $id
        ])->exists();
    }
}
