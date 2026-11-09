<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'transaction_code',
        'branch_id',
        'user_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'commission_total',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'cash_amount',
        'card_amount',
        'transfer_amount',
        'payment_references',
        'items_payload',
        'order_id',
        'drawer_opened',
        'status',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
        'payment_references' => 'array',
        'items_payload' => 'array',
        'drawer_opened' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function getFolioAttribute(): string
    {
        return (string) ($this->transaction_code ?? ('POS-' . $this->id));
    }

    public function getTransactionDateAttribute()
    {
        return $this->created_at;
    }

    public function getTabLabelAttribute(): string
    {
        return 'POS Central';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function financieraPayments()
    {
        return $this->hasMany(FinSalePayment::class, 'pos_transaction_id');
    }

    public function loanPayments()
    {
        return $this->hasMany(LoanPayment::class, 'pos_transaction_id');
    }

    public function tandaPayments()
    {
        return $this->hasMany(TandaPayment::class, 'pos_transaction_id');
    }

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class, 'pos_transaction_id');
    }
}
