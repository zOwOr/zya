<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinDeviceTransfer extends Model
{
    use HasFactory;

    protected $table = 'fin_device_transfers';

    protected $fillable = [
        'device_id',
        'from_branch_id',
        'to_branch_id',
        'user_id',
        'notes',
    ];

    protected $with = ['device', 'fromBranch', 'toBranch', 'user'];

    public function device()
    {
        return $this->belongsTo(FinDevice::class, 'device_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
