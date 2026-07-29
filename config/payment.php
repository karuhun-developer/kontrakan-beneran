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
    | Webhook Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum gateway callbacks accepted per minute, per source IP. The webhook
    | is public and unauthenticated (verified by signature), so this caps abuse
    | while staying well above a gateway's legitimate retry volume.
    |
    */

    'webhook_rate_limit' => (int) env('PAYMENT_WEBHOOK_RATE_LIMIT', 120),

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
