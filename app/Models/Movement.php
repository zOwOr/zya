<?php 
// app/Models/Movement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'movement_type', 'amount', 'justification','user_id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function user()
{
    return $this->belongsTo(User::class); // o el modelo que corresponda para el usuario
}

}
