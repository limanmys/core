<?php

return [
    'server_connection_timeout' => 5000, //ms
    'default_views' => [
        'sidebar' => 'servers',
        'dashboard' => [
            'servers',
            'users',
            'version',
            'most_used_extensions',
            'most_used_servers',
        ],
    ],
    'search' => [
        'admin' => [
            [
                'name' => 'Sistem Ayarları',
                'children' => [
                    [
                        'name' => 'Eklentiler',
                        'url' => '/settings/extensions'
                    ],
                    [
                        'name' => 'Kullanıcılar',
                        'url' => '/settings/users'
                    ],
                    [
                        'name' => 'Roller',
                        'url' => '/settings/roles'
                    ],
                    [
                        'name' => 'E-Posta',
                        'url' => '/settings/mail'
                    ],
                    [
                        'name' => 'Bildirimler',
                        'url' => '/settings/notifications'
                    ],
                    [
                        'name' => 'Abonelikler',
                        'url' => '/settings/subscriptions'
                    ],
                    [
                        'name' => 'Erişim',
                        'url' => '/settings/access'
                    ],
                    [
                        'name' => 'Sağlık Durumu',
                        'url' => '/settings/health'
                    ],
                    [
                        'name' => 'Gelişmiş Ayarlar',
                        'url' => '/settings/advanced'
                    ]
                ]
            ]
        ],
        'user' => [

        ],
        'common' => [
            [
                'name' => 'Ayarlar',
                'url' => '/settings'
            ],
            [
                'name' => 'Kullanıcı Ayarları',
                'children' => [
                    [
                        'name' => 'Profil',
                        'url' => '/settings/profile'
                    ],
                    [
                        'name' => 'Kasa',
                        'url' => '/settings/vault'
                    ],
                    [
                        'name' => 'Kişisel Erişim Anahtarları',
                        'url' => '/settings/tokens'
                    ]
                ]
            ]
        ]
    ]
];
