<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class ExternalCustomer extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'activo';
    public const STATUS_CONVERTED = 'convertido';
    public const STATUS_INACTIVE = 'inactivo';

    public const TIT_STATUS_OPTIONS = [
        'aprobado' => 'Aprobado',
        'riesgo' => 'Riesgo',
        'moroso' => 'Moroso',
        'rescate' => 'Rescate',
        'desaprobado' => 'Desaprobado',
        'solicitud' => 'En solicitud',
    ];

    protected $table = 'external_customers';

    protected $fillable = [
        'tit_name',
        'tit_email',
        'tit_phone',
        'tit_facebook',
        'tit_status',
        'tit_city',
        'tit_address',
        'alternate_phone',
        'tit_work',
        'position',
        'monthly_income',
        'tit_link_location',
        'notes',
        'branch_id',
        'created_by',
        'status',
        'converted_customer_id',
        'converted_at',
        'converted_by',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    /**
     * Reglas de validación equivalentes a los campos requeridos y opcionales del titular estándar.
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'tit_name' => 'required|string|max:100',
            'tit_email' => 'required|email|max:100',
            'tit_phone' => [
                'required',
                'string',
                'max:15',
                Rule::unique('external_customers', 'tit_phone')
                    ->ignore($ignoreId)
                    ->whereNull('deleted_at'),
            ],
            'tit_status' => 'required|string|max:30|in:' . implode(',', array_keys(self::TIT_STATUS_OPTIONS)),
            'tit_address' => 'required|string|max:999',
            'tit_facebook' => 'required|string|max:255',
            'tit_city' => 'required|string|max:120',
            'alternate_phone' => 'nullable|string|max:15',
            'tit_work' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'tit_link_location' => 'nullable|string|max:999',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:' . implode(',', [self::STATUS_ACTIVE, self::STATUS_CONVERTED, self::STATUS_INACTIVE]),
        ];
    }

    /**
     * Mensajes claros en español para formularios web y peticiones AJAX del POS.
     */
    public static function validationMessages(): array
    {
        return [
            'tit_name.required' => 'El nombre completo del titular es obligatorio.',
            'tit_email.required' => 'El correo electrónico es obligatorio.',
            'tit_email.email' => 'Ingresa un correo electrónico válido.',
            'tit_phone.required' => 'El teléfono del titular es obligatorio.',
            'tit_phone.unique' => 'Ya existe un cliente externo registrado con ese número telefónico.',
            'tit_facebook.required' => 'El perfil o usuario de Facebook es obligatorio.',
            'tit_status.required' => 'El estatus del titular es obligatorio.',
            'tit_city.required' => 'La ciudad es obligatoria.',
            'tit_address.required' => 'La dirección es obligatoria.',
        ];
    }

    /**
     * Atributos mapeados al catálogo principal de clientes internos (`customers`).
     */
    public function toInternalCustomerAttributes(): array
    {
        return [
            'tit_name' => trim((string) $this->tit_name),
            'tit_email' => trim((string) $this->tit_email),
            'tit_phone' => trim((string) $this->tit_phone),
            'tit_status' => $this->tit_status ?: 'aprobado',
            'tit_address' => trim((string) $this->tit_address),
            'tit_facebook' => trim((string) $this->tit_facebook),
            'tit_city' => trim((string) $this->tit_city),
            'alternate_phone' => $this->alternate_phone ? trim((string) $this->alternate_phone) : null,
            'tit_work' => $this->tit_work ? trim((string) $this->tit_work) : null,
            'position' => $this->position ? trim((string) $this->position) : null,
            'monthly_income' => $this->monthly_income,
            'tit_link_location' => $this->tit_link_location ? trim((string) $this->tit_link_location) : null,
        ];
    }

    public function hasRequiredInternalFields(): bool
    {
        foreach (['tit_name', 'tit_email', 'tit_phone', 'tit_status', 'tit_address', 'tit_facebook', 'tit_city'] as $field) {
            if (trim((string) ($this->{$field} ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED || !empty($this->converted_customer_id);
    }

    public function scopeSelectableInPos($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->whereNull('converted_customer_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $term = trim((string) $search);
            $q->where(function ($sub) use ($term) {
                $sub->where('tit_name', 'like', "%{$term}%")
                    ->orWhere('tit_phone', 'like', "%{$term}%")
                    ->orWhere('tit_email', 'like', "%{$term}%")
                    ->orWhere('tit_facebook', 'like', "%{$term}%")
                    ->orWhere('tit_city', 'like', "%{$term}%");
            });
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function posTransactions()
    {
        return $this->hasMany(PosTransaction::class, 'external_customer_id');
    }

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class, 'external_customer_id');
    }

    public function financieraPayments()
    {
        return $this->hasMany(FinSalePayment::class, 'external_customer_id');
    }
}

