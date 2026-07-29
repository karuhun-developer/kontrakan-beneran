<?php

namespace App\Actions\Notification;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceDueReminder;
use App\Notifications\PenyewaInvoiceDueReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendDueInvoiceReminders
{
    /**
     * Notify branch staff about outstanding invoices approaching their due
     * date. Each configured milestone (e.g. 7/3/1 days before) fires once per
     * invoice — a tracking row keeps repeated daily runs idempotent.
     *
     * @return int Number of invoices a reminder was sent for.
     */
    public function handle(?string $asOf = null): int
    {
        $today = $asOf !== null
            ? CarbonImmutable::parse($asOf)->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        /** @var list<int> $milestones */
        $milestones = config('billing.reminder_days', []);

        $sent = 0;

        foreach ($milestones as $daysBefore) {
            $dueDate = $today->addDays($daysBefore)->toDateString();

            Invoice::query()
                ->whereIn('status', InvoiceStatus::outstanding())
                ->whereDate('due_date', $dueDate)
                ->whereDoesntHave('reminders', fn ($query) => $query->where('days_before', $daysBefore))
                ->with('penyewa.user', 'branch')
                ->cursor()
                ->each(function (Invoice $invoice) use ($daysBefore, &$sent): void {
                    $recipients = $this->recipientsFor($invoice);

                    if ($recipients->isNotEmpty()) {
                        Notification::send($recipients, new InvoiceDueReminder($invoice, $daysBefore));
                    }

                    $portalUser = $invoice->penyewa?->user;

                    if ($portalUser !== null) {
                        $portalUser->notify(new PenyewaInvoiceDueReminder($invoice, $daysBefore));
                    }

                    $invoice->reminders()->create([
                        'days_before' => $daysBefore,
                        'sent_at' => now(),
                    ]);

                    $sent++;
                });
        }

        return $sent;
    }

    /**
     * Staff who should hear about an invoice: users assigned to its branch plus
     * the tenant's owners.
     *
     * @return Collection<int, User>
     */
    private function recipientsFor(Invoice $invoice): Collection
    {
        $branchUsers = $invoice->branch->users()->get();

        $owners = User::query()
            ->where('tenant_id', $invoice->tenant_id)
            ->role(UserRole::Owner->value)
            ->get();

        return $branchUsers->concat($owners)->unique('id')->values();
    }
}
