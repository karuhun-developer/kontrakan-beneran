<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Billing (Fase 3). Generate the month's rent invoices, then flag any that
 * slipped past their due date and charge the late fee.
 */
Schedule::command('billing:generate-invoices')->dailyAt('01:00');
Schedule::command('billing:apply-late-fees')->dailyAt('01:30');
