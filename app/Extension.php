<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Class Extension
 * @package App
 * @method static Builder|Extension where($field, $value)
 * @method static Builder|Extension find($field)
 * @method static Builder|Extension delete()
 */
class Extension extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "name" , "version", "icon", "service"
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
     * @return mixed
     */
    public function servers($city = null)
    {
        // Get all Servers which have this extension.
        if($city){
            return Server::getAll()->where('city',$city)->filter(function($value){
                return DB::table('server_extensions')->where([
                    "server_id" => $value->id,
                    "extension_id" => request("extension_id")
                ])->exists();
            });
        }
        return Server::getAll()->filter(function($value){
            return DB::table('server_extensions')->where([
                "server_id" => $value->id,
                "extension_id" => request("extension_id")
            ])->exists();
        });
    }


    /**
     * @param null $name
     * @return Script[]|Collection
     */
    public static function scripts($name = null){
        return Script::all()->where('extensions','like',strtolower(($name == null) ? extension()->name : $name));
    }

    /**
     * @param array $coloumns
     * @return Extension|Extension[]|Collection|Builder
     */
    public static function getAll($coloumns = [])
    {
        if(auth()->user()->isAdmin()){
            return Extension::all();
        }
        return Extension::find(DB::table("permissions")
            ->whereNotNull("extension_id")->pluck("extension_id")->toArray());
    }

}
