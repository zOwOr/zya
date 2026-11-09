<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TandaPayment extends Model
{
    use HasFactory;

    protected $table = 'tanda_payments';

    protected $fillable = [
        'tanda_id',
        'tanda_client_id',
        'tanda_period_id',
        'branch_id',
        'user_id',
        'pos_transaction_id',
        'receipt_code',
        'period_number',
        'amount',
        'payment_method',
        'payment_breakdown',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_breakdown' => 'array',
        'paid_at' => 'datetime',
    ];

    public function tanda()
    {
        return $this->belongsTo(Tanda::class, 'tanda_id');
    }

    public function participant()
    {
        return $this->belongsTo(TandaClient::class, 'tanda_client_id');
    }

    public function period()
    {
        return $this->belongsTo(TandaPeriod::class, 'tanda_period_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

