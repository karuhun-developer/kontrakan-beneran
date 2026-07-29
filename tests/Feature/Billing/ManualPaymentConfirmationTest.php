<?php

namespace Tests\Feature\Billing;

use App\Enums\CashflowType;
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
use Tests\TestCase;

class ManualPaymentConfirmationTest extends TestCase
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
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create();
    }

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
    }

    private function pendingPayment(int $total = 1_000_000): Payment
    {
        $invoice = Invoice::factory()->forBranch($this->branch)->create([
            'penyewa_id' => $this->penyewa->id,
            'subtotal' => $total,
            'total' => $total,
            'status' => InvoiceStatus::Unpaid,
        ]);

        return Payment::factory()
            ->for($invoice)
            ->status(PaymentStatus::Pending)
            ->method(PaymentMethod::Transfer)
            ->create(['amount' => $total, 'paid_at' => null, 'recorded_by' => null]);
    }

    public function test_confirming_settles_the_payment_and_records_cashflow()
    {
        $staff = $this->actingAsRole(UserRole::Staff);
        $payment = $this->pendingPayment(1_000_000);

        $this->post(route('invoices.payments.confirm', $payment))->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame($staff->id, $payment->recorded_by);
        $this->assertNotNull($payment->paid_at);

        $this->assertSame(InvoiceStatus::Paid, $payment->invoice->fresh()->status);

        $this->assertDatabaseHas('cashflows', [
            'source_type' => (new Payment)->getMorphClass(),
            'source_id' => $payment->id,
            'type' => CashflowType::Income->value,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_rejecting_marks_the_payment_failed_without_cashflow()
    {
        $this->actingAsRole(UserRole::Staff);
        $payment = $this->pendingPayment(1_000_000);

        $this->post(route('invoices.payments.reject', $payment))->assertRedirect();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(InvoiceStatus::Unpaid, $payment->invoice->fresh()->status);
        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_a_settled_payment_cannot_be_confirmed_again()
    {
        $this->actingAsRole(UserRole::Staff);
        $payment = $this->pendingPayment(1_000_000);
        $payment->update(['status' => PaymentStatus::Completed]);

        $this->post(route('invoices.payments.confirm', $payment))
            ->assertSessionHasErrors('payment');
    }

    public function test_cannot_confirm_a_payment_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherPenyewa = Penyewa::factory()->forBranch($otherBranch)->create();
        $invoice = Invoice::factory()->forBranch($otherBranch)->create([
            'penyewa_id' => $otherPenyewa->id,
        ]);
        $payment = Payment::factory()
            ->for($invoice)
            ->status(PaymentStatus::Pending)
            ->method(PaymentMethod::Transfer)
            ->create();

        $this->post(route('invoices.payments.confirm', $payment))->assertNotFound();
    }
}
