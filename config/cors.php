<?php

return [
    'paths' => ['api/*'],
    'allowed_methods'   => ['*'],
    'allowed_origins'   => array_map('trim', explode(',', env('CORS_TRUSTED_ORIGINS', ''))),
    'allowed_headers'   => ['*'],
    'exposed_headers'   => [],
    'max_age'           => 3600,
    'supports_credentials' => true,
];