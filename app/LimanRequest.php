<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * App\LimanRequest
 *
 * @property-read mixed $id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\LimanRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\LimanRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\LimanRequest query()
 * @mixin \Eloquent
 */
class LimanRequest extends Eloquent
{
    protected $collection = 'requests';
    protected $connection = 'mongodb';
}
