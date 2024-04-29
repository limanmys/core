<?php

namespace App\Classes\Authentication;

use Illuminate\Http\JsonResponse;

interface AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse;
}
