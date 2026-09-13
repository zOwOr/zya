<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinWarranty extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_warranties';

    protected $fillable = [
        'warranty_code',
        'device_id',
        'sale_id',
        'branch_id',
        'current_stage_id',
        'created_by',
        'issue_description',
        'resolution_notes',
        'status',
        'opened_at',
        'closed_at',
    ];

    public $sortable = [
        'warranty_code',
        'status',
        'opened_at',
        'created_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $with = ['device', 'sale', 'branch', 'currentStage', 'creator'];

    public function device()
    {
        return $this->belongsTo(FinDevice::class, 'device_id');
    }

    public function sale()
    {
        return $this->belongsTo(FinSale::class, 'sale_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currentStage()
    {
        return $this->belongsTo(FinWarrantyStage::class, 'current_stage_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(FinWarrantyLog::class, 'warranty_id')->latest();
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('warranty_code', 'like', "%{$search}%")
                  ->orWhere('issue_description', 'like', "%{$search}%")
                  ->orWhereHas('device', function ($d) use ($search) {
                      $d->where('imei', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                  })
                  ->orWhereHas('sale', function ($s) use ($search) {
                      $s->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($stageId = $filters['stage_id'] ?? false) {
            $query->where('current_stage_id', $stageId);
        }

        if ($branchId = $filters['branch_id'] ?? false) {
            $query->where('branch_id', $branchId);
        }

        if ($status = $filters['status'] ?? false) {
            $query->where('status', $status);
        }
    }
}
