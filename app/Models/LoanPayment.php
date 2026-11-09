<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $table = 'loan_payments';

    protected $fillable = [
        'loan_id',
        'installment_id',
        'branch_id',
        'user_id',
        'pos_transaction_id',
        'payment_code',
        'payment_type',
        'amount',
        'payment_method',
        'payment_breakdown',
        'scheduled_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_breakdown' => 'array',
        'scheduled_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function installment()
    {
        return $this->belongsTo(LoanInstallment::class, 'installment_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}

