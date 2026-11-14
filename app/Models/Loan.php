<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loans';

    protected $fillable = [
        'loan_code',
        'operation_type',
        'delivery_method',
        'branch_id',
        'customer_id',
        'user_id',
        'loan_type_id',
        'article_price',
        'down_payment',
        'down_payment_method',
        'article_status',
        'article_name',
        'article_brand',
        'article_model',
        'article_color',
        'article_capacity',
        'article_imei',
        'article_serial',
        'article_warranty',
        'article_observations',
        'principal_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'credit_sale_total',
        'opening_commission',
        'paid_amount',
        'balance_amount',
        'payment_frequency',
        'term_periods',
        'installment_amount',
        'start_date',
        'first_payment_date',
        'due_date',
        'type_specific_data',
        'video_path',
        'promissory_note_path',
        'status',
        'closed_manually_at',
        'closed_by',
        'closing_reason',
        'closing_final_paid',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'article_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'credit_sale_total' => 'decimal:2',
        'opening_commission' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'closing_final_paid' => 'decimal:2',
        'start_date' => 'date',
        'first_payment_date' => 'date',
        'due_date' => 'date',
        'closed_manually_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'type_specific_data' => 'array',
    ];

    public function getPaymentFrequencyLabelAttribute(): string
    {
        return self::frequencyLabel($this->payment_frequency);
    }

    public static function frequencyLabel(?string $frequency): string
    {
        return match ($frequency) {
            '12_26' => '12 y 26',
            'quincenal' => 'Quincenal (15 y 30)',
            'semanal' => 'Semanal',
            'mensual' => 'Mensual',
            default => ucfirst((string) $frequency),
        };
    }

    /**
     * Calcula el arreglo de fechas de vencimiento para cada cuota del préstamo.
     *
     * @param Carbon $startDate Fecha de inicio o suscripción del préstamo
     * @param string $frequency Frecuencia ('12_26', 'quincenal', 'semanal', 'mensual')
     * @param int $periods Número de cuotas / pagos
     * @param Carbon|null $firstPaymentDate Fecha del primer pago (opcional)
     * @param int|null $dayOfWeek Día de cobro semanal (1 = Lunes, ..., 7 = Domingo)
     * @param int|null $dayOfMonth Día de cobro mensual (1 a 31)
     * @return array<string> Arreglo de fechas en formato 'Y-m-d'
     */
    public static function calculateInstallmentDates(
        Carbon $startDate,
        string $frequency,
        int $periods,
        ?Carbon $firstPaymentDate = null,
        ?int $dayOfWeek = null,
        ?int $dayOfMonth = null
    ): array {
        $dates = [];
        $periods = max(1, $periods);
        $current = $firstPaymentDate ? $firstPaymentDate->copy() : null;

        if (!$current) {
            if ($frequency === '12_26') {
                if ($startDate->day < 12) {
                    $current = $startDate->copy()->day(12);
                } elseif ($startDate->day < 26) {
                    $current = $startDate->copy()->day(26);
                } else {
                    $current = $startDate->copy()->startOfMonth()->addMonthNoOverflow()->day(12);
                }
            } elseif ($frequency === 'quincenal') {
                $endOfMonthDay = min(30, $startDate->daysInMonth);
                if ($startDate->day < 15) {
                    $current = $startDate->copy()->day(15);
                } elseif ($startDate->day < $endOfMonthDay) {
                    $current = $startDate->copy()->day($endOfMonthDay);
                } else {
                    $current = $startDate->copy()->startOfMonth()->addMonthNoOverflow()->day(15);
                }
            } elseif ($frequency === 'semanal') {
                $dow = $dayOfWeek ?: $startDate->dayOfWeekIso;
                if ($startDate->dayOfWeekIso == $dow) {
                    $current = $startDate->copy()->addWeek();
                } else {
                    $current = $startDate->copy()->next($dow);
                }
            } elseif ($frequency === 'mensual') {
                $dom = $dayOfMonth ?: $startDate->day;
                if ($startDate->day < $dom) {
                    $current = $startDate->copy()->day(min($dom, $startDate->daysInMonth));
                } else {
                    $nextMonth = $startDate->copy()->startOfMonth()->addMonthNoOverflow();
                    $current = $nextMonth->day(min($dom, $nextMonth->daysInMonth));
                }
            } else {
                $current = $startDate->copy()->addWeek();
            }
        }

        $domMensual = $dayOfMonth ?: ($firstPaymentDate ? $firstPaymentDate->day : $startDate->day);

        for ($i = 1; $i <= $periods; $i++) {
            $dates[] = $current->toDateString();
            if ($i < $periods) {
                if ($frequency === '12_26') {
                    if ($current->day < 26) {
                        $current = $current->copy()->day(26);
                    } else {
                        $current = $current->copy()->startOfMonth()->addMonthNoOverflow()->day(12);
                    }
                } elseif ($frequency === 'quincenal') {
                    if ($current->day <= 15) {
                        $current = $current->copy()->day(min(30, $current->daysInMonth));
                    } else {
                        $current = $current->copy()->startOfMonth()->addMonthNoOverflow()->day(15);
                    }
                } elseif ($frequency === 'semanal') {
                    $current = $current->copy()->addDays(7);
                } elseif ($frequency === 'mensual') {
                    $nextMonth = $current->copy()->startOfMonth()->addMonthNoOverflow();
                    $current = $nextMonth->day(min($domMensual, $nextMonth->daysInMonth));
                } else {
                    $current = $current->copy()->addWeeks(1);
                }
            }
        }

        return $dates;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class, 'loan_type_id');
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id')->orderBy('installment_number');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'loan_id')->orderByDesc('paid_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['activo', 'mora']);
    }

    public function isArticleCredit(): bool
    {
        return $this->operation_type === 'credito_articulo';
    }

    /**
     * Obtiene el total final de la venta a crédito (Total de cuotas + Enganche),
     * manteniendo compatibilidad con registros históricos donde credit_sale_total es null.
     */
    public function getResolvedCreditSaleTotalAttribute(): float
    {
        if ($this->credit_sale_total !== null) {
            return round((float) $this->credit_sale_total, 2);
        }

        return round((float) $this->total_amount + (float) ($this->down_payment ?? 0), 2);
    }

    /**
     * Calcula de forma centralizada los totales e interés de una venta a crédito de artículos.
     *
     * @return array{
     *     cash_price: ?float,
     *     down_payment: float,
     *     principal_amount: ?float,
     *     installment_amount: float,
     *     term_periods: int,
     *     installments_total: float,
     *     credit_sale_total: float,
     *     interest_amount: ?float,
     *     interest_rate: ?float
     * }
     */
    public static function calculateArticleCreditSummary(
        float $installmentAmount,
        int $periods,
        float $downPayment = 0.0,
        ?float $cashPrice = null,
        ?float $manualPrincipal = null
    ): array {
        $periods = max(1, $periods);
        $installmentAmount = round(max(0.0, $installmentAmount), 2);
        $downPayment = round(max(0.0, $downPayment), 2);
        $normalizedCashPrice = ($cashPrice !== null && $cashPrice > 0) ? round($cashPrice, 2) : null;

        $installmentsTotal = round($installmentAmount * $periods, 2);
        $creditSaleTotal = round($installmentsTotal + $downPayment, 2);

        if ($normalizedCashPrice !== null) {
            $principalAmount = ($manualPrincipal !== null && $manualPrincipal > 0)
                ? round($manualPrincipal, 2)
                : max(0.0, round($normalizedCashPrice - $downPayment, 2));
            $interestAmount = round($creditSaleTotal - $normalizedCashPrice, 2);
            $rateBase = $principalAmount > 0 ? $principalAmount : $normalizedCashPrice;
            $interestRate = $rateBase > 0 ? round(($interestAmount / $rateBase) * 100, 2) : null;
        } else {
            $principalAmount = ($manualPrincipal !== null && $manualPrincipal > 0)
                ? round($manualPrincipal, 2)
                : null;
            $interestAmount = null;
            $interestRate = null;
        }

        return [
            'cash_price' => $normalizedCashPrice,
            'down_payment' => $downPayment,
            'principal_amount' => $principalAmount,
            'installment_amount' => $installmentAmount,
            'term_periods' => $periods,
            'installments_total' => $installmentsTotal,
            'credit_sale_total' => $creditSaleTotal,
            'interest_amount' => $interestAmount,
            'interest_rate' => $interestRate,
        ];
    }

    public function getOperationLabelAttribute(): string
    {
        return $this->isArticleCredit() ? 'Venta a Crédito de Artículo' : 'Préstamo Personal';
    }

    public function getArticleStatusLabelAttribute(): string
    {
        return match ($this->article_status) {
            'nuevo' => 'Nuevo',
            'seminuevo' => 'Seminuevo',
            'servicio' => 'Servicio',
            default => '',
        };
    }

    /**
     * Resumen corto legible del artículo vendido a crédito.
     */
    public function getArticleSummaryLabelAttribute(): string
    {
        $parts = array_filter([
            $this->article_name,
            $this->article_brand,
            $this->article_model,
            $this->article_capacity,
            $this->article_color,
        ], fn ($val) => !is_null($val) && trim((string) $val) !== '');

        if (!empty($parts)) {
            return implode(' ', $parts);
        }

        return 'Artículo / Mercancía';
    }

    /**
     * Arreglo ordenado de especificaciones no vacías de la ficha del artículo.
     *
     * @return array<string, string>
     */
    public function getArticleSpecsAttribute(): array
    {
        $raw = [
            'Estado / Tipo' => $this->article_status_label,
            'Artículo / Descripción' => $this->article_name,
            'Marca' => $this->article_brand,
            'Modelo' => $this->article_model,
            'Color' => $this->article_color,
            'Capacidad' => $this->article_capacity,
            'IMEI' => $this->article_imei,
            'Número de Serie' => $this->article_serial,
            'Garantía' => $this->article_warranty,
            'Observaciones' => $this->article_observations,
        ];

        $result = [];
        foreach ($raw as $label => $value) {
            $trimmed = trim((string) ($value ?? ''));
            if ($trimmed !== '') {
                $result[$label] = $trimmed;
            }
        }

        return $result;
    }

    /**
     * Frase en español para describir la periodicidad en el pagaré y el recibo.
     */
    public function getPaymentScheduleDescriptionAttribute(): string
    {
        return match ($this->payment_frequency) {
            '12_26' => 'programados los días 12 y 26 de cada mes',
            'quincenal' => 'programados de forma quincenal (días 15 y 30 de cada mes)',
            'semanal' => 'programados de forma semanal',
            'mensual' => 'programados de forma mensual',
            default => 'con periodicidad ' . strtolower($this->payment_frequency_label),
        };
    }

    /**
     * Resuelve datos del aval combinando ficha del cliente y captura específica del préstamo.
     *
     * @return array{name: string, phone: string, address: string, has_aval: bool}
     */
    public function resolveAvalInfo(): array
    {
        $extra = is_array($this->type_specific_data) ? $this->type_specific_data : [];
        $name = trim((string) ($extra['aval_nombre'] ?? $extra['aval_name'] ?? $this->customer?->aval_name ?? ''));
        $phone = trim((string) ($extra['aval_telefono'] ?? $extra['aval_phone'] ?? $this->customer?->aval_phone ?? ''));
        $address = trim((string) ($extra['aval_domicilio'] ?? $extra['aval_address'] ?? $this->customer?->aval_address ?? ''));

        if ($name === '' && $this->customer?->aval_name) {
            $name = trim((string) $this->customer->aval_name);
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'has_aval' => $name !== '' || $phone !== '' || $address !== '',
        ];
    }

    /**
     * Lugar de firma / entrega basado en la sucursal del préstamo o crédito.
     */
    public function resolvePlaceOfSigning(): string
    {
        $branchName = trim((string) ($this->branch?->name ?? 'Allende'));
        if (stripos($branchName, 'cercado') !== false) {
            return 'El Cercado, Santiago, Nuevo León';
        }
        if (stripos($branchName, 'nuevo león') !== false || stripos($branchName, 'n.l.') !== false) {
            return $branchName;
        }
        return $branchName . ', Nuevo León';
    }

    /**
     * Formatea una fecha en español natural (ej. "miércoles 11 de noviembre del 2026" o sin día).
     */
    public static function formatDateEs(?Carbon $date, bool $includeWeekday = false): string
    {
        if (!$date) {
            return '-';
        }
        $weekdays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        $base = $date->day . ' de ' . $months[$date->month] . ' de ' . $date->year;
        if ($includeWeekday) {
            return $weekdays[$date->dayOfWeek] . ' ' . $date->day . ' de ' . $months[$date->month] . ' del ' . $date->year;
        }
        return $base;
    }

    /**
     * Convierte la cantidad de pagos a texto en español (ej. 8 -> "ocho").
     */
    public static function integerCountInSpanish(int $count): string
    {
        $units = [
            0 => 'cero', 1 => 'un', 2 => 'dos', 3 => 'tres', 4 => 'cuatro',
            5 => 'cinco', 6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve',
            10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce',
            15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho',
            19 => 'diecinueve', 20 => 'veinte', 21 => 'veintiún', 22 => 'veintidós',
            23 => 'veintitrés', 24 => 'veinticuatro', 25 => 'veinticinco',
            26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
        ];
        if (isset($units[$count])) {
            return $units[$count];
        }
        if ($count < 100) {
            $tens = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];
            $d = intdiv($count, 10);
            $u = $count % 10;
            return ($tens[$d] ?? (string) $count) . ($u > 0 ? ' y ' . $units[$u] : '');
        }
        return (string) $count;
    }

    public function hasOverdueInstallments(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        return $this->installments()
            ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
            ->whereDate('scheduled_date', '<', Carbon::today())
            ->exists();
    }

    public function scopeForBranch($query, $branchId = null)
    {
        if (auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.all_branches'))) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            return $query;
        }

        $userBranch = $branchId ?: (auth()->check() ? auth()->user()->branch_id : null);
        if ($userBranch) {
            $query->where('branch_id', $userBranch);
        }

        return $query;
    }
}

