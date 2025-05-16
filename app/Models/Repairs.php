<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repairs extends Model
{
    use HasFactory;
     protected $table = 'repairs'; // Nombre de la tabla (Laravel usa plural por defecto)

    /**
     * Campos que se pueden llenar de forma masiva.
     */
    protected $fillable = [
        'cliente',
        'telefono',
        'marca',
        'modelo',
        'imei' ,
        'problema_reportado',
        'diagnostico',
        'fecha_ingreso',
        'fecha_entrega',
        'foto_recibido_frontal' ,
        'foto_recibido_trasera',
        'foto_entregado_frontal' ,
        'foto_entregado_trasera',
        'precio' ,
        'estado' ,
        ];
}
