<?php

return [
    'default_country_name' => 'Colombia',

    /*
    |--------------------------------------------------------------------------
    | Country Landing Pages
    |--------------------------------------------------------------------------
    |
    | Key: country slug under /mariachis/{slug}
    | Value: country display name
    |
    */
    'country_pages' => [
        'colombia' => 'Colombia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Featured Cities By Country
    |--------------------------------------------------------------------------
    |
    | City slugs to prioritize on each country landing.
    | If empty, cities are ordered automatically by number of published listings.
    |
    */
    'country_featured_cities' => [
        'colombia' => [
            // 'bogota',
            // 'medellin',
            // 'cali',
            // 'cartagena',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Reserved Slugs
    |--------------------------------------------------------------------------
    |
    | These slugs are blocked from SEO landing resolution under /mariachis/*
    | to avoid conflicts with functional/system routes.
    |
    */
    'reserved_slugs' => [
        'login',
        'registro',
        'recuperar-contrasena',
        'restablecer-contrasena',
        'mi-cuenta',
        'admin',
        'mariachi',
        'panel',
        'dashboard',
        'perfil',
        'profile',
        'auth',
        'logout',
        'cliente',
        'staff',
        'blog',
        'lang',
    ],
];
