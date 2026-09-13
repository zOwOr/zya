<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinSale extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_sales';

    protected $fillable = [
        'sale_code',
        'device_id',
        'financiera_id',
        'branch_id',
        'seller_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_ine',
        'customer_address',
        'price',
        'down_payment',
        'credit_amount',
        'term_months',
        'sale_date',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    public $sortable = [
        'sale_code',
        'customer_name',
        'price',
        'sale_date',
        'status',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'term_months' => 'integer',
        'sale_date' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $with = ['device', 'financiera', 'branch', 'seller'];

    public function device()
    {
        return $this->belongsTo(FinDevice::class, 'device_id');
    }

    public function financiera()
    {
        return $this->belongsTo(FinFinanciera::class, 'financiera_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function notes()
    {
        return $this->hasMany(FinSaleNote::class, 'sale_id')->latest();
    }

    public function warranties()
    {
        return $this->hasMany(FinWarranty::class, 'sale_id');
    }

    public function theftReports()
    {
        return $this->hasMany(FinTheftReport::class, 'sale_id');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_ine', 'like', "%{$search}%")
                  ->orWhereHas('device', function ($d) use ($search) {
                      $d->where('imei', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        if ($financieraId = $filters['financiera_id'] ?? false) {
            $query->where('financiera_id', $financieraId);
        }

        if ($branchId = $filters['branch_id'] ?? false) {
            $query->where('branch_id', $branchId);
        }

        if ($sellerId = $filters['seller_id'] ?? false) {
            $query->where('seller_id', $sellerId);
        }

        if ($status = $filters['status'] ?? false) {
            $query->where('status', $status);
        }

        if ($startDate = $filters['start_date'] ?? false) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate = $filters['end_date'] ?? false) {
            $query->whereDate('sale_date', '<=', $endDate);
        }
    }
}
