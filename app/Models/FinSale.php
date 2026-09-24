<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class FinSale extends Model
{
    use HasFactory, SoftDeletes, Sortable;

    protected $table = 'fin_sales';

    protected $fillable = [
        'sale_code',
        'sale_type',
        'device_id',
        'financiera_id',
        'branch_id',
        'seller_id',
        'seller_name',
        // Datos cliente
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_ine',
        'customer_rfc',
        'customer_address',
        'customer_chip',
        'customer_facebook',
        // Referencias
        'ref1_name', 'ref1_phone',
        'ref2_name', 'ref2_phone',
        'ref3_name', 'ref3_phone',
        // Financiero / Venta
        'price',
        'down_payment',
        'enganche_descuento',
        'credit_amount',
        'abono_semanal',
        'term_months',
        'term_weeks',
        'tag_contrato',
        'warranty_text',
        'payment_method',
        'sale_date',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    public $sortable = [
        'sale_code',
        'customer_name',
        'price',
        'sale_date',
        'status',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'term_months' => 'integer',
        'sale_date' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $with = ['device', 'financiera', 'branch', 'seller'];

    public function device()
    {
        return $this->belongsTo(FinDevice::class, 'device_id')->withTrashed();
    }

    public function financiera()
    {
        return $this->belongsTo(FinFinanciera::class, 'financiera_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function getSellerDisplayNameAttribute()
    {
        if ($this->seller_id && $this->seller) {
            return $this->seller->name;
        }

        return !empty($this->seller_name) ? $this->seller_name : 'N/A';
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function notes()
    {
        return $this->hasMany(FinSaleNote::class, 'sale_id')->latest();
    }

    public function warranties()
    {
        return $this->hasMany(FinWarranty::class, 'sale_id');
    }

    public function theftReports()
    {
        return $this->hasMany(FinTheftReport::class, 'sale_id');
    }

    public function isContado(): bool
    {
        return $this->sale_type === 'contado';
    }

    public function isCredito(): bool
    {
        return $this->sale_type !== 'contado';
    }

    public function getPriceInWordsAttribute(): string
    {
        return static::numberToWords($this->price);
    }

    public static function numberToWords($amount): string
    {
        $amount = (float) $amount;
        $cents = (int) round(($amount - floor($amount)) * 100);
        $intVal = (int) floor($amount);

        $text = static::convertIntegerToSpanish($intVal);
        $centsFormatted = str_pad($cents, 2, '0', STR_PAD_LEFT);
        return strtoupper(trim($text)) . ' PESOS ' . $centsFormatted . '/100 M.N.';
    }

    protected static function convertIntegerToSpanish(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        $units = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
            'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
            'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];

        $tens = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];

        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($number < 30) {
            return $units[$number];
        }

        if ($number < 100) {
            $u = $number % 10;
            $d = (int) floor($number / 10);
            return $tens[$d] . ($u > 0 ? ' y ' . $units[$u] : '');
        }

        if ($number === 100) {
            return 'cien';
        }

        if ($number < 1000) {
            $h = (int) floor($number / 100);
            $rest = $number % 100;
            return $hundreds[$h] . ($rest > 0 ? ' ' . static::convertIntegerToSpanish($rest) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $rest = $number % 1000;
            $thousandsText = $thousands === 1 ? 'mil' : static::convertIntegerToSpanish($thousands) . ' mil';
            return $thousandsText . ($rest > 0 ? ' ' . static::convertIntegerToSpanish($rest) : '');
        }

        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $rest = $number % 1000000;
            $millionsText = $millions === 1 ? 'un millón' : static::convertIntegerToSpanish($millions) . ' millones';
            return $millionsText . ($rest > 0 ? ' ' . static::convertIntegerToSpanish($rest) : '');
        }

        return (string) $number;
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_code', 'like', "%{$search}%")
                  ->orWhere('tag_contrato', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_ine', 'like', "%{$search}%")
                  ->orWhere('customer_rfc', 'like', "%{$search}%")
                  ->orWhere('seller_name', 'like', "%{$search}%")
                  ->orWhereHas('device', function ($d) use ($search) {
                      $d->where('imei', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");
                  });
            });
        }

        if ($saleType = $filters['sale_type'] ?? false) {
            $query->where('sale_type', $saleType);
        }

        if ($financieraId = $filters['financiera_id'] ?? false) {
            $query->where('financiera_id', $financieraId);
        }

        if (auth()->check() && !auth()->user()->can('financieras.inventario.all_branches')) {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif ($branchId = $filters['branch_id'] ?? false) {
            $query->where('branch_id', $branchId);
        }

        if ($sellerId = $filters['seller_id'] ?? false) {
            $query->where('seller_id', $sellerId);
        }

        if ($status = $filters['status'] ?? false) {
            $query->where('status', $status);
        }

        if ($startDate = $filters['start_date'] ?? false) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate = $filters['end_date'] ?? false) {
            $query->whereDate('sale_date', '<=', $endDate);
        }
    }
}
