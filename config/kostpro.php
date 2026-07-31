<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the login page shows the seeded demo account credentials so
    | visitors can explore the app quickly. Self-hosters should disable this in
    | production by setting KOSTPRO_DEMO=false.
    |
    */

    'demo' => (bool) env('KOSTPRO_DEMO', true),

    /*
    |--------------------------------------------------------------------------
    | Demo Accounts
    |--------------------------------------------------------------------------
    |
    | Credentials surfaced on the login page while demo mode is on. These match
    | the accounts created by database/seeders/DemoSeeder.php.
    |
    */

    'demo_accounts' => [
        ['role' => 'Owner', 'email' => 'owner@kostpro.test', 'password' => 'password'],
        ['role' => 'Penyewa', 'email' => 'penyewa@kostpro.test', 'password' => 'password'],
    ],

];
