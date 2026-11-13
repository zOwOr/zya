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
        'external_customer_id',
        'customer_name',
        'type',
        'amount',
        'description',
        'reference',
        'module',
        'expense_type_id',
        'expense_type_name',
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

    public function externalCustomer()
    {
        return $this->belongsTo(ExternalCustomer::class, 'external_customer_id')->withTrashed();
    }

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function getResolvedExpenseTypeLabelAttribute(): ?string
    {
        if (!empty($this->expense_type_name)) {
            return $this->expense_type_name;
        }

        return $this->expenseType?->name;
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        if (!empty($this->customer_name)) {
            return $this->customer_name;
        }
        if ($this->externalCustomer) {
            return $this->externalCustomer->tit_name;
        }
        if ($this->customer) {
            return $this->customer->tit_name;
        }
        return 'Público en General / Operación Interna';
    }

    public function getIsExternalCustomerAttribute(): bool
    {
        return !empty($this->external_customer_id);
    }

    public function getEffectiveCashAmountAttribute(): float
    {
        $method = strtolower((string) $this->payment_method);
        if (in_array($method, ['transferencia', 'cheque', 'tarjeta', 'due'], true)) {
            return 0.0;
        }

        return (float) $this->cash_amount > 0
            ? round((float) $this->cash_amount, 2)
            : (in_array($method, ['efectivo', 'handcash', ''], true) ? round((float) $this->amount, 2) : 0.0);
    }

    public function getEffectiveCardAmountAttribute(): float
    {
        $method = strtolower((string) $this->payment_method);

        return (float) $this->card_amount > 0
            ? round((float) $this->card_amount, 2)
            : (in_array($method, ['tarjeta', 'due'], true) ? round((float) $this->amount, 2) : 0.0);
    }

    public function getEffectiveTransferAmountAttribute(): float
    {
        $method = strtolower((string) $this->payment_method);

        return (float) $this->transfer_amount > 0
            ? round((float) $this->transfer_amount, 2)
            : (in_array($method, ['transferencia', 'cheque'], true) ? round((float) $this->amount, 2) : 0.0);
    }
}
