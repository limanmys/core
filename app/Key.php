<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Query\Builder;

/**
 * Class Key
 * @package App
 * @method static Builder|Key where($field, $value)
 */
class Key extends Eloquent
{
    protected $collection = 'keys';
    protected $connection = 'mongodb';
    protected $fillable = ['username' ,'server_id'];

}
