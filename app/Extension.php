<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Extension extends Eloquent
{
    protected $collection = 'extensions';
    protected $connection = 'mongodb';

    protected $fillable = [
        "name" , "status" , "service" , "icon", "publisher", "support", "serverless", "setup", "views", "parameters"
    ];
}
