<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = false;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        "/lmn/private/extensionApi",
        "/lmn/private/runCommandApi",
        "/lmn/private/putFileApi",
        "/lmn/private/getFileApi",
        "/lmn/private/runScriptApi",
        "/lmn/private/putSession",
        "/lmn/private/reverseProxyRequest",
        "/lmn/private/dispatchJob",
        "/lmn/private/getJobList",
        "/lmn/private/openTunnel",
        "/lmn/private/stopTunnel",
        "/lmn/private/sendNotification",
        "/lmn/private/sendLog",
    ];
}
