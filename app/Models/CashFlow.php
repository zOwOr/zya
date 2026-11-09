<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'pos_transaction_id',
        'customer_id',
        'customer_name',
        'type',
        'amount',
        'description',
        'reference',
        'module',
        'payment_method',
        'cash_amount',
        'card_amount',
        'transfer_amount',
        'commission_amount',
        'daily_cut_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

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

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        if (!empty($this->customer_name)) {
            return $this->customer_name;
        }
        if ($this->customer) {
            return $this->customer->tit_name;
        }
        return 'Público en General / Operación Interna';
    }
}
