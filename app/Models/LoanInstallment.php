<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $table = 'loan_installments';

    protected $fillable = [
        'loan_id',
        'installment_number',
        'scheduled_date',
        'paid_date',
        'scheduled_amount',
        'paid_amount',
        'late_fee',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'paid_date' => 'datetime',
        'scheduled_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->scheduled_amount - (float) $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['pendiente', 'parcial', 'vencido'])
            && $this->scheduled_date
            && $this->scheduled_date->lt(Carbon::today());
    }
}

