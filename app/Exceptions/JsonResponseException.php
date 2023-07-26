<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;

/**
 * JsonResponseException
 *
 * A little hack for throwing JSON data.
 */
class JsonResponseException extends Exception
{
    private $data;

    public function __construct($data, $message = null, $code = 0, $previous = null) {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function getData()
    {
        return $this->data;
    }
}
