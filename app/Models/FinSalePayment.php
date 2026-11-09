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
        'device_id',
        'financiera_id',
        'branch_id',
        'user_id',
        'pos_transaction_id',
        'receipt_code',
        'tag_contrato',
        'imei',
        'customer_name',
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
        $bd = is_array($this->payment_breakdown) ? $this->payment_breakdown : [];
        if (isset($bd['cash'])) {
            return (float) $bd['cash'];
        }
        return $this->payment_method === 'efectivo' ? $this->total_paid : 0.0;
    }

    public function getCardAmountAttribute(): float
    {
        $bd = is_array($this->payment_breakdown) ? $this->payment_breakdown : [];
        if (isset($bd['card'])) {
            return (float) $bd['card'];
        }
        return $this->payment_method === 'tarjeta' ? $this->total_paid : 0.0;
    }

    public function getTransferAmountAttribute(): float
    {
        $bd = is_array($this->payment_breakdown) ? $this->payment_breakdown : [];
        if (isset($bd['transfer'])) {
            return (float) $bd['transfer'];
        }
        return $this->payment_method === 'transferencia' ? $this->total_paid : 0.0;
    }
}
