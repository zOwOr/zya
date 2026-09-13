<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinWarrantyLog extends Model
{
    use HasFactory;

    protected $table = 'fin_warranty_logs';

    protected $fillable = [
        'warranty_id',
        'from_stage_id',
        'to_stage_id',
        'user_id',
        'notes',
    ];

    protected $with = ['fromStage', 'toStage', 'user'];

    public function warranty()
    {
        return $this->belongsTo(FinWarranty::class, 'warranty_id');
    }

    public function fromStage()
    {
        return $this->belongsTo(FinWarrantyStage::class, 'from_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(FinWarrantyStage::class, 'to_stage_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
