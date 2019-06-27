<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Class Key
 * @package App
 * @method static Builder|Key where($field, $value)
 */
class Key extends Model
{
    protected $fillable = ['username' ,'server_id'];

}
