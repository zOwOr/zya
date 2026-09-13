<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinBrand extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_brands';

    protected $fillable = [
        'name',
        'is_active',
    ];

    public $sortable = [
        'name',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function devices()
    {
        return $this->hasMany(FinDevice::class, 'brand_id');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where('name', 'like', "%{$search}%");
        }
    }
}
