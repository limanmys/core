<?php

namespace App\Http\Controllers\Wizard;

use App\Http\Controllers\Controller;
use App\User;
use BadMethodCallException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Wizard Controller
 *
 * @extends Controller
 */
class WizardController extends Controller
{
    /**
     * Get step
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse|Response
     */
    public function getStep(Request $request)
    {
        if (auth()->user()->status == 1 && env('WIZARD_STEP', 1) != config('liman.wizard_max_steps')) {
            $method = 'getStep' . $request->step;
            try {
                setEnv(['WIZARD_STEP' => $request->step]);

                return $this->$method($request);
            } catch (BadMethodCallException) {
                return respond('Fonksiyon bulunamadı!', 404);
            }
        } else {
            return redirect()->back();
        }
    }

    /**
     * Save step
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse|Response
     */
    public function saveStep(Request $request)
    {
        if (auth()->user()->status == 1 && env('WIZARD_STEP', 1) != config('liman.wizard_max_steps')) {
            $method = 'setStep' . $request->step;
            try {
                return $this->$method($request);
            } catch (BadMethodCallException) {
                return respond('Fonksiyon bulunamadı!', 404);
            }
        } else {
            return redirect()->back();
        }
    }

    /**
     * Finish installation wizard
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function finish()
    {
        system_log(7, 'LOGOUT_SUCCESS');
        hook('logout_attempt', [
            'user' => user(),
        ]);
        Auth::guard()->logout();
        request()
            ->session()
            ->invalidate();
        request()
            ->session()
            ->regenerateToken();
        request()
            ->session()
            ->flash('status', __('Liman başarıyla kuruldu! Hesabınız ile giriş yapın.'));
        hook('logout_successful');

        return redirect(route('login'));
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    private function getStep1(Request $request)
    {
        return view('wizard.step_' . $request->step, [
            'step' => $request->step,
            'progress' => '10',
            'progressClass' => 'w-1/12',
            'lang' => getenv('APP_LANG'),
        ]);
    }

    /**
     * @param Request $request
     * @return Application|ResponseFactory|RedirectResponse|Response
     */
    private function setStep1(Request $request)
    {
        system_log(7, 'SET_LOCALE');
        $languages = getLanguages();
        if (
            request()->has('locale') &&
            in_array(request('locale'), $languages)
        ) {
            setEnv(['APP_LANG' => $request->locale]);
            \Session::put('locale', $request->locale);
            auth()->user()->update([
                'locale' => $request->locale,
            ]);

            return redirect()->back();
        } else {
            return response('Language not found', 404);
        }

        return respond('OK', 200);
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    private function getStep2(Request $request)
    {
        return view('wizard.step_' . $request->step, [
            'step' => $request->step,
            'progress' => '30',
            'progressClass' => 'w-4/12',
            'onclick' => 'createUser()',
            'skip' => true,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|Response
     */
    private function setStep2(Request $request)
    {
        try {
            request()->validate([
                'name' => 'required|string|max:60',
                'email' => 'required|email',
                'confirm' => 'required|same:password',
                'username' => 'required|string|max:35',
                'password' => [
                    'required',
                    'string',
                    'min:10',
                    'max:32',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/',
                ],
            ]);

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => $request->username,
                'locale' => getenv('APP_LANG'),
                'auth_type' => 'local',
                'forceChange' => 'false',
                'status' => '1',
            ]);

            return respond('Kullanıcı başarıyla eklendi.', 200);
        } catch (\Throwable) {
            return respond('Kullanıcı eklenemedi!', 201);
        }
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    private function getStep3(Request $request)
    {
        return view('wizard.step_' . $request->step, [
            'step' => $request->step,
            'progress' => '70',
            'progressClass' => 'w-8/12',
            'skip' => true,
        ]);
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    private function getStep4(Request $request)
    {
        return view('wizard.step_' . $request->step, [
            'step' => $request->step,
        ]);
    }
}
