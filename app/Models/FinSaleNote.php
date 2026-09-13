<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinSaleNote extends Model
{
    use HasFactory;

    protected $table = 'fin_sale_notes';

    protected $fillable = [
        'sale_id',
        'user_id',
        'note',
    ];

    protected $with = ['user'];

    public function sale()
    {
        return $this->belongsTo(FinSale::class, 'sale_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
