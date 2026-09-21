<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinDevice extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_devices';

    protected $fillable = [
        'imei',
        'brand_id',
        'model',
        'color',
        'storage',
        'arrived_at',
        'branch_id',
        'supplier_id',
        'status',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($device) {
            if (empty($device->model)) {
                $device->model = 'Modelo no especificado';
            }
        });
    }

    public $sortable = [
        'imei',
        'model',
        'storage',
        'status',
        'created_at',
    ];

    protected $with = ['brand', 'branch'];

    public function supplier()
    {
        return $this->belongsTo(FinSupplier::class, 'supplier_id');
    }

    public function brand()
    {
        return $this->belongsTo(FinBrand::class, 'brand_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function transfers()
    {
        return $this->hasMany(FinDeviceTransfer::class, 'device_id');
    }

    public function sales()
    {
        return $this->hasMany(FinSale::class, 'device_id');
    }

    public function activeSale()
    {
        return $this->hasOne(FinSale::class, 'device_id')->where('status', 'activa')->latestOfMany();
    }

    public function latestSale()
    {
        return $this->hasOne(FinSale::class, 'device_id')->latestOfMany();
    }

    public function warranties()
    {
        return $this->hasMany(FinWarranty::class, 'device_id');
    }

    public function theftReports()
    {
        return $this->hasMany(FinTheftReport::class, 'device_id');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('imei', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('storage', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($b) use ($search) {
                      $b->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function ($s) use ($search) {
                      $s->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($branchId = $filters['branch_id'] ?? false) {
            $query->where('branch_id', $branchId);
        }

        if ($supplierId = $filters['supplier_id'] ?? false) {
            $query->where('supplier_id', $supplierId);
        }

        if ($model = $filters['model'] ?? false) {
            $query->where('model', 'like', "%{$model}%");
        }

        if ($status = $filters['status'] ?? false) {
            $query->where('status', $status);
        }

        if ($brandId = $filters['brand_id'] ?? false) {
            $query->where('brand_id', $brandId);
        }
    }
}
