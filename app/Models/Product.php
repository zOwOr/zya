<?php

namespace App\Models;

use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'branch_id',
        'product_name',
        'imei',
        'category_id',
        'supplier_id',
        'product_code',
        'product_garage',
        'product_image',
        'product_store',
        'buying_date',
        'expire_date',
        'buying_price',
        'selling_price',
        'stock_quantity',
        'brand',
        'model',
        'category_status',
        'warranty_time',
        'observations',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'category_id' => 'integer',
        'supplier_id' => 'integer',
        'buying_price' => 'float',
        'selling_price' => 'float',
        'stock_quantity' => 'integer',
    ];

    public $sortable = [
        'product_name',
        'selling_price',
        'stock_quantity',
        'branch_id',
    ];

    protected $guarded = [
        'id',
    ];

    protected $with = [
        'branch',
        'category',
        'supplier',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Determina si el usuario puede ver y operar productos de todas las sucursales.
     */
    public static function userCanAccessAllBranches($user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return method_exists($user, 'can') && $user->can('product.all_branches');
    }

    /**
     * Filtra productos por una sucursal específica (usado por POS / Selectores por sucursal).
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        if ($branchId && $branchId > 0) {
            return $query->where('branch_id', $branchId);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Aplica aislamiento multisucursal según permisos del usuario y filtro opcional.
     */
    public function scopeForUserBranch($query, $user = null, $requestedBranchId = null)
    {
        $user = $user ?? auth()->user();

        if (self::userCanAccessAllBranches($user)) {
            if ($requestedBranchId !== null && $requestedBranchId !== '' && $requestedBranchId !== 'all' && (int) $requestedBranchId > 0) {
                $query->where('branch_id', (int) $requestedBranchId);
            }

            return $query;
        }

        $branchId = (int) ($user->branch_id ?? 0);
        if ($branchId > 0) {
            return $query->where('branch_id', $branchId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeFilter($query, array $filters)
    {
        if ($search = $filters['search'] ?? false) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                    ->orWhere('product_code', 'like', '%' . $search . '%')
                    ->orWhere('imei', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_id', (int) $filters['category_id']);
        }
    }

    /**
     * Genera el siguiente código SKU secuencial disponible (PC0001+) sin límite de 2 dígitos.
     */
    public static function generateUniqueCode(array &$reservedCodes = [], int &$maxPcNumber = 0): string
    {
        if (empty($reservedCodes)) {
            $codes = static::whereNotNull('product_code')->pluck('product_code');
            foreach ($codes as $code) {
                $norm = mb_strtoupper(trim((string) $code), 'UTF-8');
                if ($norm !== '') {
                    $reservedCodes[$norm] = true;
                    if (preg_match('/^PC0*(\d+)$/i', $norm, $m)) {
                        $maxPcNumber = max($maxPcNumber, (int) $m[1]);
                    }
                }
            }
        }

        do {
            $maxPcNumber++;
            $candidate = 'PC' . str_pad((string) $maxPcNumber, 4, '0', STR_PAD_LEFT);
            $norm = mb_strtoupper($candidate, 'UTF-8');
        } while (isset($reservedCodes[$norm]));

        $reservedCodes[$norm] = true;

        return $candidate;
    }

    /**
     * Verifica que un código de producto sea compatible con Code-128 (ASCII imprimible).
     */
    public static function isBarcodeSafe(?string $code): bool
    {
        $trimmed = trim((string) $code);

        return $trimmed !== '' && (bool) preg_match('/^[\x20-\x7E]+$/', $trimmed);
    }

    /**
     * Convierte cualquier código legado a ASCII imprimible seguro para renderizar Code-128.
     */
    public static function toBarcodeSafeText(?string $code): string
    {
        $trimmed = trim((string) $code);
        $ascii = strtr($trimmed, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'Á' => 'A',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'É' => 'E',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'Í' => 'I',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'Ó' => 'O',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ç' => 'c', 'Ç' => 'C',
        ]);

        return preg_replace('/[^\x20-\x7E]/', '', $ascii) ?? '';
    }
}
