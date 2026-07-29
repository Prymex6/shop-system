<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Previously this file didn't exist in the repo at all — the app
    | silently relied on Laravel's vendor default, undocumented and
    | unversioned here. The values below are exactly those defaults, made
    | explicit on purpose:
    |
    |   - 'paths' only matches a handful of public, throttled helper
    |     endpoints (/api/szukaj-podpowiedzi, /api/cart/track,
    |     /api/address-suggest) — no session-authenticated route
    |     (/manager/**, /staff/**, /moje-konto/**) matches 'api/*', so CORS
    |     doesn't touch them at all.
    |   - allowed_origins: '*' combined with supports_credentials: false is
    |     the safe, deliberate Laravel pattern for a public API that never
    |     carries cookies — NOT the dangerous combination (wildcard origin +
    |     credentials: true) this file exists to guard against by being
    |     visible.
    |
    | If a future feature needs cross-origin requests WITH cookies (a SPA
    | integration via Sanctum, cross-subdomain session sharing), narrow
    | allowed_origins to the tenant's actual subdomain/custom_domain before
    | ever flipping supports_credentials to true — never both wildcard
    | origins and credentials together.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
