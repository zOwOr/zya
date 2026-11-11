<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCut extends Model
{
    protected $fillable = [
        'branch_id',
        'date',
        'cut_type',
        'cut_mode',
        'user_id',
        'opening_balance',
        'total_income',
        'cash_income',
        'card_income',
        'transfer_income',
        'total_expense',
        'withdraw_amount',
        'balance',
        'declared_cash',
        'difference_amount',
        'customer_breakdown',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'cash_income' => 'decimal:2',
        'card_income' => 'decimal:2',
        'transfer_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'withdraw_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'declared_cash' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'customer_breakdown' => 'array',
        'closed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene el saldo inicial (fondo de caja en efectivo) para una sucursal y fecha
     * a partir del último Corte Z anterior.
     * Prioriza el efectivo físico declarado (declared_cash) y, si no fue capturado,
     * calcula únicamente el efectivo esperado de ese corte (sin transferencias ni tarjeta).
     */
    public static function resolveOpeningBalance(int $branchId, string $date): float
    {
        $lastCut = self::where('branch_id', $branchId)
            ->where('cut_type', 'corte_z')
            ->whereDate('date', '<', $date)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if (!$lastCut) {
            return 0.0;
        }

        if ($lastCut->declared_cash !== null) {
            return round((float) $lastCut->declared_cash, 2);
        }

        $hasMethodBreakdown = ((float) $lastCut->cash_income > 0)
            || ((float) $lastCut->card_income > 0)
            || ((float) $lastCut->transfer_income > 0)
            || ((float) $lastCut->total_income == 0.0);

        if ($hasMethodBreakdown) {
            return round(
                (float) $lastCut->opening_balance + (float) $lastCut->cash_income - (float) $lastCut->total_expense,
                2
            );
        }

        return round((float) $lastCut->balance, 2);
    }
}
