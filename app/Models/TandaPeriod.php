<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TandaPeriod extends Model
{
    protected $table = 'tanda_periods';

    protected $fillable = [
        'tanda_id',
        'period_number',
        'due_date',
    ];

    public function tanda()
    {
        return $this->belongsTo(Tanda::class);
    }
}
