<?php

namespace App\Exceptions\Key;

use Exception;

class NotFound extends Exception
{
    public function report()
    {

    }

    public function render()
    {
        return 'BULUNAMADI';
    }
}
