<?php

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInvoiceTest extends TestCase
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

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
    }

    private function invoice(?InvoiceStatus $status = null): Invoice
    {
        return Invoice::factory()->create([
            'penyewa_id' => $this->penyewa->id,
            'status' => $status ?? InvoiceStatus::Unpaid,
        ]);
    }

    public function test_index_lists_only_the_penyewas_invoices()
    {
        $this->invoice();

        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        Invoice::factory()->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.invoices.index'))
            ->assertInertia(fn ($page) => $page
                ->component('portal/invoices/index')
                ->has('invoices.data', 1)
            );
    }

    public function test_index_filters_by_status()
    {
        $this->invoice(InvoiceStatus::Unpaid);
        $this->invoice(InvoiceStatus::Paid);

        $this->get(route('portal.invoices.index', ['status' => 'paid']))
            ->assertInertia(fn ($page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.status', 'paid')
            );
    }

    public function test_dashboard_summarises_outstanding_and_deposit()
    {
        $this->invoice(InvoiceStatus::Unpaid);

        $this->get(route('portal.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('summary.outstanding_count', 1)
                ->where('summary.outstanding_value', fn ($value) => $value > 0)
            );
    }

    public function test_can_view_own_invoice_detail()
    {
        $invoice = $this->invoice();

        $this->get(route('portal.invoices.show', $invoice))
            ->assertOk();
    }

    public function test_cannot_view_another_penyewas_invoice()
    {
        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        $invoice = Invoice::factory()->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.invoices.show', $invoice))
            ->assertNotFound();
    }

    public function test_checkout_creates_a_pending_payment_for_the_outstanding_amount()
    {
        $invoice = $this->invoice(InvoiceStatus::Unpaid);

        $this->post(route('portal.invoices.checkout', $invoice))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
        ]);
    }

    public function test_staff_cannot_reach_the_portal()
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->actingAs($staff)
            ->get(route('portal.invoices.index'))
            ->assertForbidden();
    }
}
