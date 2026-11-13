<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinSalePayment extends Model
{
    use HasFactory;

    protected $table = 'fin_sale_payments';

    protected $fillable = [
        'fin_sale_id',
        'external_customer_id',
        'device_id',
        'financiera_id',
        'branch_id',
        'user_id',
        'pos_transaction_id',
        'receipt_code',
        'tag_contrato',
        'imei',
        'customer_name',
        'is_external_company',
        'installment_number',
        'amount',
        'commission_amount',
        'total_amount',
        'payment_method',
        'payment_breakdown',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_breakdown' => 'array',
        'payment_date' => 'datetime',
        'is_external_company' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(FinSale::class, 'fin_sale_id')->withTrashed();
    }

    public function finSale()
    {
        return $this->belongsTo(FinSale::class, 'fin_sale_id')->withTrashed();
    }

    public function device()
    {
        return $this->belongsTo(FinDevice::class, 'device_id')->withTrashed();
    }

    public function financiera()
    {
        return $this->belongsTo(FinFinanciera::class, 'financiera_id');
    }

    public function finSupplier()
    {
        return $this->belongsTo(FinFinanciera::class, 'financiera_id');
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

    public function externalCustomer()
    {
        return $this->belongsTo(ExternalCustomer::class, 'external_customer_id')->withTrashed();
    }

    public function getDeviceTagAttribute(): ?string
    {
        return $this->tag_contrato;
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) ($this->total_amount ?? ($this->amount + $this->commission_amount));
    }

    public function getCashAmountAttribute(): float
    {
        $txFallback = $this->relationLoaded('posTransaction')
            ? ($this->posTransaction?->cash_amount !== null ? (float) $this->posTransaction->cash_amount : null)
            : null;

        return $this->resolveBreakdownAmount(['efectivo', 'cash'], 'efectivo', $txFallback);
    }

    public function getCardAmountAttribute(): float
    {
        $txFallback = $this->relationLoaded('posTransaction')
            ? ($this->posTransaction?->card_amount !== null ? (float) $this->posTransaction->card_amount : null)
            : null;

        return $this->resolveBreakdownAmount(['tarjeta', 'card'], 'tarjeta', $txFallback);
    }

    public function getTransferAmountAttribute(): float
    {
        $txFallback = $this->relationLoaded('posTransaction')
            ? ($this->posTransaction?->transfer_amount !== null ? (float) $this->posTransaction->transfer_amount : null)
            : null;

        return $this->resolveBreakdownAmount(['transferencia', 'transfer'], 'transferencia', $txFallback);
    }

    /**
     * Resuelve montos por método desde payment_breakdown soportando claves en español e inglés.
     */
    protected function resolveBreakdownAmount(array $keys, string $methodName, ?float $txFallback = null): float
    {
        $bd = is_array($this->payment_breakdown) ? $this->payment_breakdown : [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $bd) && $bd[$key] !== null && $bd[$key] !== '') {
                return round((float) $bd[$key], 2);
            }
        }

        if ($txFallback !== null && ($txFallback > 0 || $this->payment_method === 'mixto')) {
            return round($txFallback, 2);
        }

        return $this->payment_method === $methodName ? round($this->total_paid, 2) : 0.0;
    }
}
