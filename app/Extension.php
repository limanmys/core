<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class Extension
 * @package App
 * @method static \Illuminate\Database\Query\Builder|\App\Extension where($field, $value)
 * @method static \Illuminate\Database\Query\Builder|\App\Extension find($field)
 * @method static \Illuminate\Database\Query\Builder|\App\Extension delete()
 */
class Extension extends Eloquent
{
    /**
     * @var string
     */
    protected $collection = 'extensions';
    /**
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * @var array
     */
    protected $fillable = [
        "name" , "status" , "service" , "icon", "publisher", "support", "serverless",
        "views", "version", "database", "widgets"
    ];

    /**
     * @param $id
     * @return mixed
     */
    public static function one($id)
    {
        // Find Object
        $extension = Extension::find($id);

        // If object is not found, abort
        if($extension == null){
            abort(504,"Eklenti Bulunamadı");
        }

        return $extension;
    }

    /**
     * @param null $city
     * @param null $extension_id
     * @return mixed
     */
    public function servers($city = null, $extension_id = null)
    {
        // Get all Servers which have this extension.
        if($city){
            return Server::getAll()->where('city',$city)->filter(function($value){
                return array_key_exists(request('extension_id'),$value->extensions);
            });
        }
        return Server::getAll()->filter(function($value){
            return array_key_exists($this->_id,$value->extensions);
        });
    }


    /**
     * @param null $name
     * @return Script[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function scripts($name = null){
        return Script::all()->where('extensions','like',strtolower(($name == null) ? extension()->name : $name));
    }

    /**
     * @param array $coloumns
     * @return array
     */
    public static function getAll($coloumns = [])
    {
        $extensions = Extension::all($coloumns);
        return Extension::filterPermissions($extensions);
    }

    /**
     * @param $raw_extensions
     * @return array
     */
    public static function filterPermissions($raw_extensions)
    {
        // Ignore permissions if user is admin.
        if (auth()->user()->isAdmin()) {
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
