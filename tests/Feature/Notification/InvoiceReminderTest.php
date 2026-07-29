<?php

namespace Tests\Feature\Notification;

use App\Actions\Notification\SendDueInvoiceReminders;
use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvoiceDueReminder;
use App\Notifications\PenyewaInvoiceDueReminder;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    private const AS_OF = '2026-08-01';

    private Tenant $tenant;

    private Branch $branch;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->staff->branches()->sync([$this->branch->id]);
    }

    private function invoiceDueInDays(int $days, ?InvoiceStatus $status = null): Invoice
    {
        $dueDate = CarbonImmutable::parse(self::AS_OF)->addDays($days)->toDateString();

        return Invoice::factory()->forBranch($this->branch)->create([
            'due_date' => $dueDate,
            'status' => $status ?? InvoiceStatus::Unpaid,
        ]);
    }

    public function test_sends_reminders_at_each_configured_milestone()
    {
        Notification::fake();

        $this->invoiceDueInDays(7);
        $this->invoiceDueInDays(3);
        $this->invoiceDueInDays(1);

        $sent = app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        $this->assertSame(3, $sent);
        Notification::assertSentToTimes($this->staff, InvoiceDueReminder::class, 3);
        $this->assertDatabaseCount('invoice_reminders', 3);
    }

    public function test_does_not_resend_a_milestone_already_recorded()
    {
        Notification::fake();

        $this->invoiceDueInDays(3);

        app(SendDueInvoiceReminders::class)->handle(self::AS_OF);
        $secondRun = app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        $this->assertSame(0, $secondRun);
        Notification::assertSentToTimes($this->staff, InvoiceDueReminder::class, 1);
        $this->assertDatabaseCount('invoice_reminders', 1);
    }

    public function test_ignores_paid_void_and_off_milestone_invoices()
    {
        Notification::fake();

        $this->invoiceDueInDays(3, InvoiceStatus::Paid);
        $this->invoiceDueInDays(7, InvoiceStatus::Void);
        $this->invoiceDueInDays(5);

        $sent = app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('invoice_reminders', 0);
    }

    public function test_persists_a_database_notification_for_recipients()
    {
        $invoice = $this->invoiceDueInDays(1);

        app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staff->id,
            'notifiable_type' => $this->staff->getMorphClass(),
            'type' => InvoiceDueReminder::class,
        ]);

        $this->assertCount(1, $this->staff->fresh()->unreadNotifications);
        $this->assertSame(
            $invoice->id,
            $this->staff->fresh()->notifications->first()->data['invoice_id'],
        );
    }

    public function test_notifies_the_penyewas_portal_user_with_a_penyewa_facing_reminder()
    {
        Notification::fake();

        $portalUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $penyewa = Penyewa::factory()->forBranch($this->branch)->create([
            'user_id' => $portalUser->id,
        ]);

        $this->invoiceDueInDays(3)->update(['penyewa_id' => $penyewa->id]);

        app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        Notification::assertSentToTimes($portalUser, PenyewaInvoiceDueReminder::class, 1);
        Notification::assertNotSentTo($portalUser, InvoiceDueReminder::class);
    }

    public function test_does_not_notify_a_penyewa_without_a_portal_user()
    {
        Notification::fake();

        $this->invoiceDueInDays(3);

        app(SendDueInvoiceReminders::class)->handle(self::AS_OF);

        Notification::assertSentTimes(PenyewaInvoiceDueReminder::class, 0);
    }

    public function test_notification_exposes_database_and_mail_channels()
    {
        $invoice = $this->invoiceDueInDays(3);
        $notification = new InvoiceDueReminder($invoice, 3);

        $this->assertSame(['database', 'mail'], $notification->via($this->staff));

        $data = $notification->toArray($this->staff);
        $this->assertSame($invoice->id, $data['invoice_id']);
        $this->assertSame(3, $data['days_before']);
    }
}
