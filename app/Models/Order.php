<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Order extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'customer_id',
        'branch_id',
        'user_id',
        'order_date',
        'order_status',
        'total_products',
        'sub_total',
        'vat',
        'invoice_no',
        'total',
        'payment_status',
        'pay',
        'due',
        'device_id',
    ];

    public $sortable = [
        'customer_id',
        'branch_id',
        'order_date',
        'pay',
        'due',
        'total',
    ];

    protected $guarded = [
        'id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class, 'order_id')->orderBy('created_at', 'desc');
    }

    public function posTransaction()
    {
        return $this->hasOne(PosTransaction::class, 'order_id');
    }

    public function getResolvedBranchAttribute()
    {
        return $this->branch ?? $this->posTransaction?->branch ?? $this->paymentHistories?->first()?->branch;
    }

}
