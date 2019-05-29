<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\LimanRequest
 *
 * @property-read mixed $id
 * @method static Builder|LimanRequest newModelQuery()
 * @method static Builder|LimanRequest newQuery()
 * @method static Builder|LimanRequest query()
 * @method static Builder|LimanRequest where($value, $key)
 * @mixin Eloquent
 */
class LimanRequest extends Eloquent
{
    protected $collection = 'requests';
    protected $connection = 'mongodb';
}
