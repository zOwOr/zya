<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tanda extends Model
{
    protected $table = 'tandas';

    protected $fillable = [
        'branch_id',
        'user_id',
        'description',
        'total_amount',
        'payment_amount',
        'payment_period',
        'start_date',
        'duration',
        'status',
        'slug',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'start_date' => 'date',
        'duration' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Participantes directos de la tanda (desacoplados del módulo general de clientes).
     */
    public function participants()
    {
        return $this->hasMany(TandaClient::class, 'tanda_id')->orderBy('position');
    }

    /**
     * Mantiene compatibilidad legacy si alguna vista antigua invoca $tanda->clients
     */
    public function clients()
    {
        return $this->belongsToMany(Customer::class, 'tanda_clients')
            ->withPivot(['id', 'position', 'payments', 'customer_name', 'customer_phone', 'total_paid'])
            ->withTimestamps();
    }

    public function periods()
    {
        return $this->hasMany(TandaPeriod::class, 'tanda_id')->orderBy('period_number');
    }

    public function payments()
    {
        return $this->hasMany(TandaPayment::class, 'tanda_id')->orderByDesc('paid_at');
    }
}
