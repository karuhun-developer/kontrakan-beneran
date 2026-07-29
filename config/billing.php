<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recurring invoice generation
    |--------------------------------------------------------------------------
    |
    | How many days before a rental month begins the scheduler should already
    | generate that month's invoice, so tenants receive it ahead of the due
    | date. 0 generates on the day the period starts.
    |
    */

    'invoice_lead_days' => (int) env('BILLING_INVOICE_LEAD_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Late fee (denda telat)
    |--------------------------------------------------------------------------
    |
    | Applied once when an outstanding invoice passes its due date. "type" is
    | either "percent" (of the invoice subtotal / rent) or "flat" (a fixed
    | rupiah amount). "value" is the percentage or the flat amount accordingly.
    |
    */

    'late_fee' => [
        'enabled' => (bool) env('BILLING_LATE_FEE_ENABLED', true),
        'type' => env('BILLING_LATE_FEE_TYPE', 'percent'),
        'value' => (int) env('BILLING_LATE_FEE_VALUE', 5),
    ],

];
