<?php

namespace Tests\Unit;

use App\Models\FinSale;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HistoricalSalesImportTest extends TestCase
{
    /**
     * Test catalog normalization (case-insensitive, accent-insensitive)
     */
    public function test_catalog_normalization()
    {
        $normalize = function ($str) {
            if ($str === null || $str === '') return '';
            $str = mb_strtolower(trim((string)$str), 'UTF-8');
            $str = strtr(utf8_decode($str), utf8_decode('àáâãäåòóôõöøèéêëçìíîïùúûüñ'), 'aaaaaaooooooeeeeciiiiuuuun');
            $str = preg_replace('/[^a-z0-9]/', ' ', $str);
            return trim(preg_replace('/\s+/', ' ', $str));
        };

        $this->assertEquals('krediya', $normalize('Krediya'));
        $this->assertEquals('krediya', $normalize('  KREDIYA  '));
        $this->assertEquals('krediya', $normalize('Krediyá'));
        $this->assertEquals('samsung', $normalize('SAMSUNG'));
        $this->assertEquals('motorola', $normalize('Motorola  '));
        $this->assertEquals('xiaomi', $normalize('XIAOMI'));
    }

    /**
     * Test strict date validation (rejection of ND, 1899, invalid format, accepting DD/MM/YYYY)
     */
    public function test_strict_date_validation()
    {
        $validateDate = function ($rawDate) {
            if ($rawDate === null || trim((string)$rawDate) === '') {
                return ['error' => 'La fecha de venta está vacía.'];
            }

            if (is_numeric($rawDate)) {
                try {
                    $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
                    $year = (int)$dt->format('Y');
                    if ($year < 2000 || $year > 2099) {
                        return ['error' => "Fecha inválida '{$rawDate}' (año {$year})."];
                    }
                    return ['date' => Carbon::instance($dt)];
                } catch (\Throwable $t) {
                    return ['error' => "Fecha numérica no interpretable '{$rawDate}'."];
                }
            }

            $str = trim((string)$rawDate);
            if (preg_match('/^(ND|#|N\/A|SIN|NULL)/i', $str) || preg_match('/\bND\b/i', $str)) {
                return ['error' => "La fecha '{$str}' contiene valores no válidos (ND/#)."];
            }

            $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d'];
            foreach ($formats as $fmt) {
                try {
                    $d = Carbon::createFromFormat($fmt, $str);
                    if ($d && checkdate((int)$d->month, (int)$d->day, (int)$d->year)) {
                        $year = (int)$d->year;
                        if ($year < 2000 || $year > 2099) {
                            return ['error' => "La fecha '{$str}' tiene un año inválido ({$year})."];
                        }
                        return ['date' => $d];
                    }
                } catch (\Throwable $t) {
                }
            }

            try {
                $parsed = Carbon::parse($str);
                if ($parsed && checkdate((int)$parsed->month, (int)$parsed->day, (int)$parsed->year)) {
                    $year = (int)$parsed->year;
                    if ($year < 2000 || $year > 2099) {
                        return ['error' => "La fecha '{$str}' tiene un año inválido ({$year})."];
                    }
                    return ['date' => $parsed];
                }
            } catch (\Throwable $t) {
            }

            return ['error' => "Formato de fecha inválido '{$str}'."];
        };

        // Valid dates
        $v1 = $validateDate('23/09/2023');
        $this->assertArrayHasKey('date', $v1);
        $this->assertEquals('2023-09-23', $v1['date']->format('Y-m-d'));

        $v2 = $validateDate('2024-01-15');
        $this->assertArrayHasKey('date', $v2);
        $this->assertEquals('2024-01-15', $v2['date']->format('Y-m-d'));

        // Invalid: year 1899 / 1900
        $inv1 = $validateDate('06/12/1899');
        $this->assertArrayHasKey('error', $inv1);

        // Invalid: ND
        $inv2 = $validateDate('ND 09/2023');
        $this->assertArrayHasKey('error', $inv2);

        // Invalid: format
        $inv3 = $validateDate('no-es-fecha');
        $this->assertArrayHasKey('error', $inv3);

        // Invalid: empty
        $inv4 = $validateDate('');
        $this->assertArrayHasKey('error', $inv4);
    }

    /**
     * Test term parsing
     */
    public function test_term_parsing()
    {
        $parseTerm = function ($val) {
            if ($val === null || trim((string)$val) === '') {
                return ['months' => null, 'weeks' => null];
            }
            $str = mb_strtolower(trim((string)$val), 'UTF-8');
            preg_match('/(\d+)/', $str, $matches);
            $num = isset($matches[1]) ? (int)$matches[1] : null;
            if (!$num) {
                return ['months' => null, 'weeks' => null];
            }
            if (str_contains($str, 'sem') || str_contains($str, 'week')) {
                return ['months' => null, 'weeks' => $num];
            }
            return ['months' => $num, 'weeks' => null];
        };

        $t1 = $parseTerm('12 meses');
        $this->assertEquals(12, $t1['months']);
        $this->assertNull($t1['weeks']);

        $t2 = $parseTerm('26 semanas');
        $this->assertNull($t2['months']);
        $this->assertEquals(26, $t2['weeks']);

        $t3 = $parseTerm('6');
        $this->assertEquals(6, $t3['months']);
    }

    /**
     * Test references parsing
     */
    public function test_references_parsing()
    {
        $parseReferences = function ($rawRef) {
            $data = [
                'ref1_name' => null, 'ref1_phone' => null,
                'ref2_name' => null, 'ref2_phone' => null,
                'ref3_name' => null, 'ref3_phone' => null,
            ];
            if (empty($rawRef)) return $data;

            $parts = preg_split('/[|\n\r]+/', (string)$rawRef);
            $parts = array_values(array_filter(array_map('trim', $parts)));

            for ($idx = 0; $idx < min(3, count($parts)); $idx++) {
                $part = $parts[$idx];
                $slot = $idx + 1;
                if (preg_match('/(\+?[\d\s\-\(\)]{7,15})/', $part, $pm)) {
                    $phone = trim($pm[1]);
                    $name = trim(str_replace($phone, '', $part));
                    $data["ref{$slot}_name"] = mb_substr($name, 0, 150) ?: mb_substr($part, 0, 150);
                    $data["ref{$slot}_phone"] = mb_substr($phone, 0, 30);
                } else {
                    $data["ref{$slot}_name"] = mb_substr($part, 0, 150);
                }
            }

            if (count($parts) === 0 && !empty(trim((string)$rawRef))) {
                $data['ref1_name'] = mb_substr(trim((string)$rawRef), 0, 150);
            }
            return $data;
        };

        $raw = 'Hermano: Juan 5512345678 | Mamá: Maria 5587654321';
        $res = $parseReferences($raw);
        $this->assertStringContainsString('Juan', $res['ref1_name']);
        $this->assertEquals('5512345678', $res['ref1_phone']);
        $this->assertStringContainsString('Maria', $res['ref2_name']);
        $this->assertEquals('5587654321', $res['ref2_phone']);
    }

    /**
     * Test seller_display_name accessor on FinSale
     */
    public function test_seller_display_name_accessor()
    {
        $saleWithTextOnly = new FinSale(['seller_name' => 'Vendedor Histórico Externo']);
        $this->assertEquals('Vendedor Histórico Externo', $saleWithTextOnly->seller_display_name);

        $saleEmpty = new FinSale([]);
        $this->assertEquals('N/A', $saleEmpty->seller_display_name);
    }

    /**
     * Test default date interval logic (current month)
     */
    public function test_default_date_interval_is_current_month()
    {
        $resolveDates = function (array $queryParams) {
            $defaultStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $defaultEndDate = Carbon::now()->endOfMonth()->format('Y-m-d');

            if (array_key_exists('start_date', $queryParams) || array_key_exists('end_date', $queryParams)) {
                $startDate = $queryParams['start_date'] ?? null;
                $endDate = $queryParams['end_date'] ?? null;
            } else {
                $startDate = $defaultStartDate;
                $endDate = $defaultEndDate;
            }

            return ['start_date' => $startDate, 'end_date' => $endDate];
        };

        // When no dates provided (initial load) -> defaults to current month
        $defaultDates = $resolveDates([]);
        $this->assertEquals(Carbon::now()->startOfMonth()->format('Y-m-d'), $defaultDates['start_date']);
        $this->assertEquals(Carbon::now()->endOfMonth()->format('Y-m-d'), $defaultDates['end_date']);

        // When custom range provided -> respects custom range
        $customDates = $resolveDates(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        $this->assertEquals('2026-01-01', $customDates['start_date']);
        $this->assertEquals('2026-03-31', $customDates['end_date']);

        // When user explicitly clears both -> empty/null to show all
        $clearedDates = $resolveDates(['start_date' => '', 'end_date' => '']);
        $this->assertEmpty($clearedDates['start_date']);
        $this->assertEmpty($clearedDates['end_date']);
    }
}

