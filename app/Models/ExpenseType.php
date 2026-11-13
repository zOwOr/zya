<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExpenseType extends Model
{
    protected $fillable = [
        'name',
        'examples',
        'slug',
        'is_other',
        'requires_reason',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_other' => 'boolean',
        'requires_reason' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class, 'expense_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Orden estándar del catálogo: siempre coloca "Otro" al final.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('is_other')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function getFullLabelAttribute(): string
    {
        if (!empty($this->examples)) {
            return "{$this->name} ({$this->examples})";
        }

        return $this->name;
    }

    /**
     * Construye las listas para el selector de egresos de una sucursal:
     * "Más usados" arriba según frecuencia real en esa sucursal y lista completa debajo con "Otro" al final.
     *
     * @return array{mostUsed: Collection, allTypes: Collection}
     */
    public static function forBranchSelector(int $branchId, int $limit = 5): array
    {
        $allTypes = static::active()->ordered()->get();

        $usageCounts = CashFlow::query()
            ->select('expense_type_id', DB::raw('COUNT(*) as total_uses'))
            ->where('type', 'expense')
            ->where('branch_id', $branchId)
            ->whereNotNull('expense_type_id')
            ->groupBy('expense_type_id')
            ->orderByDesc('total_uses')
            ->pluck('total_uses', 'expense_type_id');

        $mostUsed = $allTypes
            ->filter(fn (self $type) => !$type->is_other && ((int) ($usageCounts[$type->id] ?? 0)) > 0)
            ->map(function (self $type) use ($usageCounts) {
                $clone = clone $type;
                $clone->setAttribute('usage_count', (int) ($usageCounts[$type->id] ?? 0));
                return $clone;
            })
            ->sort(function (self $a, self $b) {
                $cmp = ((int) $b->getAttribute('usage_count')) <=> ((int) $a->getAttribute('usage_count'));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return ($a->sort_order <=> $b->sort_order) ?: strcmp($a->name, $b->name);
            })
            ->take($limit)
            ->values();

        return [
            'mostUsed' => $mostUsed,
            'allTypes' => $allTypes,
        ];
    }
}

