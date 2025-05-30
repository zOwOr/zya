<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tanda;
use Kyslik\ColumnSortable\Sortable;

class Customer extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'tit_name',
        'tit_email',
        'tit_phone',
        'tit_status',
        'tit_address',
        'tit_photo',
        'tit_photo_ine_f',
        'tit_photo_ine_b',
        'tit_facebook',
        'tit_photo_home',
        'tit_link_location',
        'tit_photo_proof_address',
        'tit_work',
        'tit_city',

        'ref1_name',
        'ref1_phone',
        'ref1_address',

        'ref2_name',
        'ref2_phone',
        'ref2_address',

        'ref3_name',
        'ref3_phone',
        'ref3_address',

        'aval_name',
        'aval_phone',
        'aval_address',
        'aval_photo_ine_f',
        'aval_photo_ine_b',
        'aval_photo_home',






    ];
    public $sortable = [
        'tit_name',
        'tit_email',
        'tit_phone',
        'tit_facebook',
        'tit_city',
    ];

    protected $guarded = [
        'id',
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function ($query, $search) {
            return $query->where('tit_name', 'like', '%' . $search . '%')->orWhere('tit_facebook', 'like', '%' . $search . '%');
        });
    }
    public function tandas()
    {
        return $this->belongsToMany(Tanda::class, 'tanda_clients')
                    ->withPivot('position')
                    ->withTimestamps();
    }
}
