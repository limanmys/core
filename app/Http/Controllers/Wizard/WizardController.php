<?php

namespace App\Http\Controllers\Wizard;

use App\Http\Controllers\Controller;
use App\User;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator as Validator;

class WizardController extends Controller
{
    public function getStep(Request $request)
    {
        if (auth()->user()->status == 1 && env("WIZARD_STEP", 1) != config("liman.wizard_max_steps")) {
            $method = "getStep" . $request->step;
            try {
                setEnv(["WIZARD_STEP" => $request->step]);
                return $this->$method($request);
            } catch (BadMethodCallException $e) {
                return respond("Fonksiyon bulunamadı!", 404);
            }
        } else {
            return redirect()->back();
        }        
    }

    public function saveStep(Request $request)
    {
        if (auth()->user()->status == 1 && env("WIZARD_STEP", 1) != config("liman.wizard_max_steps")) {
            $method = "setStep" . $request->step;
            try {
                return $this->$method($request);
            } catch (BadMethodCallException $e) {
                return respond("Fonksiyon bulunamadı!", 404);
            }   
        } else {
            return redirect()->back();
        }  
    }

    private function getStep1(Request $request)
    {
        return view("wizard.step_" . $request->step, [
            "step" => $request->step,
            "progress" => "10",
            "progressClass" => "w-1/12",
            "lang" => getEnv("APP_LANG")
        ]);
    }

    private function setStep1(Request $request)
    {
        system_log(7, "SET_LOCALE");
        $languages = ["tr", "en"];
        if (
            request()->has('locale') &&
            in_array(request('locale'), $languages)
        ) {
            setEnv(["APP_LANG" => $request->locale]);
            \Session::put('locale', $request->locale);
            auth()->user()->update([
                "locale" => $request->locale
            ]);
            return redirect()->back();
        } else {
            return response('Language not found', 404);
        }
        return respond("OK", 200);
    }

    private function getStep2(Request $request)
    {
        return view("wizard.step_" . $request->step, [
            "step" => $request->step,
            "progress" => "30",
            "progressClass" => "w-4/12",
            "onclick" => "createUser()",
            "skip" => true
        ]);
    }

    private function setStep2(Request $request)
    {
        try {
            request()->validate([
                "name" => "required|string|max:60",
                "email" => "required|email",
                "confirm" => "required|same:password",
                "username" => "required|string|max:35",
                'password' => [
                    'required',
                    'string',
                    'min:10',
                    'max:32',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[\[\]\(\)\{\}\#\?\%\&\*\+\,\-\.\/\:\;\<\=\>\@\^\_\`\~]).{10,}$/',
                ]
            ]);

            User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "username" => $request->username,
                "locale" => getEnv("APP_LANG"),
                "auth_type" => "local",
                "forceChange" => "false",
                "status" => "1"
            ]);
            return respond("Kullanıcı başarıyla eklendi.", 200);
        } catch (\Throwable $e) {
            return respond("Kullanıcı eklenemedi!", 201);
        }
    }

    private function getStep3(Request $request)
    {
        return view("wizard.step_" . $request->step, [
            "step" => $request->step,
            "progress" => "70",
            "progressClass" => "w-8/12",
            "skip" => true
        ]);
    }

    private function getStep4(Request $request)
    {
        return view("wizard.step_" . $request->step, [
            "step" => $request->step
        ]);
    }

    public function finish()
    {
        system_log(7, "LOGOUT_SUCCESS");
        hook('logout_attempt', [
            "user" => user(),
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
            ->flash('status', __("Liman başarıyla kuruldu! Hesabınız ile giriş yapın."));
        hook('logout_successful');
        return redirect(route('login'));
    }
}
