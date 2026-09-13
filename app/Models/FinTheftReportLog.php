<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinTheftReportLog extends Model
{
    use HasFactory;

    protected $table = 'fin_theft_report_logs';

    protected $fillable = [
        'theft_report_id',
        'user_id',
        'status_change',
        'notes',
    ];

    protected $with = ['user'];

    public function theftReport()
    {
        return $this->belongsTo(FinTheftReport::class, 'theft_report_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
