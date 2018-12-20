<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LimanRequest extends Eloquent
{
    protected $collection = 'requests';
    protected $connection = 'mongodb';
}
