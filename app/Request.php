<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Request extends Eloquent
{
    protected $collection = 'requests';
    protected $connection = 'mongodb';
}
