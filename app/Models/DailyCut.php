<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCut extends Model
{
    protected $fillable = [
        'branch_id',
        'date',
        'total_income',
        'total_expense',
        'balance',
    ];

    /**
     * Relación con la sucursal.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
