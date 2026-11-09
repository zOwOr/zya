<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',    // si quieres agregar dirección
        'phone',      // teléfono sucursal
        'auto_cut_enabled',
        'auto_cut_time',
    ];

    protected $casts = [
        'auto_cut_enabled' => 'boolean',
    ];

    // Relación con cortes diarios
    public function dailyCuts()
    {
        return $this->hasMany(DailyCut::class);
    }

    // Relación con usuarios (si aplica)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
