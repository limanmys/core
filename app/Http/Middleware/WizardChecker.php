<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Wizard Checker Middleware
 *
 * This middleware checks if user completed the setup wizard or not
 */
class WizardChecker
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $safeRoutes = ['wizard', 'finish_wizard', 'save_wizard'];

        if (
            $request->session()->get('wizard_check') &&
            env('WIZARD_STEP', 1) == config('liman.wizard_max_steps')
        ) {
            return $next($request);
        }

        if (
            auth()->check() &&
            auth()->user()->status == 1 &&
            auth()->user()->forceChange != true &&
            env('WIZARD_STEP', 1) != config('liman.wizard_max_steps') &&
            ! in_array(
                request()
                    ->route()
                    ->getName(),
                $safeRoutes
            )
        ) {
            return redirect(route('wizard', env('WIZARD_STEP', 1)));
        } else {
            $request->session()->put('wizard_check', true);
        }

        return $next($request);
    }
}
