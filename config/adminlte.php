<?php

return [

    'title' => 'Liman Sistem Yönetimi',

    'title_prefix' => '',

    'title_postfix' => '',

    'logo' => '<b>Liman</b>',

    'logo_mini' => '<b>L</b>',

    'skin' => 'blue',

    'layout' => null,

    'collapse_sidebar' => false,

    'dashboard_url' => '/',

    'logout_url' => 'cikis',

    'login_url' => 'giris',

    'register_url' => 'register',

    'menu' => [

    ],

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SubmenuFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
    ],

    'plugins' => [
        'datatables' => true,
        'chartjs'    => true,
    ],
];
