<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loans';

    protected $fillable = [
        'loan_code',
        'branch_id',
        'customer_id',
        'user_id',
        'loan_type_id',
        'principal_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'opening_commission',
        'paid_amount',
        'balance_amount',
        'payment_frequency',
        'term_periods',
        'installment_amount',
        'start_date',
        'due_date',
        'type_specific_data',
        'video_path',
        'promissory_note_path',
        'status',
        'closed_manually_at',
        'closed_by',
        'closing_reason',
        'closing_final_paid',
        'notes',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'opening_commission' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'closing_final_paid' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'closed_manually_at' => 'datetime',
        'type_specific_data' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function loanType()
    {
        return $this->belongsTo(LoanType::class, 'loan_type_id');
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id')->orderBy('installment_number');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'loan_id')->orderByDesc('paid_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['activo', 'mora']);
    }

    public function hasOverdueInstallments(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        return $this->installments()
            ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
            ->whereDate('scheduled_date', '<', Carbon::today())
            ->exists();
    }

    public function scopeForBranch($query, $branchId = null)
    {
        if (auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.all_branches'))) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            return $query;
        }

        $userBranch = $branchId ?: (auth()->check() ? auth()->user()->branch_id : null);
        if ($userBranch) {
            $query->where('branch_id', $userBranch);
        }

        return $query;
    }
}

