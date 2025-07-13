<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
protected $fillable = [
        'type', 'amount', 'description', 'reference', 'module', 'branch_id'
    ];
}
