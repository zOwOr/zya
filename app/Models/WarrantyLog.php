<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Repairs;

class WarrantyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'repairs_id',
        'accion',
        'descripcion',
        'user_id',
        'tipo_garantia',
    ];

    /**
     * Relación con reparación.
     */
    public function repairs()
    {
        return $this->belongsTo(Repairs::class);
    }

    /**
     * Relación con usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}