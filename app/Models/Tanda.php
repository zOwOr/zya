<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TandaPeriod;
class Tanda extends Model
{
    protected $table = 'tandas';

    protected $fillable = [
        'description',
        'total_amount',
        'payment_amount',
        'payment_period',
    ];

public function clients()
{
    return $this->belongsToMany(Customer::class ,'tanda_clients')
                ->withPivot(['position', 'payments'])  // agregamos payments
                ->withTimestamps();
}


    public function periods()
    {
        return $this->hasMany(TandaPeriod::class);
    }
}
