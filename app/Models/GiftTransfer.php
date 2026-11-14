<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftTransfer extends Model
{
    use HasFactory;

    protected $table = 'gift_transfers';

    protected $fillable = [
        'transfer_code',
        'gift_id',
        'from_branch_id',
        'to_branch_id',
        'quantity',
        'from_previous_stock',
        'from_new_stock',
        'to_previous_stock',
        'to_new_stock',
        'user_id',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'gift_id' => 'integer',
        'from_branch_id' => 'integer',
        'to_branch_id' => 'integer',
        'quantity' => 'integer',
        'from_previous_stock' => 'integer',
        'from_new_stock' => 'integer',
        'to_previous_stock' => 'integer',
        'to_new_stock' => 'integer',
        'transferred_at' => 'datetime',
    ];

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movements()
    {
        return $this->hasMany(GiftMovement::class, 'gift_transfer_id');
    }

    public function getOriginLabelAttribute(): string
    {
        return $this->fromBranch?->name ?? 'Inventario General (Almacén Central)';
    }

    public function getDestinationLabelAttribute(): string
    {
        return $this->toBranch?->name ?? 'Sucursal';
    }
}

