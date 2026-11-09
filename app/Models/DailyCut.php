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
}
