<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ExtensionSettings extends Eloquent
{
    protected $collection = 'feature_settings';
    protected $connection = 'mongodb';

}
