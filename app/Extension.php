<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Extension extends Eloquent
{
    protected $collection = 'extensions';
    protected $connection = 'mongodb';

    protected $fillable = [
        "name" , "status" , "service" , "icon", "publisher", "support", "serverless", "setup", "views", "parameters" , "install" , "install_script"
    ];

    public static function one($id)
    {
        // Find Object
        $extension = Extension::find($id);

        // If object is not found, abort
        if($extension == null){
            abort(404);
        }

        return $extension;
    }

    public static function servers($city = null)
    {
        // Get all Servers which have this extension.
        if($city){
            return Server::getAll()->where('city',$city)->filter(function($value){
                return array_key_exists(request('extension_id'),$value->extensions);
            });
        }
        return Server::getAll()->filter(function($value){
            return array_key_exists(request('extension_id'),$value->extensions);
        });
    }

    public static function scripts(){
        return Script::all()->where('extensions','like',strtolower(extension()->name));
    }

    public static function getAll($coloumns = [])
    {
        $extensions = Extension::all($coloumns);
        return Extension::filterPermissions($extensions);
    }

    public static function filterPermissions($raw_extensions)
    {
        // Ignore permissions if user is admin.
        if (\Auth::user()->isAdmin()) {
            return $raw_extensions;
        }

        // Get permissions from middleware.
        $permissions = request('permissions');

        // Create new array for permitted servers
        $extensions = [];

        // Loop through each server and add permitted ones in servers array.
        foreach ($raw_extensions as $extension) {
            if (in_array($extension->_id, $permissions->extension)) {
                array_push($extensions, $extension);
            }
        }
        return $extensions;
    }
}
