<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinTheftReport extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_theft_reports';

    protected $fillable = [
        'report_code',
        'device_id',
        'sale_id',
        'branch_id',
        'reported_by',
        'incident_date',
        'police_report_number',
        'description',
        'status',
    ];

    public $sortable = [
        'report_code',
        'incident_date',
        'status',
        'created_at',
    ];

    protected $casts = [
        'incident_date' => 'datetime',
    ];

    protected $with = ['device', 'sale', 'branch', 'reporter'];

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

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function logs()
    {
        return $this->hasMany(FinTheftReportLog::class, 'theft_report_id')->latest();
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('report_code', 'like', "%{$search}%")
                  ->orWhere('police_report_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
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

        if ($status = $filters['status'] ?? false) {
            $query->where('status', $status);
        }

        if (auth()->check() && !auth()->user()->can('financieras.inventario.all_branches')) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif ($branchId = $filters['branch_id'] ?? false) {
            $query->where('branch_id', $branchId);
        }
    }
}
