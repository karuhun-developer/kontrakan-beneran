<?php

namespace Tests\Feature\Deposit;

use App\Actions\Deposit\OpenDeposit;
use App\Actions\Deposit\RecordDepositTransaction;
use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Models\Branch;
use App\Models\Deposit;
use App\Models\Penyewa;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function penyewa(): Penyewa
    {
        return Penyewa::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_opening_a_deposit_holds_funds_and_writes_the_ledger()
    {
        $deposit = app(OpenDeposit::class)->handle($this->penyewa(), 1_000_000);

        $this->assertSame(1_000_000, $deposit->amount);
        $this->assertSame(1_000_000, $deposit->balance);
        $this->assertSame(DepositStatus::Held, $deposit->status);

        $this->assertDatabaseHas('deposit_transactions', [
            'deposit_id' => $deposit->id,
            'type' => DepositTransactionType::Hold->value,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_opening_a_deposit_rejects_a_non_positive_amount()
    {
        $this->expectException(ValidationException::class);

        app(OpenDeposit::class)->handle($this->penyewa(), 0);
    }

    public function test_a_deduction_lowers_the_balance_and_marks_it_partial()
    {
        $deposit = app(OpenDeposit::class)->handle($this->penyewa(), 1_000_000);

        app(RecordDepositTransaction::class)->handle(
            $deposit,
            DepositTransactionType::Deduction,
            300_000,
            'Potong laundry',
        );

        $deposit->refresh();

        $this->assertSame(700_000, $deposit->balance);
        $this->assertSame(1_000_000, $deposit->amount);
        $this->assertSame(DepositStatus::Partial, $deposit->status);
    }

    public function test_refunding_the_full_balance_settles_the_deposit()
    {
        $deposit = app(OpenDeposit::class)->handle($this->penyewa(), 1_000_000);

        app(RecordDepositTransaction::class)->handle(
            $deposit,
            DepositTransactionType::Refund,
            1_000_000,
            'Pengembalian saat checkout',
        );

        $deposit->refresh();

        $this->assertSame(0, $deposit->balance);
        $this->assertSame(DepositStatus::Refunded, $deposit->status);
        $this->assertNotNull($deposit->settled_at);
    }

    public function test_a_transaction_cannot_exceed_the_remaining_balance()
    {
        $deposit = app(OpenDeposit::class)->handle($this->penyewa(), 500_000);

        $this->expectException(ValidationException::class);

        app(RecordDepositTransaction::class)->handle(
            $deposit,
            DepositTransactionType::Deduction,
            600_000,
        );
    }

    public function test_hold_type_is_not_allowed_through_the_transaction_action()
    {
        $deposit = app(OpenDeposit::class)->handle($this->penyewa(), 500_000);

        $this->expectException(ValidationException::class);

        app(RecordDepositTransaction::class)->handle(
            $deposit,
            DepositTransactionType::Hold,
            100_000,
        );
    }

    public function test_active_scope_excludes_refunded_deposits()
    {
        $held = Deposit::factory()->forBranch($this->branch)->create();
        Deposit::factory()->forBranch($this->branch)->create([
            'status' => DepositStatus::Refunded,
            'balance' => 0,
        ]);

        $active = Deposit::query()->active()->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->contains($held));
    }
}
