<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

/**
 * Auth Middleware
 * This function is empty because of Laravel
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
