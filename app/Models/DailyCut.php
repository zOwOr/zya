<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCut extends Model
{
    protected $fillable = [
        'date',
        'total_income',
        'total_expense',
        'balance',
    ];
}
