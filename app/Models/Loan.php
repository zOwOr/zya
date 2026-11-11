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
        'branch_id',
        'customer_id',
        'user_id',
        'loan_type_id',
        'principal_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'opening_commission',
        'paid_amount',
        'balance_amount',
        'payment_frequency',
        'term_periods',
        'installment_amount',
        'start_date',
        'due_date',
        'type_specific_data',
        'video_path',
        'promissory_note_path',
        'status',
        'closed_manually_at',
        'closed_by',
        'closing_reason',
        'closing_final_paid',
        'notes',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'opening_commission' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'closing_final_paid' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'closed_manually_at' => 'datetime',
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
                if ($startDate->day < 15) {
                    $current = $startDate->copy()->day(15);
                } elseif ($startDate->day < 30) {
                    $current = $startDate->copy()->day(min(30, $startDate->daysInMonth));
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

