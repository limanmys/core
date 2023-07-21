<?php

return [
    'server_connection_timeout' => 5000, //ms
    'wizard_max_steps' => 4,
    'admin_searchable' => [
        [
            'name' => 'Sistem Ayarları',
            'url' => '/ayarlar',
        ],
        [
            'name' => 'Kullanıcı Ayarları',
            'url' => '/ayarlar#users',
        ],
        [
            'name' => 'Sistem Ayarları / Eklentiler',
            'url' => '/ayarlar#extensions',
        ],
        [
            'name' => 'Sistem Ayarları / Rol Grupları',
            'url' => '/ayarlar#roles',
        ],
        [
            'name' => 'Sistem Ayarları / Sertifikalar',
            'url' => '/ayarlar#certificates',
        ],
        [
            'name' => 'Sistem Ayarları / Sağlık Durumu',
            'url' => '/ayarlar#health',
        ],
        [
            'name' => 'Sistem Ayarları / DNS',
            'url' => '/ayarlar#dnsSettings',
        ],
        [
            'name' => 'Sistem Ayarları / Mail Ayarları',
            'url' => '/ayarlar#mailSettings',
        ],
        [
            'name' => 'Sistem Ayarları / İnce Ayarlar',
            'url' => '/ayarlar#limanTweaks',
        ],
        [
            'name' => 'Yetki Talepleri',
            'url' => '/talepler',
        ],
    ],
    'user_searchable' => [
        [
            'name' => 'Kullanıcı Profili',
            'url' => '/profil',
        ],
        [
            'name' => 'Kasa',
            'url' => '/kasa',
        ],
        [
            'name' => 'Erişim Anahtarları',
            'url' => '/profil/anahtarlarim',
        ],
        [
            'name' => 'Yetki Talebi',
            'url' => '/taleplerim',
        ],
    ],
    'new_search' => [
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
                        'url' => '/settings/access_tokens'
                    ]
                ]
            ]
        ]
    ]
];
