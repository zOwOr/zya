<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $table = 'gifts';

    protected $fillable = [
        'code',
        'name',
        'brand',
        'description',
        'supplier_id',
        'fin_supplier_id',
        'supplier_name',
        'branch_id',
        'stock_quantity',
        'min_stock',
        'unit_cost',
        'status',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function finSupplier()
    {
        return $this->belongsTo(FinSupplier::class, 'fin_supplier_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function movements()
    {
        return $this->hasMany(GiftMovement::class, 'gift_id')->orderByDesc('movement_date');
    }

    public function getSupplierDisplayNameAttribute(): string
    {
        if (!empty($this->supplier_name)) {
            return $this->supplier_name;
        }
        if ($this->finSupplier) {
            return $this->finSupplier->name;
        }
        if ($this->supplier) {
            return $this->supplier->name;
        }
        return 'General / Sin proveedor';
    }
}

