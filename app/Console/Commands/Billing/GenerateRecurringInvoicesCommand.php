<?php

namespace App\Console\Commands\Billing;

use App\Actions\Billing\GenerateRecurringInvoices;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('billing:generate-invoices {--as-of= : Reference date (Y-m-d), defaults to today}')]
#[Description('Generate due monthly rent invoices for active bookings')]
class GenerateRecurringInvoicesCommand extends Command
{
    public function handle(GenerateRecurringInvoices $action): int
    {
        $created = $action->handle($this->option('as-of'));

        $this->info("Generated {$created} invoice(s).");

        return self::SUCCESS;
    }
}
