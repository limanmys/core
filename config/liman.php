<?php

return [
    "server_connection_timeout" => 5000, //ms
    "user_widget_count" => 10,
    "nav_extension_hide_count" => 10,
    "widget_refresh_time" => 30000, //ms
    "admin_searchable" => [
        [
            'name' => 'Sistem Ayarları',
            'url' =>  '/ayarlar'     
        ],
        [
            'name' => 'Kullanıcı Ayarları',
            'url' => '/ayarlar#users'
        ],
        [
            'name' => 'Sistem Ayarları / Eklentiler',
            'url' => '/ayarlar#extensions'
        ],
        [
            'name' => 'Sistem Ayarları / Rol Grupları',
            'url' => '/ayarlar#roles'
        ],
        [
            'name' => 'Sistem Ayarları / Sunucu Grupları',
            'url' => '/ayarlar#serverGroups'
        ],
        [
            'name' => 'Sistem Ayarları / Sertifikalar',
            'url' => '/ayarlar#certificates'
        ],
        [
            'name' => 'Sistem Ayarları / Sağlık Durumu',
            'url' => '/ayarlar#health'
        ],
        [
            'name' => 'Sistem Ayarları / Dış Bildirimler',
            'url' => '/ayarlar#externalNotifications'
        ],
        [
            'name' => 'Sistem Ayarları / Kısıtlı Mod',
            'url' => '/ayarlar#restrictedMode'
        ],
        [
            'name' => 'Sistem Ayarları / Liman Market',
            'url' => '/ayarlar#limanMarket'
        ],
        [
            'name' => 'Sistem Ayarları / DNS',
            'url' => '/ayarlar#dnsSettings'
        ],
        [
            'name' => 'Sistem Ayarları / Mail Ayarları',
            'url' => '/ayarlar#mailSettings'
        ],
        [
            'name' => 'Sistem Ayarları / İnce Ayarlar',
            'url' => '/ayarlar#limanTweaks'
        ],
        [
            'name' => 'Eklenti Mağazası',
            'url' =>  '/market'   
        ],
        [
            'name' => 'Market',
            'url' => '/market'
        ],
        [
            'name' => 'Yetki Talepleri',
            'url' => '/talepler'
        ],
    ],
    "user_searchable" => [
        [
            'name' => 'Kullanıcı Profili',
            'url' => '/profil'
        ],
        [
            'name' => 'Kasa',
            'url' => '/kasa'
        ],
        [
            'name' => 'Erişim Anahtarları',
            'url' => '/profil/anahtarlarim'
        ],
        [
            'name' => 'Yetki Talebi',
            'url' => '/taleplerim'
        ],
    ]
];
