<?php return [
    'barryvdh/laravel-ide-helper' => [
        'providers' => [
            0 => 'Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider',
        ],
    ],
    'beyondcode/laravel-websockets' => [
        'providers' => [
            0 => 'BeyondCode\\LaravelWebSockets\\WebSocketsServiceProvider',
        ],
        'aliases' => [
            'WebSocketRouter' =>
                'BeyondCode\\LaravelWebSockets\\Facades\\WebSocketRouter',
        ],
    ],
    'facade/ignition' => [
        'providers' => [
            0 => 'Facade\\Ignition\\IgnitionServiceProvider',
        ],
        'aliases' => [
            'Flare' => 'Facade\\Ignition\\Facades\\Flare',
        ],
    ],
    'laravel/tinker' => [
        'providers' => [
            0 => 'Laravel\\Tinker\\TinkerServiceProvider',
        ],
    ],
    'nesbot/carbon' => [
        'providers' => [
            0 => 'Carbon\\Laravel\\ServiceProvider',
        ],
    ],
    'nunomaduro/collision' => [
        'providers' => [
            0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
        ],
    ],
    'spatie/laravel-web-tinker' => [
        'providers' => [
            0 => 'Spatie\\WebTinker\\WebTinkerServiceProvider',
        ],
    ],
];
