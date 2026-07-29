<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Penyewa-facing reminder for their own invoice — mirrors InvoiceDueReminder
 * but with copy and links aimed at the portal (not staff).
 */
class PenyewaInvoiceDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $daysBefore  Days remaining until the invoice's due date.
     */
    public function __construct(
        public Invoice $invoice,
        public int $daysBefore,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $due = $this->invoice->due_date->translatedFormat('d M Y');
        $amount = 'Rp'.number_format($this->invoice->outstandingAmount(), 0, ',', '.');

        return (new MailMessage)
            ->subject("Tagihan {$this->invoice->number} jatuh tempo {$this->duePhrase()}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Tagihan {$this->invoice->number} kamu jatuh tempo {$this->duePhrase()} ({$due}).")
            ->line("Sisa tagihan: {$amount}.")
            ->action('Lihat & Bayar Tagihan', route('portal.invoices.show', $this->invoice))
            ->line('Terima kasih telah melakukan pembayaran tepat waktu.');
    }

    /**
     * Get the array (database) representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'due_date' => $this->invoice->due_date->toDateString(),
            'days_before' => $this->daysBefore,
            'outstanding' => $this->invoice->outstandingAmount(),
            'message' => "Tagihan {$this->invoice->number} kamu jatuh tempo {$this->duePhrase()}.",
        ];
    }

    /**
     * Human phrase for the remaining days ("hari ini", "besok", "3 hari lagi").
     */
    private function duePhrase(): string
    {
        return match ($this->daysBefore) {
            0 => 'hari ini',
            1 => 'besok',
            default => "{$this->daysBefore} hari lagi",
        };
    }
}
