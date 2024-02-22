<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Handler
 *
 * @extends ExceptionHandler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Use validator response hack
        $this->renderable(function (JsonResponseException $e) {
            return response()->json($e->getData(), $e->getCode() ? $e->getCode() : Response::HTTP_OK);
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Kayıt bulunamadı.'
                ], 404);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Kayıt bulunamadı.'
                ], 404);
            }
        });

        $this->renderable(function (QueryException $e) {
            return response()->json([
                'message' => 'Veritabanı hatası mevcut. Sistem veritabanı bağlantısını kontrol ediniz.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $this->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'message' => 'Çok fazla istek gönderdiniz. Lütfen biraz bekleyin.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        });

        $this->renderable(function (HttpException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'message' => 'Giriş yapmanız gereklidir.'
            ], Response::HTTP_UNAUTHORIZED)
                ->withoutCookie('token')
                ->withoutCookie('currentUser');
        });

        if (config('app.debug')) {
            $this->renderable(function (Throwable $e) {
                return response()->json([
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            });
        }
        
        $this->renderable(function (Throwable $e) {
            return response()->json([
                'type' => get_class($e),
                'message' => 'Beklenmeyen bir hata oluştu. Sistem yöneticinize başvurunuz.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
