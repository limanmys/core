<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

/**
 * Encrypt cookies
 *
 * @extends Middleware
 */
class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        // We are using this in order to allow WebSSH to read unencrypted xsrf cookie.
        '_xsrf',
        'currentUser'
    ];
}
