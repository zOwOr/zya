<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',    // si quieres agregar dirección
        'phone',      // teléfono sucursal
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
