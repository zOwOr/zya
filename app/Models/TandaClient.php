<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TandaClient extends Model
{
    protected $table = 'tanda_clients';

    protected $fillable = [
        'tanda_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_ine',
        'customer_notes',
        'position',
        'payments',
        'total_paid',
        'is_delivered',
    ];

    protected $casts = [
        'payments' => 'array',
        'total_paid' => 'decimal:2',
        'is_delivered' => 'boolean',
    ];

    public function tanda()
    {
        return $this->belongsTo(Tanda::class, 'tanda_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function paymentLogs()
    {
        return $this->hasMany(TandaPayment::class, 'tanda_client_id')->orderByDesc('paid_at');
    }

    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->customer_name)) {
            return $this->customer_name;
        }
        if ($this->customer) {
            return $this->customer->tit_name ?? 'Participante #' . $this->id;
        }
        return 'Participante #' . $this->id;
    }

    public function getDisplayPhoneAttribute(): string
    {
        if (!empty($this->customer_phone)) {
            return $this->customer_phone;
        }
        if ($this->customer) {
            return $this->customer->tit_phone ?? '-';
        }
        return '-';
    }
}
