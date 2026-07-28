<?php

$clients = json_decode((string) env('AUTH_HANDOFF_CLIENTS', '{}'), true);

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted authentication handoff clients
    |--------------------------------------------------------------------------
    |
    | Clients are configured as a JSON object keyed by a stable client ID:
    |
    | {
    |   "netex": {
    |     "secret": "<at least 32 random characters>",
    |     "redirect_uris": ["https://netex.example/auth/liman/callback"]
    |   }
    | }
    |
    | Redirect URIs are matched exactly. JWTs are never sent through the
    | browser; the registered backend redeems a short-lived, single-use code.
    |
    */
    'clients' => is_array($clients) ? $clients : [],

    // Keep browser-visible authorization codes short-lived.
    'code_ttl' => max(30, min(300, (int) env('AUTH_HANDOFF_CODE_TTL', 60))),

    // HTTP callback URIs are only useful for explicit local development.
    'allow_insecure_loopback' => filter_var(
        env('AUTH_HANDOFF_ALLOW_INSECURE_LOOPBACK', false),
        FILTER_VALIDATE_BOOLEAN,
    ),
];
