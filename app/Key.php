<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class Key
 * @package App
 * @method static \Illuminate\Database\Query\Builder|\App\Key where($field, $value)
 */
class Key extends Eloquent
{
    protected $collection = 'keys';
    protected $connection = 'mongodb';
    protected $fillable = ['username' ,'server_id'];

}
