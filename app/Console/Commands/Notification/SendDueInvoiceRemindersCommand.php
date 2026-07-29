<?php

namespace App\Console\Commands\Notification;

use App\Actions\Notification\SendDueInvoiceReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:send-due-reminders {--as-of= : Reference date (Y-m-d), defaults to today}')]
#[Description('Notify staff about outstanding invoices approaching their due date')]
class SendDueInvoiceRemindersCommand extends Command
{
    public function handle(SendDueInvoiceReminders $action): int
    {
        $sent = $action->handle($this->option('as-of'));

        $this->info("Sent reminders for {$sent} invoice(s).");

        return self::SUCCESS;
    }
}
