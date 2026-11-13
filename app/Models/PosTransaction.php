<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'transaction_code',
        'branch_id',
        'user_id',
        'customer_id',
        'external_customer_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'commission_total',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'cash_amount',
        'card_amount',
        'transfer_amount',
        'payment_references',
        'items_payload',
        'order_id',
        'drawer_opened',
        'status',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
        'payment_references' => 'array',
        'items_payload' => 'array',
        'drawer_opened' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function getFolioAttribute(): string
    {
        return (string) ($this->transaction_code ?? ('POS-' . $this->id));
    }

    public function getTransactionDateAttribute()
    {
        return $this->created_at;
    }

    public function getTabLabelAttribute(): string
    {
        return 'POS Central';
    }

    /**
     * Resumen legible de los conceptos del movimiento (soporta claves title, label y description).
     */
    public function getConceptsSummaryAttribute(): string
    {
        return collect($this->items_payload ?? [])
            ->map(function ($item) {
                if (!is_array($item)) {
                    return is_string($item) ? trim($item) : null;
                }

                return $item['title']
                    ?? ($item['label']
                    ?? ($item['description']
                    ?? ($item['section'] ?? null)));
            })
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->implode(', ');
    }

    /**
     * Elimina la referencia al proveedor del gift en subtítulos guardados para no mostrarla en tickets.
     */
    public static function sanitizeGiftSubtitle(?string $subtitle): ?string
    {
        if ($subtitle === null) {
            return null;
        }

        $cleaned = preg_replace('/\bRegalo de proveedor:\s*[^|]*(\|\s*)?/iu', '', trim($subtitle));
        $cleaned = preg_replace('/\bProv(?:eedor)?:\s*[^|]*(\|\s*)?/iu', '', (string) $cleaned);
        $cleaned = trim((string) $cleaned, " \t\n\r\0\x0B|•-");

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Secciones de carrito consideradas conceptos de pago de financiera.
     */
    public static function isFinancieraSection(?string $section): bool
    {
        return in_array($section, ['financiera_abono', 'financiera_externa'], true);
    }

    /**
     * Determina si los conceptos del carrito contienen al menos un pago de financiera.
     */
    public static function containsFinancieraConcept(array $items): bool
    {
        foreach ($items as $item) {
            if (is_array($item) && self::isFinancieraSection($item['section'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function externalCustomer()
    {
        return $this->belongsTo(ExternalCustomer::class, 'external_customer_id')->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function financieraPayments()
    {
        return $this->hasMany(FinSalePayment::class, 'pos_transaction_id');
    }

    public function loanPayments()
    {
        return $this->hasMany(LoanPayment::class, 'pos_transaction_id');
    }

    public function tandaPayments()
    {
        return $this->hasMany(TandaPayment::class, 'pos_transaction_id');
    }

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class, 'pos_transaction_id');
    }
}
