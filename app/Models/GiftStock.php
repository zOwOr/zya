<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftStock extends Model
{
    use HasFactory;

    protected $table = 'gift_stocks';

    protected $fillable = [
        'gift_id',
        'branch_id',
        'stock_quantity',
        'min_stock',
    ];

    protected $casts = [
        'gift_id' => 'integer',
        'branch_id' => 'integer',
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
    ];

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

