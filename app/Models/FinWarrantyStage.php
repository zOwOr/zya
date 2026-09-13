<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinWarrantyStage extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_warranty_stages';

    protected $fillable = [
        'name',
        'order',
        'color',
        'is_final',
    ];

    public $sortable = [
        'name',
        'order',
        'is_final',
        'created_at',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'order' => 'integer',
    ];

    public function warranties()
    {
        return $this->hasMany(FinWarranty::class, 'current_stage_id');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where('name', 'like', "%{$search}%");
        }
    }
}
