<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    use HasFactory;

    protected $table = 'loan_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_interest_rate',
        'default_opening_fee',
        'requires_collateral',
        'default_frequency',
        'default_periods',
        'required_fields',
        'is_active',
    ];

    protected $casts = [
        'default_interest_rate' => 'decimal:2',
        'default_opening_fee' => 'decimal:2',
        'requires_collateral' => 'boolean',
        'default_periods' => 'integer',
        'required_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_type_id');
    }
}

