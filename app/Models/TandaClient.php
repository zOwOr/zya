<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TandaClient extends Pivot
{
    protected $table = 'tanda_clients';

    protected $fillable = [
        'tanda_id',
        'customer_id',
        'position',
    ];
}
