<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\CashController;
use App\Http\Controllers\Dashboard\UnifiedPosController;
use App\Models\CashFlow;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class FinancieraReconciliationUnitTest extends TestCase
{
    public function test_fin_sale_payment_resolves_spanish_and_english_mixed_breakdowns(): void
    {
        $spanishMixed = new FinSalePayment([
            'amount' => 230.00,
            'commission_amount' => 20.00,
            'total_amount' => 250.00,
            'payment_method' => 'mixto',
            'payment_breakdown' => [
                'efectivo' => 100.00,
                'tarjeta' => 90.00,
                'transferencia' => 60.00,
            ],
        ]);

        $this->assertEquals(100.00, $spanishMixed->cash_amount);
        $this->assertEquals(90.00, $spanishMixed->card_amount);
        $this->assertEquals(60.00, $spanishMixed->transfer_amount);

        $zeroCashMixed = new FinSalePayment([
            'total_amount' => 250.00,
            'payment_method' => 'mixto',
            'payment_breakdown' => [
                'efectivo' => 0.00,
                'tarjeta' => 150.00,
                'transferencia' => 100.00,
            ],
        ]);

        $this->assertEquals(0.00, $zeroCashMixed->cash_amount);
        $this->assertEquals(150.00, $zeroCashMixed->card_amount);
        $this->assertEquals(100.00, $zeroCashMixed->transfer_amount);

        $englishLegacy = new FinSalePayment([
            'total_amount' => 175.00,
            'payment_method' => 'mixto',
            'payment_breakdown' => [
                'cash' => 100.00,
                'card' => 50.00,
                'transfer' => 25.00,
            ],
        ]);

        $this->assertEquals(100.00, $englishLegacy->cash_amount);
        $this->assertEquals(50.00, $englishLegacy->card_amount);
        $this->assertEquals(25.00, $englishLegacy->transfer_amount);

        $singleMethodFallback = new FinSalePayment([
            'total_amount' => 320.00,
            'payment_method' => 'transferencia',
            'payment_breakdown' => null,
        ]);

        $this->assertEquals(0.00, $singleMethodFallback->cash_amount);
        $this->assertEquals(0.00, $singleMethodFallback->card_amount);
        $this->assertEquals(320.00, $singleMethodFallback->transfer_amount);
    }

    public function test_fin_sale_payment_falls_back_to_loaded_pos_transaction_breakdown(): void
    {
        $tx = new PosTransaction([
            'cash_amount' => 120.00,
            'card_amount' => 80.00,
            'transfer_amount' => 50.00,
        ]);

        $payment = new FinSalePayment([
            'total_amount' => 250.00,
            'payment_method' => 'mixto',
            'payment_breakdown' => null,
        ]);
        $payment->setRelation('posTransaction', $tx);

        $this->assertEquals(120.00, $payment->cash_amount);
        $this->assertEquals(80.00, $payment->card_amount);
        $this->assertEquals(50.00, $payment->transfer_amount);
    }

    public function test_pos_transaction_concepts_summary_reads_title_with_label_fallback(): void
    {
        $tx = new PosTransaction([
            'items_payload' => [
                ['section' => 'financiera_abono', 'title' => 'Abono PayJoy #3 - TAG: PJ-102'],
                ['section' => 'producto_dinamico', 'label' => 'Funda Uso Rudo'],
                ['section' => 'producto_inventario', 'description' => 'Mica 9H'],
            ],
        ]);

        $this->assertEquals(
            'Abono PayJoy #3 - TAG: PJ-102, Funda Uso Rudo, Mica 9H',
            $tx->concepts_summary
        );
    }

    public function test_fin_sale_credit_vs_contado_helpers(): void
    {
        $contadoSale = new FinSale(['sale_type' => 'contado']);
        $creditoSale = new FinSale(['sale_type' => 'credito']);
        $legacyNullSale = new FinSale([]);

        $this->assertTrue($contadoSale->isContado());
        $this->assertFalse($contadoSale->isCredito());

        $this->assertFalse($creditoSale->isContado());
        $this->assertTrue($creditoSale->isCredito());

        $this->assertFalse($legacyNullSale->isContado());
        $this->assertTrue($legacyNullSale->isCredito());
    }

    public function test_allocate_payment_breakdown_splits_multi_item_mixed_payments_exactly(): void
    {
        $controller = (new \ReflectionClass(UnifiedPosController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(UnifiedPosController::class, 'allocatePaymentBreakdown');
        $method->setAccessible(true);

        // Single-item exact breakdown
        $single = $method->invoke($controller, 250.00, 250.00, 100.00, 100.00, 50.00);
        $this->assertEquals(['efectivo' => 100.00, 'tarjeta' => 100.00, 'transferencia' => 50.00], $single);

        // Multi-item cart: $250 financiera + $150 accessory = $400 total paid ($200 cash + $200 card)
        $itemFin = $method->invoke($controller, 250.00, 400.00, 200.00, 200.00, 0.00);
        $this->assertEquals(250.00, round($itemFin['efectivo'] + $itemFin['tarjeta'] + $itemFin['transferencia'], 2));
        $this->assertEquals(125.00, $itemFin['efectivo']);
        $this->assertEquals(125.00, $itemFin['tarjeta']);
        $this->assertEquals(0.00, $itemFin['transferencia']);
    }

    public function test_cash_controller_chronological_operations_extracts_title_and_legacy_label(): void
    {
        $controller = (new \ReflectionClass(CashController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(CashController::class, 'buildChronologicalOperations');
        $method->setAccessible(true);

        $tx = new PosTransaction([
            'items_payload' => [
                ['section' => 'financiera_abono', 'title' => 'Abono Credimás #2', 'amount' => 300, 'commission' => 0, 'quantity' => 1],
                ['section' => 'producto_dinamico', 'label' => 'Cable USB-C', 'amount' => 100, 'commission' => 0, 'quantity' => 1],
            ],
        ]);

        $flow = new CashFlow([
            'type' => 'income',
            'amount' => 400.00,
            'payment_method' => 'efectivo',
            'module' => 'Cobro Centralizado',
            'customer_name' => 'María López',
            'reference' => 'MOV-20261113-TEST',
        ]);
        $flow->created_at = Carbon::parse('2026-11-13 14:30:00');
        $flow->setRelation('posTransaction', $tx);
        $flow->setRelation('user', new User(['name' => 'Jimena']));

        $ops = $method->invoke($controller, collect([$flow]));

        $this->assertCount(1, $ops);
        $this->assertEquals('Abono Credimás #2', $ops[0]['concepts'][0]['title']);
        $this->assertEquals('Cable USB-C', $ops[0]['concepts'][1]['title']);
    }

    public function test_sanitize_gift_subtitle_strips_provider_metadata(): void
    {
        $this->assertEquals(
            'Código: GFT-101',
            PosTransaction::sanitizeGiftSubtitle('Regalo de proveedor: PayJoy | Código: GFT-101')
        );
        $this->assertNull(
            PosTransaction::sanitizeGiftSubtitle('Regalo de proveedor: Samsung')
        );
        $this->assertEquals(
            'Código: GFT-202',
            PosTransaction::sanitizeGiftSubtitle('Código: GFT-202')
        );
    }
}

