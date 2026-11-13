<?php

namespace App\Models;

use App\Models\Concerns\StripsMissingSchemaColumns;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use StripsMissingSchemaColumns;

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

    protected static function optionalSchemaColumns(): array
    {
        return ['note', 'cash_amount', 'card_amount', 'transfer_amount', 'branch_id'];
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
