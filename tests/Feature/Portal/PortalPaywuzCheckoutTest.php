<?php

namespace Tests\Feature\Portal;

use App\Contracts\PaymentGateway;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalPaywuzCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        $this->penyewa = Penyewa::factory()->forBranch($branch)->create(['user_id' => $user->id]);

        $this->actingAs($user);
        Cache::flush();

        config()->set('payment.default', 'paywuz');
        config()->set('payment.gateways.paywuz', [
            'base_url' => 'https://api.paywuz.test/v1',
            'key' => 'pk_test_key',
            'webhook_secret' => 'whsec_test',
        ]);

        app()->forgetInstance(PaymentGateway::class);
        app()->forgetInstance(PaymentGatewayManager::class);
    }

    public function test_show_exposes_available_payment_methods()
    {
        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'qris_id', 'name' => 'QRIS', 'type' => 'qris'],
            ]]),
        ]);

        $invoice = Invoice::factory()->create([
            'penyewa_id' => $this->penyewa->id,
            'total' => 1_000_000,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->get(route('portal.invoices.show', $invoice))
            ->assertInertia(fn ($page) => $page
                ->where('_inertiaui_modal.props.payment_methods.0.code', 'qris_id'));
    }

    public function test_checkout_forwards_the_selected_method()
    {
        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'qris_id', 'name' => 'QRIS', 'type' => 'qris'],
            ]]),
            '*/transactions' => Http::response(['data' => [
                'id' => 'PW-REF-1',
                'paymentUrl' => 'https://pay.paywuz.test/checkout/PW-REF-1',
            ]]),
        ]);

        $invoice = Invoice::factory()->create([
            'penyewa_id' => $this->penyewa->id,
            'total' => 1_000_000,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->post(route('portal.invoices.checkout', $invoice), ['method' => 'qris_id'])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'method' => PaymentMethod::Qris->value,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_checkout_rejects_an_unknown_method()
    {
        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'qris_id', 'name' => 'QRIS', 'type' => 'qris'],
            ]]),
        ]);

        $invoice = Invoice::factory()->create([
            'penyewa_id' => $this->penyewa->id,
            'total' => 1_000_000,
            'status' => InvoiceStatus::Unpaid,
        ]);

        $this->post(route('portal.invoices.checkout', $invoice), ['method' => 'bogus'])
            ->assertSessionHasErrors('method');

        $this->assertDatabaseCount('payments', 0);
    }
}
