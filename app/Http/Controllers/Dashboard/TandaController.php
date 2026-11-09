<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Tanda;
use App\Models\TandaClient;
use App\Models\TandaPayment;
use App\Models\TandaPeriod;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TandaController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'tandas';

    protected array $permissionMapping = [
        'index' => 'read',
        'create' => 'create',
        'store' => 'create',
        'show' => 'read',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'updatePayments' => 'edit',
        'registerSinglePayment' => 'abonar',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('tandas.all_branches'));
    }

    public function index(Request $request)
    {
        $branchId = $this->canAccessAllBranches()
            ? $request->input('branch_id')
            : auth()->user()->branch_id;

        $tandas = Tanda::with(['participants', 'branch', 'periods'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->get();

        $branches = Branch::orderBy('name')->get();

        return view('tandas.index', compact('tandas', 'branches', 'branchId'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('tandas.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_period' => 'required|in:semana,quincena,mes',
            'duration' => 'required|integer|min:1|max:200',
            'start_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
            'participants' => 'required|array|min:1',
            'participants.*.customer_name' => 'required|string|max:190',
            'participants.*.customer_phone' => 'nullable|string|max:50',
            'participants.*.customer_address' => 'nullable|string|max:255',
            'participants.*.position' => 'required|integer|min:1',
        ]);

        // Validar que no se repitan posiciones
        $positions = [];
        foreach ($request->participants as $participant) {
            $pos = (int) ($participant['position'] ?? 0);
            if ($pos < 1) {
                return back()->withInput()->withErrors(['participants' => 'Cada participante debe tener una posición válida mayor a 0.']);
            }
            if (in_array($pos, $positions, true)) {
                return back()->withInput()->withErrors(['participants' => "La posición #{$pos} está duplicada. Cada participante debe tener un número único."]);
            }
            $positions[] = $pos;
        }

        $branchId = ($this->canAccessAllBranches() && $request->filled('branch_id'))
            ? $request->input('branch_id')
            : (auth()->user()->branch_id ?? Branch::value('id'));

        $tanda = DB::transaction(function () use ($request, $branchId) {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::today();

            $tanda = Tanda::create([
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'description' => $request->description,
                'total_amount' => $request->total_amount,
                'payment_amount' => $request->payment_amount,
                'payment_period' => $request->payment_period,
                'start_date' => $startDate->toDateString(),
                'duration' => (int) $request->duration,
                'status' => 'activa',
                'slug' => Str::slug($request->description) . '-' . strtolower(Str::random(5)),
            ]);

            // Crear participantes con información básica (desacoplados del módulo general de clientes)
            foreach ($request->participants as $p) {
                TandaClient::create([
                    'tanda_id' => $tanda->id,
                    'customer_id' => null,
                    'customer_name' => trim($p['customer_name']),
                    'customer_phone' => !empty($p['customer_phone']) ? trim($p['customer_phone']) : null,
                    'customer_address' => !empty($p['customer_address']) ? trim($p['customer_address']) : null,
                    'customer_notes' => !empty($p['customer_notes']) ? trim($p['customer_notes']) : null,
                    'position' => (int) $p['position'],
                    'payments' => [],
                    'total_paid' => 0,
                ]);
            }

            // Crear los periodos de pago
            for ($i = 1; $i <= (int) $request->duration; $i++) {
                $due = match ($request->payment_period) {
                    'semana' => $startDate->copy()->addWeeks($i),
                    'quincena' => $startDate->copy()->addDays($i * 15),
                    'mes' => $startDate->copy()->addMonths($i),
                };

                $tanda->periods()->create([
                    'period_number' => $i,
                    'due_date' => $due->format('Y-m-d'),
                ]);
            }

            return $tanda;
        });

        return redirect()->route('tandas.show', $tanda->id)->with('success', 'Tanda creada correctamente con sus participantes asignados.');
    }

    public function show(Tanda $tanda)
    {
        if (!$this->canAccessAllBranches() && $tanda->branch_id && $tanda->branch_id !== auth()->user()->branch_id) {
            abort(403, 'No tienes acceso a tandas de otra sucursal.');
        }

        $tanda->load(['participants', 'periods', 'branch', 'payments.user', 'payments.participant']);
        return view('tandas.show', compact('tanda'));
    }

    public function edit(Tanda $tanda)
    {
        if (!$this->canAccessAllBranches() && $tanda->branch_id && $tanda->branch_id !== auth()->user()->branch_id) {
            abort(403, 'No tienes acceso a tandas de otra sucursal.');
        }

        $tanda->load(['participants', 'branch']);
        $branches = Branch::orderBy('name')->get();

        return view('tandas.edit', compact('tanda', 'branches'));
    }

    public function update(Request $request, Tanda $tanda)
    {
        if (!$this->canAccessAllBranches() && $tanda->branch_id && $tanda->branch_id !== auth()->user()->branch_id) {
            abort(403);
        }

        $request->validate([
            'description' => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'remove_participants' => 'nullable|array',
            'new_participants' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $tanda) {
            if ($request->filled('description')) {
                $tanda->update([
                    'description' => $request->input('description'),
                    'status' => $request->input('status', $tanda->status ?? 'activa'),
                ]);
            }

            // 1. Actualizar participantes existentes
            if ($request->has('participants') && is_array($request->participants)) {
                foreach ($request->participants as $participantId => $data) {
                    $participant = TandaClient::where('tanda_id', $tanda->id)->where('id', $participantId)->first();
                    if ($participant) {
                        $participant->update([
                            'customer_name' => $data['customer_name'] ?? $participant->customer_name,
                            'customer_phone' => $data['customer_phone'] ?? $participant->customer_phone,
                            'customer_address' => $data['customer_address'] ?? $participant->customer_address,
                            'position' => isset($data['position']) ? (int) $data['position'] : $participant->position,
                        ]);
                    }
                }
            }

            // 2. Eliminar participantes marcados
            if ($request->has('remove_participants') && is_array($request->remove_participants)) {
                TandaClient::where('tanda_id', $tanda->id)
                    ->whereIn('id', $request->remove_participants)
                    ->delete();
            }

            // 3. Agregar nuevos participantes con datos básicos directos
            if ($request->has('new_participants') && is_array($request->new_participants)) {
                $maxPosition = (int) TandaClient::where('tanda_id', $tanda->id)->max('position');
                foreach ($request->new_participants as $newP) {
                    $name = trim((string) ($newP['customer_name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $maxPosition++;
                    $pos = !empty($newP['position']) ? (int) $newP['position'] : $maxPosition;

                    TandaClient::create([
                        'tanda_id' => $tanda->id,
                        'customer_id' => null,
                        'customer_name' => $name,
                        'customer_phone' => !empty($newP['customer_phone']) ? trim($newP['customer_phone']) : null,
                        'customer_address' => !empty($newP['customer_address']) ? trim($newP['customer_address']) : null,
                        'position' => $pos,
                        'payments' => [],
                        'total_paid' => 0,
                    ]);
                }
            }
        });

        return redirect()->route('tandas.show', $tanda->id)
            ->with('success', 'Participantes y datos de la tanda actualizados correctamente.');
    }

    public function updatePayments(Request $request, Tanda $tanda)
    {
        $payments = $request->input('payments', []);
        $recordCashFlow = $request->boolean('record_cashflow', false);
        $paymentMethod = $request->input('payment_method', 'efectivo');
        $branchId = auth()->user()->branch_id ?? $tanda->branch_id ?? 1;

        DB::transaction(function () use ($payments, $tanda, $recordCashFlow, $paymentMethod, $branchId) {
            foreach ($payments as $payment) {
                $participantId = $payment['participant_id'] ?? $payment['client_id'] ?? null;
                $period = (int) ($payment['period'] ?? 1);
                $rawAmount = $payment['amount'] ?? '';

                $participant = TandaClient::where('tanda_id', $tanda->id)
                    ->where(function ($q) use ($participantId) {
                        $q->where('id', $participantId)->orWhere('customer_id', $participantId);
                    })
                    ->first();

                if (!$participant) {
                    continue;
                }

                $existingPayments = is_array($participant->payments)
                    ? $participant->payments
                    : (json_decode($participant->payments ?? '{}', true) ?: []);

                $previousVal = isset($existingPayments[$period]) && $existingPayments[$period] !== ''
                    ? (float) $existingPayments[$period]
                    : 0;

                if ($rawAmount === '' || $rawAmount === null) {
                    unset($existingPayments[$period]);
                    $newVal = 0;
                } else {
                    $newVal = round((float) $rawAmount, 2);
                    $existingPayments[$period] = $newVal;
                }

                $totalPaid = 0;
                foreach ($existingPayments as $val) {
                    if (is_numeric($val)) {
                        $totalPaid += (float) $val;
                    }
                }

                $participant->payments = $existingPayments;
                $participant->total_paid = round($totalPaid, 2);
                $participant->save();

                $diff = round($newVal - $previousVal, 2);
                if ($recordCashFlow && $diff > 0) {
                    $receiptCode = 'TND-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                    TandaPayment::create([
                        'tanda_id' => $tanda->id,
                        'tanda_client_id' => $participant->id,
                        'branch_id' => $branchId,
                        'user_id' => auth()->id(),
                        'receipt_code' => $receiptCode,
                        'period_number' => $period,
                        'amount' => $diff,
                        'payment_method' => $paymentMethod,
                        'paid_at' => now(),
                        'notes' => "Aportación Periodo #{$period} - {$tanda->description}",
                    ]);

                    CashFlowService::register(
                        'income',
                        $diff,
                        "Pago Tanda ({$tanda->description}) - Periodo #{$period} - {$participant->display_name}",
                        $receiptCode,
                        'Tandas',
                        $branchId,
                        [
                            'customer_name' => $participant->display_name,
                            'payment_method' => $paymentMethod,
                        ]
                    );
                }
            }
        });

        return response()->json(['message' => 'Pagos de la tanda actualizados correctamente.']);
    }

    public function destroy(Tanda $tanda)
    {
        if (!$this->canAccessAllBranches() && $tanda->branch_id && $tanda->branch_id !== auth()->user()->branch_id) {
            abort(403);
        }

        DB::transaction(function () use ($tanda) {
            TandaClient::where('tanda_id', $tanda->id)->delete();
            $tanda->periods()->delete();
            $tanda->delete();
        });

        return redirect()->route('tandas.index')->with('success', 'Tanda eliminada correctamente.');
    }
}
