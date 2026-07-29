<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform contact
    |--------------------------------------------------------------------------
    |
    | Who the platform itself is, as opposed to any one shop on it. The address
    | below identifies this installation when it calls a third-party API that
    | asks callers to say who they are, such as OpenStreetMap's Nominatim.
    |
    */

    'contact_email' => env('PLATFORM_CONTACT_EMAIL', 'contact@example.com'),

    /*
    |--------------------------------------------------------------------------
    | First administrator
    |--------------------------------------------------------------------------
    |
    | The landlord account the seeder creates on a fresh installation. Leaving
    | the password unset is deliberate: a fixed default would be the same on
    | every deployment that never got round to changing it.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
