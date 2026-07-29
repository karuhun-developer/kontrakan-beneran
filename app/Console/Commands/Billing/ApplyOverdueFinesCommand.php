<?php

namespace App\Console\Commands\Billing;

use App\Actions\Billing\ApplyOverdueFines;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('billing:apply-late-fees {--as-of= : Reference date (Y-m-d), defaults to today}')]
#[Description('Flag overdue invoices and apply late fees')]
class ApplyOverdueFinesCommand extends Command
{
    public function handle(ApplyOverdueFines $action): int
    {
        $overdue = $action->handle($this->option('as-of'));

        $this->info("Marked {$overdue} invoice(s) overdue.");

        return self::SUCCESS;
    }
}
