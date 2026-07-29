<?php

namespace Tests\Feature\Billing;

use App\Enums\BookingStatus;
use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    private Room $room;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $type = RoomType::factory()->forBranch($this->branch)->create();
        $this->room = Room::factory()->forBranch($this->branch)->create(['room_type_id' => $type->id]);
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

    private function invoice(int $total = 1_000_000, InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
    {
        return Invoice::factory()->forBranch($this->branch)->create([
            'penyewa_id' => $this->penyewa->id,
            'subtotal' => $total,
            'total' => $total,
            'status' => $status,
        ]);
    }

    public function test_index_lists_only_current_branch_invoices()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->invoice();

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Invoice::factory()->forBranch($otherBranch)->create();

        $this->get(route('invoices.index'))
            ->assertInertia(fn ($page) => $page
                ->component('billing/invoices/index')
                ->has('invoices.data', 1)
                ->has('stats')
            );
    }

    public function test_index_filters_by_status()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->invoice(status: InvoiceStatus::Unpaid);
        $this->invoice(status: InvoiceStatus::Paid);

        $this->get(route('invoices.index', ['status' => 'paid']))
            ->assertInertia(fn ($page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.status', 'paid')
            );
    }

    public function test_staff_can_record_a_payment_and_it_hits_cashflow()
    {
        $this->actingAsRole(UserRole::Staff);

        $invoice = $this->invoice(1_000_000);

        $this->post(route('invoices.pay', $invoice), [
            'amount' => 1_000_000,
            'method' => PaymentMethod::Cash->value,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('cashflows', [
            'source_type' => (new Payment)->getMorphClass(),
            'type' => CashflowType::Income->value,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_staff_can_attach_a_payment_proof()
    {
        Storage::fake('public');
        $this->actingAsRole(UserRole::Staff);

        $invoice = $this->invoice(1_000_000);

        $this->post(route('invoices.pay', $invoice), [
            'amount' => 1_000_000,
            'method' => PaymentMethod::Transfer->value,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertTrue($payment->hasMedia(Payment::MEDIA_PROOF));
    }

    public function test_overpayment_is_rejected_with_errors()
    {
        $this->actingAsRole(UserRole::Staff);

        $invoice = $this->invoice(1_000_000);

        $this->post(route('invoices.pay', $invoice), [
            'amount' => 1_500_000,
            'method' => PaymentMethod::Cash->value,
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_unpaid_invoice_can_be_voided()
    {
        $this->actingAsRole(UserRole::Staff);

        $invoice = $this->invoice(1_000_000);

        $this->post(route('invoices.void', $invoice))->assertRedirect();

        $this->assertSame(InvoiceStatus::Void, $invoice->fresh()->status);
    }

    public function test_staff_can_generate_an_invoice_from_a_booking()
    {
        $this->actingAsRole(UserRole::Staff);

        $booking = Booking::factory()
            ->forRoom($this->room, '2026-07-01', '2026-10-01')
            ->status(BookingStatus::CheckedIn)
            ->create([
                'penyewa_id' => $this->penyewa->id,
                'monthly_price' => 1_200_000,
            ]);

        $this->post(route('invoices.from-booking', $booking))->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'booking_id' => $booking->id,
            'branch_id' => $this->branch->id,
            'total' => 1_200_000,
        ]);
    }

    public function test_paying_an_invoice_from_another_branch_is_blocked()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $invoice = Invoice::factory()->forBranch($otherBranch)->create();

        $this->post(route('invoices.pay', $invoice), [
            'amount' => 100_000,
            'method' => PaymentMethod::Cash->value,
        ])->assertNotFound();
    }
}
