<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ServerFeature extends Eloquent
{
    protected $collection = 'server_features';
    protected $connection = 'mongodb';

    protected $fillable = ["feature_id", "server_id"];
}
