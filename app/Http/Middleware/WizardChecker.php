<?php

namespace App\Http\Middleware;

use Closure;

class WizardChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $safeRoutes = ['wizard', 'finish_wizard', 'save_wizard'];
        if (
            auth()->check() &&
            auth()->user()->status == 1 &&
            auth()->user()->forceChange != true &&
            env("WIZARD_STEP", 1) != config("liman.wizard_max_steps") &&
            !in_array(
                request()
                    ->route()
                    ->getName(),
                $safeRoutes
            )
        ) {
            return redirect(route('wizard', env("WIZARD_STEP", 1)));
        }
        return $next($request);
    }
}
