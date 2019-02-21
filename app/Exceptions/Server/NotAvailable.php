<?php

namespace App\Exceptions\Server;

use Exception;

class NotAvailable extends Exception
{
    public function report()
    {

    }

    public function render()
    {
        return response("CAN'T CONNECT SERVER");
    }
}
