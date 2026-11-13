<?php

namespace Tests\Unit;

use App\Models\CashFlow;
use App\Models\Concerns\OptionalSchemaColumnCache;
use App\Models\FinSalePayment;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class PosSchemaAndCheckoutGuardUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OptionalSchemaColumnCache::flush();
    }

    protected function tearDown(): void
    {
        OptionalSchemaColumnCache::flush();
        Mockery::close();
        parent::tearDown();
    }

    private function invokeStripper(Model $model): void
    {
        $method = new ReflectionMethod(get_class($model), 'stripMissingOptionalColumns');
        $method->setAccessible(true);
        $method->invoke(null, $model);
    }

    public function test_strips_null_or_default_new_columns_when_migrations_are_pending(): void
    {
        Schema::shouldReceive('hasColumn')
            ->with('pos_transactions', 'external_customer_id')
            ->andReturn(false);

        Schema::shouldReceive('hasColumn')
            ->with('fin_sale_payments', 'external_customer_id')
            ->andReturn(false);
        Schema::shouldReceive('hasColumn')
            ->with('fin_sale_payments', 'is_external_company')
            ->andReturn(false);

        Schema::shouldReceive('hasColumn')
            ->with('cash_flows', 'external_customer_id')
            ->andReturn(false);
        Schema::shouldReceive('hasColumn')
            ->with('cash_flows', 'expense_type_id')
            ->andReturn(false);
        Schema::shouldReceive('hasColumn')
            ->with('cash_flows', 'expense_type_name')
            ->andReturn(false);

        $posTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-ABC12',
            'external_customer_id' => null,
            'customer_name' => 'Cliente Mostrador',
        ]);
        $this->invokeStripper($posTx);
        $this->assertArrayNotHasKey('external_customer_id', $posTx->getAttributes());
        $this->assertArrayHasKey('transaction_code', $posTx->getAttributes());

        $finPayment = new FinSalePayment([
            'fin_sale_id' => 10,
            'external_customer_id' => null,
            'is_external_company' => false,
            'customer_name' => 'Cliente Interno',
        ]);
        $this->invokeStripper($finPayment);
        $this->assertArrayNotHasKey('external_customer_id', $finPayment->getAttributes());
        $this->assertArrayNotHasKey('is_external_company', $finPayment->getAttributes());

        $cashFlow = new CashFlow([
            'type' => 'income',
            'amount' => 250.00,
            'external_customer_id' => null,
            'expense_type_id' => null,
            'expense_type_name' => null,
        ]);
        $this->invokeStripper($cashFlow);
        $this->assertArrayNotHasKey('external_customer_id', $cashFlow->getAttributes());
        $this->assertArrayNotHasKey('expense_type_id', $cashFlow->getAttributes());
        $this->assertArrayNotHasKey('expense_type_name', $cashFlow->getAttributes());
    }

    public function test_throws_explicit_message_when_populated_external_column_lacks_migration(): void
    {
        Schema::shouldReceive('hasColumn')
            ->with('pos_transactions', 'external_customer_id')
            ->andReturn(false);

        $posTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-EXT99',
            'external_customer_id' => 15,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('php artisan migrate');
        $this->invokeStripper($posTx);
    }

    public function test_throws_explicit_message_when_external_company_flag_true_lacks_migration(): void
    {
        Schema::shouldReceive('hasColumn')
            ->with('fin_sale_payments', 'is_external_company')
            ->andReturn(false);

        $payment = new FinSalePayment([
            'customer_name' => 'Cliente Externo',
            'is_external_company' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('php artisan migrate');
        $this->invokeStripper($payment);
    }

    public function test_payment_history_preserves_unconditional_stripping_of_optional_legacy_columns(): void
    {
        Schema::shouldReceive('hasColumn')
            ->with('payment_histories', 'note')
            ->andReturn(false);

        $ph = new PaymentHistory([
            'order_id' => 5,
            'amount' => 100.00,
            'note' => 'Nota compatibilidad',
        ]);
        $this->invokeStripper($ph);
        $this->assertArrayNotHasKey('note', $ph->getAttributes());
        $this->assertSame(100.00, (float) $ph->getAttributes()['amount']);
    }

    public function test_keeps_all_columns_intact_when_schema_columns_exist(): void
    {
        Schema::shouldReceive('hasColumn')
            ->with('pos_transactions', 'external_customer_id')
            ->andReturn(true);

        $posTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-MIG01',
            'external_customer_id' => 21,
        ]);
        $this->invokeStripper($posTx);
        $this->assertSame(21, $posTx->getAttributes()['external_customer_id']);
    }

    public function test_central_pos_checkout_script_uses_relative_routes_json_headers_and_timeout_guard(): void
    {
        $blade = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $this->assertNotFalse($blade);

        $this->assertStringContainsString("route('central-pos.checkout', [], false)", $blade);
        $this->assertStringContainsString("'Accept': 'application/json'", $blade);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $blade);
        $this->assertStringContainsString('AbortController', $blade);
        $this->assertStringContainsString('isCheckoutInFlight', $blade);
        $this->assertStringContainsString('normalizePosUrl', $blade);
    }
}
