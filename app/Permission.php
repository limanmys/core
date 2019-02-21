<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * App\Permission
 *
 * @property-read mixed $id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Permission query()
 * @mixin \Eloquent
 */
class Permission extends Eloquent
{
    protected $collection = 'permissions';
    protected $connection = 'mongodb';

    public static function new($user_id)
    {
        // Simply create new permission table
        $permissions = new Permission();
        $permissions->user_id = $user_id;

        // Create empty arrays to use it later.
        $permissions->server = [];
        $permissions->extension = [];
        $permissions->script = [];

        // Save Permissions
        $permissions->save();

        // Return new Permission object.
        return $permissions;
    }

    public static function grant($user_id, $type, $id)
    {
        // Retrieve Permissions
        $permissions = Permission::where('user_id', $user_id)->first();

        // Get Array
        $current = $permissions->__get($type);

        // Add Array
        array_push($current, $id);

        // Set Array
        $permissions->__set($type, $current);

        // Save and Return Permissions
        $permissions->save();

        return $permissions;
    }

    public static function revoke($user_id, $type, $id)
    {
        // Retrieve Permissions
        $permissions = Permission::where('user_id', $user_id)->first();

        // Get Array
        $current = $permissions->__get($type);
        $old = $current;
        // Search and Delete Id
        unset($current[array_search($id, $current)]);
        dd($id,array_search($id, $current),$current, $old);
        // Update Object
        $permissions->__set($type, array_values($current));

        // Save and return object.
        $permissions->save();

        return $permissions;
    }

    public static function get($user_id, $type = null)
    {
        // Retrieve Permissions
        $permissions = Permission::where('user_id', $user_id)->first();

        if ($type == null) {
            return $permissions;
        }

        return $permissions->__get($type);
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
        $permissions = Permission::get($user_id, $type);

        if(!$permissions || !in_array($permissions, $id)){
            return false;
        }

        return true;
    }
}
