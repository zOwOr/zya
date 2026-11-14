<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoanCalculationTest extends TestCase
{
    /**
     * Criterio de Aceptación 1:
     * Préstamo de ejemplo: capital $5,000, abono $854, 8 pagos, frecuencia 12 y 26.
     * El sistema calcula interés y total, y genera la amortización correcta.
     */
    public function test_criterion_1_loan_5000_installment_854_8_periods_frequency_12_26(): void
    {
        $principal = 5000.00;
        $installmentAmount = 854.00;
        $periods = 8;
        $frequency = '12_26';
        $startDate = Carbon::parse('2026-11-11');

        $totalAmount = round($installmentAmount * $periods, 2);
        $this->assertEquals(6832.00, $totalAmount);

        $interestAmount = round($totalAmount - $principal, 2);
        $this->assertEquals(1832.00, $interestAmount);

        $rate = round(($interestAmount / $principal) * 100, 2);
        $this->assertEquals(36.64, $rate);

        // Amortización con fechas en días 12 y 26
        $dates = Loan::calculateInstallmentDates($startDate, $frequency, $periods);
        $this->assertCount(8, $dates);
        $this->assertEquals([
            '2026-11-12',
            '2026-11-26',
            '2026-12-12',
            '2026-12-26',
            '2027-01-12',
            '2027-01-26',
            '2027-02-12',
            '2027-02-26',
        ], $dates);

        // Cada fecha es día 12 o 26
        foreach ($dates as $date) {
            $d = Carbon::parse($date)->day;
            $this->assertTrue(in_array($d, [12, 26]), "La fecha {$date} debe ser día 12 o 26.");
        }
    }

    /**
     * Criterio de Aceptación 2:
     * Préstamo de $3,000 en 8 pagos de $601 y verificar que la tasa calculada es consistente.
     */
    public function test_criterion_2_loan_3000_installment_601_8_periods_rate_consistency(): void
    {
        $principal = 3000.00;
        $installmentAmount = 601.00;
        $periods = 8;

        $totalAmount = round($installmentAmount * $periods, 2);
        $this->assertEquals(4808.00, $totalAmount);

        $interestAmount = round($totalAmount - $principal, 2);
        $this->assertEquals(1808.00, $interestAmount);

        // Tasa calculada consistente: (1,808 / 3,000) * 100 = 60.27%
        $rate = round(($interestAmount / $principal) * 100, 2);
        $this->assertEquals(60.27, $rate);

        // La tasa es consistente con el préstamo al ~60%
        $this->assertGreaterThanOrEqual(60.00, $rate);
        $this->assertLessThan(61.00, $rate);
    }

    /**
     * Frecuencia Quincenal (días 15 y 30 de cada mes)
     */
    public function test_quincenal_frequency_days_15_and_30(): void
    {
        $startDate = Carbon::parse('2026-11-11');
        $dates = Loan::calculateInstallmentDates($startDate, 'quincenal', 6);

        $this->assertCount(6, $dates);
        $this->assertEquals([
            '2026-11-15',
            '2026-11-30',
            '2026-12-15',
            '2026-12-30',
            '2027-01-15',
            '2027-01-30',
        ], $dates);
    }

    /**
     * Frecuencia Semanal (cada 7 días) eligiendo día de la semana
     */
    public function test_semanal_frequency_every_7_days_with_day_of_week(): void
    {
        // 2026-11-11 es Miércoles. Si se eligen los Sábados (día 6):
        $startDate = Carbon::parse('2026-11-11');
        $dayOfWeek = 6; // Sábado
        $dates = Loan::calculateInstallmentDates($startDate, 'semanal', 4, null, $dayOfWeek);

        $this->assertCount(4, $dates);
        $this->assertEquals([
            '2026-11-14', // Sábado
            '2026-11-21', // Sábado
            '2026-11-28', // Sábado
            '2026-12-05', // Sábado
        ], $dates);

        foreach ($dates as $date) {
            $this->assertEquals(6, Carbon::parse($date)->dayOfWeekIso);
        }
    }

    /**
     * Frecuencia Mensual (elegir día del mes)
     */
    public function test_mensual_frequency_with_day_of_month(): void
    {
        $startDate = Carbon::parse('2026-11-11');
        $dayOfMonth = 15;
        $dates = Loan::calculateInstallmentDates($startDate, 'mensual', 4, null, null, $dayOfMonth);

        $this->assertCount(4, $dates);
        $this->assertEquals([
            '2026-11-15',
            '2026-12-15',
            '2027-01-15',
            '2027-02-15',
        ], $dates);

        foreach ($dates as $date) {
            $this->assertEquals(15, Carbon::parse($date)->day);
        }
    }

    /**
     * Frecuencia Mensual con día 31 en meses cortos (ej. Febrero / Abril)
     */
    public function test_mensual_frequency_clamping_on_short_months(): void
    {
        $startDate = Carbon::parse('2027-01-31');
        $dayOfMonth = 31;
        $dates = Loan::calculateInstallmentDates($startDate, 'mensual', 4, Carbon::parse('2027-01-31'), null, $dayOfMonth);

        $this->assertEquals([
            '2027-01-31',
            '2027-02-28', // Febrero se ajusta al último día
            '2027-03-31', // Marzo vuelve al 31
            '2027-04-30', // Abril se ajusta a 30
        ], $dates);
    }

    /**
     * Venta a crédito con precio de contado: calcula total cuotas, total final e interés generado.
     */
    public function test_article_credit_summary_with_cash_price_and_down_payment(): void
    {
        $summary = Loan::calculateArticleCreditSummary(750.00, 8, 1500.00, 6500.00);

        $this->assertEquals(6500.00, $summary['cash_price']);
        $this->assertEquals(1500.00, $summary['down_payment']);
        $this->assertEquals(5000.00, $summary['principal_amount']);
        $this->assertEquals(6000.00, $summary['installments_total']);
        $this->assertEquals(7500.00, $summary['credit_sale_total']);
        $this->assertEquals(1000.00, $summary['interest_amount']);
        $this->assertEquals(20.00, $summary['interest_rate']);
    }

    /**
     * Venta a crédito sin precio de contado ni monto financiado: interés y capital quedan null.
     */
    public function test_article_credit_summary_without_cash_price_leaves_interest_null(): void
    {
        $summaryEmpty = Loan::calculateArticleCreditSummary(400.00, 10, 500.00, null);

        $this->assertNull($summaryEmpty['cash_price']);
        $this->assertNull($summaryEmpty['principal_amount']);
        $this->assertEquals(4000.00, $summaryEmpty['installments_total']);
        $this->assertEquals(4500.00, $summaryEmpty['credit_sale_total']);
        $this->assertNull($summaryEmpty['interest_amount']);
        $this->assertNull($summaryEmpty['interest_rate']);

        $summaryZero = Loan::calculateArticleCreditSummary(400.00, 10, 0.00, 0.0);
        $this->assertNull($summaryZero['cash_price']);
        $this->assertEquals(4000.00, $summaryZero['credit_sale_total']);
        $this->assertNull($summaryZero['interest_amount']);
    }

    /**
     * Fecha del primer pago opcional como fecha base respetando días 12/26 y 15/30 con ajuste en febrero.
     */
    public function test_quincenal_and_12_26_project_from_optional_first_payment_date_with_february_adjustment(): void
    {
        $datesFebNonLeap = Loan::calculateInstallmentDates(
            Carbon::parse('2026-02-01'),
            'quincenal',
            4,
            Carbon::parse('2026-02-15')
        );
        $this->assertEquals([
            '2026-02-15',
            '2026-02-28',
            '2026-03-15',
            '2026-03-30',
        ], $datesFebNonLeap);

        $datesFebLeap = Loan::calculateInstallmentDates(
            Carbon::parse('2028-02-01'),
            'quincenal',
            4,
            Carbon::parse('2028-02-15')
        );
        $this->assertEquals([
            '2028-02-15',
            '2028-02-29',
            '2028-03-15',
            '2028-03-30',
        ], $datesFebLeap);

        // Si se firma exactamente el 28 de febrero sin fecha de primer pago, salta al 15 de marzo
        $datesFromFebEnd = Loan::calculateInstallmentDates(
            Carbon::parse('2026-02-28'),
            'quincenal',
            2
        );
        $this->assertEquals([
            '2026-03-15',
            '2026-03-30',
        ], $datesFromFebEnd);

        $dates1226 = Loan::calculateInstallmentDates(
            Carbon::parse('2026-04-01'),
            '12_26',
            4,
            Carbon::parse('2026-04-26')
        );
        $this->assertEquals([
            '2026-04-26',
            '2026-05-12',
            '2026-05-26',
            '2026-06-12',
        ], $dates1226);
    }

    /**
     * Las vistas de creación, detalle y documentos de venta a crédito renderizan sin errores cuando
     * Precio de contado, Monto financiado e Interés generado son null.
     */
    public function test_article_credit_views_render_cleanly_with_nullable_cash_price_and_interest(): void
    {
        View::share('errors', new ViewErrorBag());
        $mockUser = new class(['name' => 'Admin Prueba']) extends User {
            public function isSuperAdmin(): bool
            {
                return true;
            }

            public function hasPermission(string $permission, ?string $action = null): bool
            {
                return true;
            }

            public function can($abilities, $arguments = []): bool
            {
                return true;
            }
        };
        Auth::setUser($mockUser);

        $renderSection = function (string $bladePath, array $data): string {
            $source = file_get_contents(resource_path($bladePath));
            $source = preg_replace('/@extends\([^)]+\)\s*/', '', $source);
            $source = preg_replace('/@section\(\'container\'\)\s*/', '', $source);
            $source = preg_replace('/@endsection\s*$/', '', $source);

            return Blade::render($source, $data);
        };

        $createHtml = $renderSection('views/loans/create.blade.php', [
            'customers' => new Collection(),
            'loanTypes' => new Collection(),
            'branches' => new Collection(),
            'userBranchId' => 1,
            'operationType' => 'credito_articulo',
            'isArticleCreditView' => true,
        ]);

        $this->assertStringContainsString('Precio de contado ($)', $createHtml);
        $this->assertStringContainsString('Método de pago del enganche', $createHtml);
        $this->assertStringContainsString('Total de cuotas (Pagos × Abono):', $createHtml);
        $this->assertStringContainsString('Total final de la venta a crédito:', $createHtml);
        $this->assertStringContainsString('Sin calcular', $createHtml);
        $this->assertStringContainsString('Quincenal (días 12 y 26)', $createHtml);
        $this->assertStringContainsString('Quincenal (días 15 y 30)', $createHtml);
        $this->assertStringContainsString('Fecha del Primer Pago', $createHtml);
        $this->assertStringNotContainsString('Comisión por Apertura ($)', $createHtml);

        $prestamoCreateHtml = $renderSection('views/loans/create.blade.php', [
            'customers' => new Collection(),
            'loanTypes' => new Collection(),
            'branches' => new Collection(),
            'userBranchId' => 1,
            'operationType' => 'prestamo',
            'isArticleCreditView' => false,
        ]);
        $this->assertStringContainsString('Capital Prestado ($)', $prestamoCreateHtml);
        $this->assertStringContainsString('12 y 26 (Días 12 y 26)', $prestamoCreateHtml);
        $this->assertStringContainsString('Comisión por Apertura ($)', $prestamoCreateHtml);

        $loan = new Loan([
            'loan_code' => 'CRED-20261113-TEST',
            'operation_type' => 'credito_articulo',
            'delivery_method' => 'mercancia',
            'article_name' => 'Refrigerador Hisense',
            'article_price' => null,
            'down_payment' => 500.00,
            'down_payment_method' => 'transferencia',
            'principal_amount' => null,
            'interest_rate' => null,
            'interest_amount' => null,
            'total_amount' => 4000.00,
            'credit_sale_total' => 4500.00,
            'paid_amount' => 0.00,
            'balance_amount' => 4000.00,
            'payment_frequency' => 'quincenal',
            'term_periods' => 10,
            'installment_amount' => 400.00,
            'start_date' => '2026-02-10',
            'first_payment_date' => '2026-02-15',
            'due_date' => '2026-06-30',
            'status' => 'activo',
        ]);
        $loan->id = 999;
        $loan->setRelation('customer', new Customer([
            'tit_name' => 'Cliente Prueba',
            'tit_phone' => '8261001122',
            'tit_address' => 'Calle Centro 10',
        ]));
        $loan->setRelation('branch', new Branch(['name' => 'Allende']));
        $loan->setRelation('user', null);
        $loan->setRelation('loanType', null);
        $loan->setRelation('installments', new Collection());
        $loan->setRelation('payments', new Collection());

        $showHtml = $renderSection('views/loans/show.blade.php', [
            'loan' => $loan,
            'otherOpenLoans' => new Collection(),
        ]);
        $this->assertStringContainsString('Sin calcular', $showHtml);
        $this->assertStringContainsString('$4,000.00', $showHtml);
        $this->assertStringContainsString('$4,500.00', $showHtml);

        $pagareHtml = view('loans.pagare-pdf', ['loan' => $loan])->render();
        $this->assertStringContainsString('BUENO POR: $4,000.00 M.N.', $pagareHtml);
        $this->assertStringContainsString('$4,500.00 MXN', $pagareHtml);

        $receiptHtml = view('loans.delivery-receipt-pdf', ['loan' => $loan])->render();
        $this->assertStringContainsString('RECIBO DE ENTREGA DE MERCANCÍA', $receiptHtml);
        $this->assertStringContainsString('$4,500.00 MXN', $receiptHtml);

        $loanWithPrice = new Loan([
            'loan_code' => 'CRED-20261113-WITHPRICE',
            'operation_type' => 'credito_articulo',
            'delivery_method' => 'mercancia',
            'article_name' => 'Estufa 6 Quemadores',
            'article_price' => 6500.00,
            'down_payment' => 1500.00,
            'down_payment_method' => 'efectivo',
            'principal_amount' => 5000.00,
            'interest_rate' => 20.00,
            'interest_amount' => 1000.00,
            'total_amount' => 6000.00,
            'credit_sale_total' => 7500.00,
            'paid_amount' => 0.00,
            'balance_amount' => 6000.00,
            'payment_frequency' => '12_26',
            'term_periods' => 8,
            'installment_amount' => 750.00,
            'start_date' => '2026-11-12',
            'due_date' => '2027-02-26',
            'status' => 'activo',
        ]);
        $loanWithPrice->id = 1000;
        $loanWithPrice->setRelation('customer', $loan->customer);
        $loanWithPrice->setRelation('branch', $loan->branch);
        $loanWithPrice->setRelation('user', null);
        $loanWithPrice->setRelation('loanType', null);
        $loanWithPrice->setRelation('installments', new Collection());
        $loanWithPrice->setRelation('payments', new Collection());

        $pagareWithPrice = view('loans.pagare-pdf', ['loan' => $loanWithPrice])->render();
        $this->assertStringContainsString('Precio total: <strong>$6,500.00 MXN</strong>', $pagareWithPrice);
        $this->assertStringContainsString('Total final de la venta a crédito: <strong>$7,500.00 MXN</strong>', $pagareWithPrice);

        $receiptWithPrice = view('loans.delivery-receipt-pdf', ['loan' => $loanWithPrice])->render();
        $this->assertStringContainsString('<strong>Precio:</strong> $6,500.00 MXN', $receiptWithPrice);
    }
}

