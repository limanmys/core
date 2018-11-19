<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Feature extends Eloquent
{
    protected $collection = 'features';
    protected $connection = 'mongodb';
}
