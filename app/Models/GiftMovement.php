<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftMovement extends Model
{
    use HasFactory;

    protected $table = 'gift_movements';

    protected $fillable = [
        'gift_id',
        'gift_transfer_id',
        'branch_id',
        'user_id',
        'movement_type',
        'quantity',
        'previous_stock',
        'new_stock',
        'supplier_name',
        'customer_name',
        'customer_phone',
        'reference_module',
        'reference_code',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'movement_date' => 'datetime',
    ];

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function transfer()
    {
        return $this->belongsTo(GiftTransfer::class, 'gift_transfer_id');
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

