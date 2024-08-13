<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

/**
 * Auth Middleware
 *
 * @extends Middleware
 */
class Authenticate extends Middleware
{
    /**
     * @param $request
     * @return void
     */
    protected function redirectTo($request)
    {
        //
    }
}
