<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Portal Password
    |--------------------------------------------------------------------------
    |
    | When staff provision a penyewa portal login without typing a password,
    | this value is used instead. The portal shows a "change your password"
    | banner while the account still matches this default.
    |
    */

    'default_password' => env('PORTAL_DEFAULT_PASSWORD', 'kostpro123'),

];
