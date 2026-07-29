<?php

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Kost Demo',
        ]);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
    }

    private function invoice(int $total = 1_000_000, InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
    {
        return Invoice::factory()->create([
            'penyewa_id' => $this->penyewa->id,
            'subtotal' => $total,
            'total' => $total,
            'status' => $status,
        ]);
    }

    public function test_detail_exposes_transfer_instructions_and_can_submit_manual()
    {
        $invoice = $this->invoice();

        $this->get(route('portal.invoices.show', $invoice))
            ->assertInertia(fn ($page) => $page
                ->where('_inertiaui_modal.props.invoice.can_submit_manual', true)
                ->where('_inertiaui_modal.props.invoice.has_pending_payment', false)
                ->where('_inertiaui_modal.props.invoice.transfer.bank_name', 'BCA')
                ->where('_inertiaui_modal.props.invoice.transfer.account_number', '1234567890')
            );
    }

    public function test_submitting_proof_creates_a_pending_payment_with_media()
    {
        Storage::fake('public');

        $invoice = $this->invoice(1_000_000);

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 800_000,
            'notes' => 'Transfer via BCA mobile',
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(PaymentMethod::Transfer, $payment->method);
        $this->assertSame(800_000, $payment->amount);
        $this->assertNull($payment->recorded_by);
        $this->assertNull($payment->paid_at);
        $this->assertTrue($payment->hasMedia(Payment::MEDIA_PROOF));

        // Pending payment must not settle the invoice.
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->fresh()->status);
    }

    public function test_submitted_proof_is_exposed_for_preview_in_the_detail_payload()
    {
        Storage::fake('public');

        $invoice = $this->invoice(1_000_000);

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->get(route('portal.invoices.show', $invoice))
            ->assertInertia(fn ($page) => $page
                ->where('_inertiaui_modal.props.invoice.payments.0.proof_url', fn ($url) => is_string($url) && $url !== '')
                ->where('_inertiaui_modal.props.invoice.payments.0.proof_thumb_url', fn ($url) => is_string($url) && $url !== '')
            );
    }

    public function test_proof_is_required()
    {
        $invoice = $this->invoice();

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 500_000,
        ])->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_amount_over_outstanding_is_rejected()
    {
        Storage::fake('public');

        $invoice = $this->invoice(1_000_000);

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 1_500_000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cannot_submit_when_a_payment_is_already_pending()
    {
        Storage::fake('public');

        $invoice = $this->invoice(1_000_000);
        Payment::factory()
            ->for($invoice)
            ->status(PaymentStatus::Pending)
            ->method(PaymentMethod::Transfer)
            ->create(['amount' => 500_000]);

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasErrors('invoice');

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_cannot_submit_for_another_penyewas_invoice()
    {
        Storage::fake('public');

        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        $invoice = Invoice::factory()->create(['penyewa_id' => $otherPenyewa->id]);

        $this->post(route('portal.invoices.manual-payment', $invoice), [
            'amount' => 500_000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertNotFound();
    }
}
