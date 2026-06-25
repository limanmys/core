<?php

namespace App\Http\Controllers\API;

use App\Classes\Authentication\KeycloakAuthenticator;
use App\Classes\Authentication\LDAPAuthenticator;
use App\Classes\Authentication\LimanAuthenticator;
use App\Classes\Authentication\OIDCAuthenticator;
use App\Http\Controllers\Controller;
use App\Models\SystemSettings;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(
            'auth:api',
            ['except' => 
                [
                    'login', 
                    'activeAuthTypes',
                    'forceChangePassword', 
                    'setupTwoFactorAuthentication', 
                    'sendPasswordResetLink', 
                    'resetPassword',
                    'loginBranding',
                    'authGate',
                    'logout',
                    'oidcCallback',
                ]
            ]
        );
    }

    /**
     * Active authentication types
     */
    public function activeAuthTypes()
    {
        $types = ['liman'];

        if ((bool) env('KEYCLOAK_ACTIVE')) {
            $types[] = 'keycloak';
        }

        if ((bool) env('LDAP_STATUS')) {
            $types[] = 'ldap';
        }

        if ((bool) env('OIDC_ACTIVE')) {
            $types[] = 'oidc';
        }

        return $types;
    }

    /**
     * Return login screen branding
     */
    public function loginBranding()
    {
        return response()->json([
            'image' => SystemSettings::where('key', 'LOGIN_IMAGE')->first()?->data ?? '',
        ]);
    }

    /**
     * Get default auth gate
     */
    public function authGate()
    {
        return response()->json(env('DEFAULT_AUTH_GATE', 'liman'));
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate request
        if ($request->type === 'oidc') {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string',
                'redirect_path' => 'nullable|string',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string',
                'password' => 'required|string',
            ]);
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // I wish there is a better way to type hint this
        $authenticator = null;
        switch ($request->type) {
            case 'keycloak':
                $authenticator = new KeycloakAuthenticator();
                break;
            case 'ldap':
                $authenticator = new LDAPAuthenticator();
                break;
            case 'oidc':
                $authenticator = new OIDCAuthenticator();
                break;
            default:
                $authenticator = new LimanAuthenticator();
                break;
        }

        $token = $authenticator->authenticate($validator->validated(), $request);

        if ($request->type === 'oidc' && isset($token->original['redirect_required'])) {
            return $token;
        }

        if (! auth('api')->user()) {
            return $token;
        }

        if (auth('api')->user()->otp_enabled) {
            $tfa = app('pragmarx.google2fa');

            if (auth('api')->user()->google2fa_secret == null) {
                $secret = $tfa->generateSecretKey();
                auth('api')->user()->update([
                    'google2fa_secret' => $secret,
                ]);
                return response()->json([
                    'message' => 'İki faktörlü doğrulama için Google Authenticator uygulaması ile QR kodunu okutunuz.',
                    'image' => $tfa->getQRCodeInline(
                        "Liman",
                        auth('api')->user()->email,
                        $secret,
                        400
                    ),
                ], 402);
            }

            if (! $request->token) {
                return response()->json(['message' => 'İki faktörlü doğrulama gerekmektedir.'], 406);
            } else {
                if (! $tfa->verifyGoogle2FA(
                    auth('api')->user()->google2fa_secret,
                    $request->token
                )) {
                    return response()->json(['message' => 'İki faktörlü doğrulama başarısız.'], 406);
                }
            }
        }

        return $token;
    }

    public function oidcCallback(Request $request)
    {
        return OIDCAuthenticator::handleCallback($request);
    }

    /**
     * Setup Two Factor Authentication
     * 
     * @return JsonResponse
     */
    public function setupTwoFactorAuthentication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where(function ($query) use ($validator) {
                $query->where('email', $validator->validated()["email"])
                    ->orWhere('username', $validator->validated()["email"]);
            })->first();

        if (! $user) {
            // Timing normalization: 'kullanıcı yok' ve 'parola yanlış' sürelerini eşitle.
            // Hash::check, "parola yanlış" path'inde çalıştırılan süre (~50ms) kadar zaman alır.
            Hash::check(
                'invalid',
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            );
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        $authToken = auth('api')->attempt([
            'email' => $user->email,
            'password' => $validator->validated()["password"],
        ]);
        if (! $authToken) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        $authenticatedUser = auth('api')->user();
        if (! $authenticatedUser->google2fa_secret) {
            return response()->json(['message' => '2FA kurulum süreci başlatılmamış. Lütfen önce giriş yapınız.'], 422);
        }

        $tfa = app('pragmarx.google2fa');
        if (! $tfa->verifyGoogle2FA($authenticatedUser->google2fa_secret, $request->token)) {
            return response()->json(['message' => 'OTP doğrulama başarısız. QR kodunu tekrar okutup deneyiniz.'], 422);
        }

        $authenticatedUser->update([
            'otp_enabled' => true,
        ]);

        return response()->json(['message' => '2FA kurulumu başarıyla yapıldı.']);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $deleteToken = Cookie::forget('token', '/', $request->getHost());
        $deleteCurrentUser = Cookie::forget('currentUser', '/', $request->getHost());
        try {
            auth('api')->logout();
        } catch (\Throwable $e) {}

        if (env('LOGOUT_REDIRECT_URL') != '') {
            return response()->json([
                'message' => 'User successfully signed out',
                'redirect' => env('LOGOUT_REDIRECT_URL')
            ])
            ->withCookie($deleteToken)
            ->withCookie($deleteCurrentUser)
            ->withoutCookie('token')
            ->withoutCookie('currentUser');
        }

        return response()->json(['message' => 'User successfully signed out'])
            ->withCookie($deleteToken)
            ->withCookie($deleteCurrentUser)
            ->withoutCookie('token')
            ->withoutCookie('currentUser');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Force change password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'new_password' => ['required', 'string', new StrongPassword],
        ], [
            'new_password.regex' => 'Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı, özel karakter ve büyük harf içermelidir.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where(function ($query) use ($request) {
                    $query->where('email', $request->email)
                        ->orWhere('username', $request->email);
                })->where('auth_type', 'local')
                  ->first();

        if (! $user) {
            // Timing normalization: 'kullanıcı yok' ve 'parola yanlış' sürelerini eşitle.
            Hash::check(
                'invalid',
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            );
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        $token = auth('api')->attempt([
            'email' => $user->email,
            'password' => $request->password,
        ]);
        if (! $token) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        // If new_password is same as password return error
        if (Hash::check($request->new_password, auth('api')->user()->password)) {
            return response()->json(['message' => 'Yeni şifreniz eski şifreniz ile aynı olamaz.'], 405);
        }

        $user = auth('api')->user();
        $user->forceChange = false;
        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Şifreniz başarıyla değiştirildi.']);
    }

    /**
     * Send password reset link
     */
    public function sendPasswordResetLink(Request $request)
    {  
        // Check email exists on database laravel validator
        validate([
            'email' => 'required|email',
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // Do nothing
        }

        return response()->json(['message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.']);
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                new StrongPassword,
                'confirmed',
            ],
        ]);
     
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
     
                $user->save();
     
                event(new PasswordReset($user));
            }
        );
     
        return $status === Password::PASSWORD_RESET
                    ? response()->json(['message' => 'Şifreniz başarıyla değiştirildi.'])
                    : response()->json(['message' => 'Şifre sıfırlama bağlantısı geçersiz.'], 401);
    }
}
