<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Permission extends Eloquent
{
    protected $collection = 'permissions';
    protected $connection = 'mongodb';
}
