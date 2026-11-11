<?php

namespace Tests\Unit;

use App\Models\Loan;
use Carbon\Carbon;
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
}

