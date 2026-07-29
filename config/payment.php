<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | The online payment gateway resolved for checkout and webhooks. The domain
    | never references a vendor directly — swap this to change providers.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Per-driver credentials. Each key maps to a createXxxDriver() method on the
    | PaymentGatewayManager. The "null" driver needs no configuration.
    |
    */

    'gateways' => [

        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY', ''),
            'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
            'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        ],

    ],

];
