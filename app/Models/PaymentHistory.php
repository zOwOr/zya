<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PaymentHistory extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'cash_amount',
        'card_amount',
        'transfer_amount',
        'method',
        'note',
        'user_id',
        'branch_id',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            $optional = ['note', 'cash_amount', 'card_amount', 'transfer_amount', 'branch_id'];
            foreach ($optional as $col) {
                if (array_key_exists($col, $model->attributes) && !Schema::hasColumn('payment_histories', $col)) {
                    unset($model->attributes[$col]);
                }
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
