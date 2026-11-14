<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Gift extends Model
{
    use HasFactory;

    protected $table = 'gifts';

    protected $fillable = [
        'code',
        'name',
        'normalized_name',
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

    public static function normalizeName(?string $name): string
    {
        $clean = preg_replace('/\s+/u', ' ', trim((string) $name)) ?? trim((string) $name);
        return mb_strtolower($clean, 'UTF-8');
    }

    protected static function booted(): void
    {
        static::saving(function (Gift $gift) {
            if ($gift->name !== null) {
                $gift->name = preg_replace('/\s+/u', ' ', trim((string) $gift->name)) ?? trim((string) $gift->name);
                if (Schema::hasColumn('gifts', 'normalized_name')) {
                    $gift->normalized_name = static::normalizeName($gift->name);
                }
            }
        });

        // Compatibilidad automática si un flujo o prueba heredada pasa branch_id + stock_quantity al crear:
        // migra ese stock inicial hacia gift_stocks y deja el registro maestro con branch_id = null.
        static::created(function (Gift $gift) {
            if (!empty($gift->branch_id) && Schema::hasTable('gift_stocks')) {
                $branchId = (int) $gift->branch_id;
                $qty = max(0, (int) $gift->stock_quantity);

                GiftStock::updateOrCreate(
                    ['gift_id' => $gift->id, 'branch_id' => $branchId],
                    ['stock_quantity' => $qty, 'min_stock' => $gift->min_stock]
                );

                static::where('id', $gift->id)->update([
                    'branch_id' => null,
                    'stock_quantity' => 0,
                ]);
                $gift->branch_id = null;
                $gift->stock_quantity = 0;
            }
        });
    }

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

    public function stocks()
    {
        return $this->hasMany(GiftStock::class, 'gift_id');
    }

    public function transfers()
    {
        return $this->hasMany(GiftTransfer::class, 'gift_id')
            ->orderByDesc('transferred_at')
            ->orderByDesc('id');
    }

    public function movements()
    {
        return $this->hasMany(GiftMovement::class, 'gift_id')
            ->orderByDesc('movement_date')
            ->orderByDesc('id');
    }

    public function stockInBranch(?int $branchId): int
    {
        if (empty($branchId)) {
            return (int) $this->stock_quantity;
        }

        if ($this->relationLoaded('stocks')) {
            return (int) ($this->stocks->firstWhere('branch_id', (int) $branchId)?->stock_quantity ?? 0);
        }

        return (int) ($this->stocks()->where('branch_id', (int) $branchId)->value('stock_quantity') ?? 0);
    }

    public function getCentralStockAttribute(): int
    {
        return (int) $this->stock_quantity;
    }

    public function getTotalBranchStockAttribute(): int
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('stock_quantity');
        }

        return (int) $this->stocks()->sum('stock_quantity');
    }

    public function getTotalGlobalStockAttribute(): int
    {
        return (int) $this->stock_quantity + $this->total_branch_stock;
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
