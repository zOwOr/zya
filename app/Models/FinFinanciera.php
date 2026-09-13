<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinFinanciera extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_financieras';

    protected $fillable = [
        'name',
        'contact_name',
        'contact_phone',
        'contact_email',
        'is_active',
    ];

    public $sortable = [
        'name',
        'contact_name',
        'contact_phone',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(FinSale::class, 'financiera_id');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }
    }
}
