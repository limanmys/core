<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Extension extends Eloquent
{
    protected $collection = 'features';
    protected $connection = 'mongodb';
}
