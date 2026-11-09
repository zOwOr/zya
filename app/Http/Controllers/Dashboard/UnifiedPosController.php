<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSaleNote;
use App\Models\FinSalePayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Tanda;
use App\Models\TandaClient;
use App\Models\TandaPayment;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnifiedPosController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'cobros';

    protected array $permissionMapping = [
        'index' => 'read',
        'lookupFinanciera' => 'read',
        'lookupCustomerLoans' => 'read',
        'lookupTanda' => 'read',
        'lookupProducts' => 'read',
        'checkout' => 'create',
        'printReceipt' => 'read',
        'openCashDrawer' => 'open_drawer',
        'registerDirectFinancieraAbono' => 'financieras',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.all_branches'));
    }

    public function index(Request $request)
    {
        $branchId = $this->canAccessAllBranches() && $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : (auth()->user()->branch_id ?? Branch::value('id'));

        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get();
        if ($financieras->isEmpty()) {
            $financieras = FinFinanciera::orderBy('name')->get();
        }

        $loanTypes = LoanType::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('tit_name')->get(['id', 'tit_name', 'tit_phone', 'tit_address', 'tit_status']);
        $tandas = Tanda::with(['participants', 'periods'])
            ->when(!$this->canAccessAllBranches() && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->get();

        $products = Product::orderBy('product_name')
            ->take(100)
            ->get(['id', 'product_name', 'product_code', 'selling_price', 'stock_quantity', 'brand', 'model', 'imei', 'category_status', 'warranty_time']);

        $branches = Branch::orderBy('name')->get();

        // Movimientos recientes del día en curso
        $recentTransactions = PosTransaction::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('central-pos.index', compact(
            'financieras',
            'loanTypes',
            'customers',
            'tandas',
            'products',
            'branches',
            'branchId',
            'recentTransactions'
        ));
    }

    /**
     * Buscar contrato de Financiera por selección de catálogo + (Device_id / IMEI / TAG).
     */
    public function lookupFinanciera(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $financieraId = $request->input('financiera_id');

        if ($query === '' && !$financieraId) {
            return response()->json(['results' => []]);
        }

        $sales = FinSale::with(['device.brand', 'financiera', 'branch', 'payments'])
            ->where('status', '!=', 'cancelada')
            ->when($financieraId, fn($q) => $q->where('financiera_id', $financieraId))
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('tag_contrato', 'like', "%{$query}%")
                        ->orWhere('device_id', $query)
                        ->orWhere('sale_code', 'like', "%{$query}%")
                        ->orWhere('customer_name', 'like', "%{$query}%")
                        ->orWhere('customer_phone', 'like', "%{$query}%")
                        ->orWhereHas('device', function ($d) use ($query) {
                            $d->where('imei', 'like', "%{$query}%")
                              ->orWhere('id', $query)
                              ->orWhere('model', 'like', "%{$query}%");
                        });
                });
            })
            ->orderByDesc('sale_date')
            ->limit(15)
            ->get();

        $results = $sales->map(function (FinSale $sale) {
            $creditAmount = (float) ($sale->credit_amount ?: max(0, (float) $sale->price - (float) $sale->down_payment));
            $totalAbonos = (float) $sale->total_abonos_paid;
            $remaining = max(0, round($creditAmount - $totalAbonos, 2));

            return [
                'sale_id' => $sale->id,
                'sale_code' => $sale->sale_code,
                'device_id' => $sale->device_id,
                'imei' => $sale->device?->imei ?? 'N/A',
                'device_model' => trim(($sale->device?->brand?->name ?? '') . ' ' . ($sale->device?->model ?? 'Equipo')),
                'tag_contrato' => $sale->tag_contrato ?: 'SIN-TAG',
                'financiera_id' => $sale->financiera_id,
                'financiera_name' => $sale->financiera?->name ?? 'Contado / General',
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'price' => (float) $sale->price,
                'down_payment' => (float) $sale->down_payment,
                'credit_amount' => $creditAmount,
                'abono_semanal' => (float) ($sale->abono_semanal ?: 0),
                'total_abonos_paid' => $totalAbonos,
                'abonos_count' => (int) $sale->abonos_count,
                'remaining_balance' => $remaining,
                'next_installment' => ((int) $sale->abonos_count) + 1,
                'branch_name' => $sale->branch?->name ?? 'General',
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Consultar préstamos abiertos y tabla de pagos pendientes de un cliente.
     */
    public function lookupCustomerLoans(Request $request)
    {
        $customerId = $request->input('customer_id');
        if (!$customerId) {
            return response()->json(['open_loans' => [], 'has_open_loans' => false]);
        }

        $openLoans = Loan::with(['loanType', 'installments' => function ($q) {
                $q->orderBy('installment_number');
            }])
            ->where('customer_id', $customerId)
            ->whereIn('status', ['activo', 'mora'])
            ->orderByDesc('created_at')
            ->get();

        $formatted = $openLoans->map(function (Loan $loan) {
            $pendingInstallments = $loan->installments
                ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                ->values()
                ->map(fn($inst) => [
                    'id' => $inst->id,
                    'installment_number' => $inst->installment_number,
                    'scheduled_date' => $inst->scheduled_date ? $inst->scheduled_date->format('Y-m-d') : null,
                    'scheduled_date_formatted' => $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-',
                    'scheduled_amount' => (float) $inst->scheduled_amount,
                    'paid_amount' => (float) $inst->paid_amount,
                    'remaining_amount' => $inst->remaining_amount,
                    'is_overdue' => $inst->is_overdue,
                    'status' => $inst->status,
                ]);

            return [
                'id' => $loan->id,
                'loan_code' => $loan->loan_code,
                'type_name' => $loan->loanType?->name ?? 'General',
                'principal_amount' => (float) $loan->principal_amount,
                'total_amount' => (float) $loan->total_amount,
                'paid_amount' => (float) $loan->paid_amount,
                'balance_amount' => (float) $loan->balance_amount,
                'installment_amount' => (float) $loan->installment_amount,
                'status' => $loan->status,
                'pending_installments' => $pendingInstallments,
                'all_installments' => $loan->installments->map(fn($inst) => [
                    'id' => $inst->id,
                    'installment_number' => $inst->installment_number,
                    'scheduled_date_formatted' => $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-',
                    'paid_date_formatted' => $inst->paid_date ? $inst->paid_date->format('d/m/Y H:i') : '-',
                    'scheduled_amount' => (float) $inst->scheduled_amount,
                    'paid_amount' => (float) $inst->paid_amount,
                    'remaining_amount' => $inst->remaining_amount,
                    'status' => $inst->status,
                    'is_overdue' => $inst->is_overdue,
                ]),
            ];
        });

        return response()->json([
            'has_open_loans' => $formatted->isNotEmpty(),
            'open_loans_count' => $formatted->count(),
            'open_loans' => $formatted,
        ]);
    }

    /**
     * Buscar productos de inventario por código de barras, IMEI o nombre.
     */
    public function lookupProducts(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $products = Product::when($q !== '', function ($query) use ($q) {
                $query->where('product_name', 'like', "%{$q}%")
                    ->orWhere('product_code', 'like', "%{$q}%")
                    ->orWhere('imei', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%");
            })
            ->orderBy('product_name')
            ->limit(25)
            ->get(['id', 'product_name', 'product_code', 'selling_price', 'stock_quantity', 'brand', 'model', 'imei', 'category_status', 'warranty_time']);

        return response()->json(['products' => $products]);
    }

    /**
     * Procesar el cierre de un registro de pago unificado (interactuando con las 4 secciones:
     * Financieras, Préstamos, Tandas y Venta de Productos con stock / dinámicos sin stock).
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:190',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'cash_received' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_references' => 'nullable|array',
            'open_drawer' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
        ]);

        $branchId = ($this->canAccessAllBranches() && !empty($validated['branch_id']))
            ? (int) $validated['branch_id']
            : (auth()->user()->branch_id ?? Branch::value('id'));

        $items = $validated['items'];

        // Evitar duplicar el abono del mismo período de pago de préstamo en la misma transacción
        $loanInstallmentIds = [];
        foreach ($items as $item) {
            if (($item['section'] ?? '') === 'prestamo_abono' && !empty($item['installment_id'])) {
                $instId = (int) $item['installment_id'];
                if (in_array($instId, $loanInstallmentIds, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No es posible abonar dos veces el mismo período de pago en la misma cuenta. Si deseas abonar un monto diferente, ajusta el importe de la cuota.',
                    ], 422);
                }
                $loanInstallmentIds[] = $instId;
            }
        }

        // Calcular totales del movimiento
        $subtotal = 0;
        $commissionTotal = 0;
        $resolvedCustomerName = trim((string) ($validated['customer_name'] ?? ''));
        $resolvedCustomerId = $validated['customer_id'] ?? null;

        if ($resolvedCustomerId && $resolvedCustomerName === '') {
            $cust = Customer::find($resolvedCustomerId);
            if ($cust) {
                $resolvedCustomerName = $cust->tit_name;
            }
        }

        foreach ($items as $item) {
            $section = $item['section'] ?? '';
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unitAmount = round((float) ($item['amount'] ?? 0), 2);
            $comm = round((float) ($item['commission'] ?? 0), 2);

            if ($section === 'prestamo_apertura') {
                // En apertura de préstamo, el capital prestado NO entra ni sale de caja; SOLO se cobra la comisión por apertura
                $commissionTotal += $comm;
            } else {
                $subtotal += ($unitAmount * $qty);
                $commissionTotal += $comm;
            }

            if ($resolvedCustomerName === '' && !empty($item['customer_name'])) {
                $resolvedCustomerName = $item['customer_name'];
            }
        }

        if ($resolvedCustomerName === '') {
            $resolvedCustomerName = 'Cliente Mostrador';
        }

        $totalAmount = round($subtotal + $commissionTotal, 2);
        $method = $validated['payment_method'];

        $hasCustomPaidAmount = isset($validated['paid_amount']) && $validated['paid_amount'] !== null && $validated['paid_amount'] !== '';

        if ($method === 'efectivo') {
            $cashAmount = isset($validated['cash_amount']) && $validated['cash_amount'] !== null && $validated['cash_amount'] !== '' 
                ? round((float) $validated['cash_amount'], 2) 
                : ($hasCustomPaidAmount ? round((float) $validated['paid_amount'], 2) : $totalAmount);
            $cardAmount = 0;
            $transferAmount = 0;
            $paidToday = min($totalAmount, $cashAmount);
        } elseif ($method === 'tarjeta') {
            $cardAmount = isset($validated['card_amount']) && $validated['card_amount'] !== null && $validated['card_amount'] !== '' 
                ? round((float) $validated['card_amount'], 2) 
                : ($hasCustomPaidAmount ? round((float) $validated['paid_amount'], 2) : $totalAmount);
            $cashAmount = 0;
            $transferAmount = 0;
            $paidToday = min($totalAmount, $cardAmount);
        } elseif ($method === 'transferencia') {
            $transferAmount = isset($validated['transfer_amount']) && $validated['transfer_amount'] !== null && $validated['transfer_amount'] !== '' 
                ? round((float) $validated['transfer_amount'], 2) 
                : ($hasCustomPaidAmount ? round((float) $validated['paid_amount'], 2) : $totalAmount);
            $cashAmount = 0;
            $cardAmount = 0;
            $paidToday = min($totalAmount, $transferAmount);
        } else { // mixto
            $cashAmount = round((float) ($validated['cash_amount'] ?? 0), 2);
            $cardAmount = round((float) ($validated['card_amount'] ?? 0), 2);
            $transferAmount = round((float) ($validated['transfer_amount'] ?? 0), 2);
            $sumMix = round($cashAmount + $cardAmount + $transferAmount, 2);
            $paidToday = $hasCustomPaidAmount ? round((float) $validated['paid_amount'], 2) : min($totalAmount, $sumMix);

            if (abs($sumMix - $paidToday) > 0.05) {
                $faltante = round($paidToday - $sumMix, 2);
                if ($faltante > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'En Pago Mixto, falta asignar $' . number_format($faltante, 2) . ' para cubrir el monto a cobrar ($' . number_format($paidToday, 2) . ').'
                    ], 422);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'En Pago Mixto, la suma asignada excede el monto a cobrar por $' . number_format(abs($faltante), 2) . '.'
                    ], 422);
                }
            }
        }

        $dueAmount = max(0, round($totalAmount - $paidToday, 2));

        if ($dueAmount > 0) {
            if ($resolvedCustomerName === '' || $resolvedCustomerName === 'Cliente Mostrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Para dejar una Nota con Saldo Pendiente (el cliente queda debiendo $' . number_format($dueAmount, 2) . '), es obligatorio ingresar o seleccionar al Cliente.',
                ], 422);
            }
        }

        $cashReceived = (float) ($validated['cash_received'] ?? $cashAmount);
        $changeAmount = max(0, round($cashReceived - $cashAmount, 2));

        try {
            $transaction = DB::transaction(function () use (
                $validated,
                $branchId,
                $items,
                $subtotal,
                $commissionTotal,
                $totalAmount,
                $paidToday,
                $dueAmount,
                $method,
                $cashAmount,
                $cardAmount,
                $transferAmount,
                $changeAmount,
                $resolvedCustomerId,
                $resolvedCustomerName
            ) {
                // 1. Validar stock real primero para productos de inventario
                $inventoryItems = [];
                $dynamicItems = [];

                foreach ($items as $item) {
                    $section = $item['section'] ?? '';
                    if ($section === 'producto_inventario') {
                        $productId = (int) ($item['product_id'] ?? 0);
                        $qty = max(1, (int) ($item['quantity'] ?? 1));
                        $product = Product::where('id', $productId)->lockForUpdate()->first();
                        if (!$product) {
                            throw new \RuntimeException("El producto con ID #{$productId} no existe en el inventario.");
                        }
                        if ($product->stock_quantity < $qty) {
                            throw new \RuntimeException("Stock insuficiente para '{$product->product_name}' (Disponible: {$product->stock_quantity}, Solicitado: {$qty}).");
                        }
                        $inventoryItems[] = ['product' => $product, 'item' => $item, 'qty' => $qty];
                    } elseif ($section === 'producto_dinamico') {
                        $dynamicItems[] = $item;
                    }
                }

                $txCode = 'MOV-' . date('Ymd') . '-' . strtoupper(Str::random(5));
                $txNotes = $dueAmount > 0
                    ? ("Nota con saldo pendiente. Total: $" . number_format($totalAmount, 2) . " | Abonó: $" . number_format($paidToday, 2) . " | Resta: $" . number_format($dueAmount, 2) . (!empty($validated['notes']) ? " - " . $validated['notes'] : ""))
                    : ($validated['notes'] ?? null);

                $posTx = PosTransaction::create([
                    'transaction_code' => $txCode,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'customer_id' => $resolvedCustomerId,
                    'customer_name' => $resolvedCustomerName,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'subtotal' => $subtotal,
                    'commission_total' => $commissionTotal,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidToday + $changeAmount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $method,
                    'cash_amount' => $cashAmount,
                    'card_amount' => $cardAmount,
                    'transfer_amount' => $transferAmount,
                    'payment_references' => $validated['payment_references'] ?? [],
                    'items_payload' => $items,
                    'drawer_opened' => (bool) ($validated['open_drawer'] ?? true),
                    'status' => 'completado',
                    'notes' => $txNotes,
                ]);

                // 2. Si hay productos (de inventario o dinámicos), crear registro en Order y OrderDetails
                if (!empty($inventoryItems) || !empty($dynamicItems)) {
                    $productSubtotal = 0;
                    $totalQty = 0;

                    foreach ($inventoryItems as $inv) {
                        $price = round((float) ($inv['item']['amount'] ?? $inv['product']->selling_price), 2);
                        $productSubtotal += ($price * $inv['qty']);
                        $totalQty += $inv['qty'];
                    }
                    foreach ($dynamicItems as $dyn) {
                        $qty = max(1, (int) ($dyn['quantity'] ?? 1));
                        $price = round((float) ($dyn['amount'] ?? 0), 2);
                        $productSubtotal += ($price * $qty);
                        $totalQty += $qty;
                    }

                    $invoiceNo = IdGenerator::generate([
                        'table' => 'orders',
                        'field' => 'invoice_no',
                        'length' => 10,
                        'prefix' => 'INV-'
                    ]);

                    $orderCustomerId = $resolvedCustomerId ?: (Customer::value('id') ?? 1);
                    $orderPay = ($totalAmount > 0) ? round(($productSubtotal / $totalAmount) * $paidToday, 2) : $productSubtotal;
                    $orderDue = max(0, round($productSubtotal - $orderPay, 2));
                    $orderStatus = ($orderDue <= 0) ? 'complete' : 'pending';
                    $paymentStatusText = ($orderDue <= 0) ? $method : ($orderPay > 0 ? 'Parcial (' . strtoupper($method) . ')' : 'Pendiente');

                    $orderData = [
                        'customer_id' => $orderCustomerId,
                        'order_date' => Carbon::now()->format('Y-m-d'),
                        'order_status' => $orderStatus,
                        'total_products' => $totalQty,
                        'sub_total' => $productSubtotal,
                        'vat' => 0,
                        'invoice_no' => $invoiceNo,
                        'total' => $productSubtotal,
                        'payment_status' => $paymentStatusText,
                        'pay' => $orderPay,
                        'due' => $orderDue,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                    if (Schema::hasColumn('orders', 'branch_id')) {
                        $orderData['branch_id'] = $branchId;
                    }
                    if (Schema::hasColumn('orders', 'user_id')) {
                        $orderData['user_id'] = auth()->id();
                    }

                    $orderId = Order::insertGetId($orderData);

                    $posTx->update(['order_id' => $orderId]);

                    // Guardar detalles de productos de inventario y descontar stock real
                    foreach ($inventoryItems as $inv) {
                        /** @var Product $prod */
                        $prod = $inv['product'];
                        $qty = $inv['qty'];
                        $price = round((float) ($inv['item']['amount'] ?? $prod->selling_price), 2);

                        OrderDetails::create([
                            'order_id' => $orderId,
                            'product_id' => $prod->id,
                            'quantity' => $qty,
                            'unitcost' => $price,
                            'total' => round($price * $qty, 2),
                            'is_dynamic' => false,
                        ]);

                        $prod->stock_quantity = max(0, (int) $prod->stock_quantity - $qty);
                        $prod->save();
                    }

                    // Guardar detalles de productos dinámicos SIN impactar el stock del módulo de inventario
                    foreach ($dynamicItems as $dyn) {
                        $qty = max(1, (int) ($dyn['quantity'] ?? 1));
                        $price = round((float) ($dyn['amount'] ?? 0), 2);
                        $dynCode = 'DYN-' . time() . '-' . rand(100, 999);

                        OrderDetails::create([
                            'order_id' => $orderId,
                            'product_id' => null,
                            'quantity' => $qty,
                            'unitcost' => $price,
                            'total' => round($price * $qty, 2),
                            'is_dynamic' => true,
                            'dynamic_product_name' => $dyn['name'] ?? 'Producto Libre',
                            'dynamic_brand' => $dyn['brand'] ?? null,
                            'dynamic_model' => $dyn['model'] ?? null,
                            'dynamic_imei' => $dyn['imei'] ?? null,
                            'dynamic_category_status' => $dyn['category_status'] ?? 'Nuevo',
                            'dynamic_warranty_time' => $dyn['warranty_time'] ?? null,
                            'dynamic_observations' => $dyn['observations'] ?? null,
                            'dynamic_product_code' => $dynCode,
                        ]);
                    }

                    // Registrar en PaymentHistory para compatibilidad con reportes de Caja/Banco
                    $methodMap = [
                        'efectivo' => 'HandCash',
                        'transferencia' => 'Cheque',
                        'tarjeta' => 'Due',
                    ];
                    $hasNoteCol = Schema::hasColumn('payment_histories', 'note');

                    if ($method === 'mixto') {
                        if ($cashAmount > 0) {
                            $hData = [
                                'order_id' => $orderId,
                                'amount' => $cashAmount,
                                'method' => 'HandCash',
                                'user_id' => auth()->id(),
                                'branch_id' => $branchId,
                            ];
                            if ($hasNoteCol) $hData['note'] = "Pago mixto (Efectivo) - {$resolvedCustomerName}";
                            PaymentHistory::create($hData);
                        }
                        if ($cardAmount > 0) {
                            $hData = [
                                'order_id' => $orderId,
                                'amount' => $cardAmount,
                                'method' => 'Due',
                                'user_id' => auth()->id(),
                                'branch_id' => $branchId,
                            ];
                            if ($hasNoteCol) $hData['note'] = "Pago mixto (Tarjeta) - {$resolvedCustomerName}";
                            PaymentHistory::create($hData);
                        }
                        if ($transferAmount > 0) {
                            $hData = [
                                'order_id' => $orderId,
                                'amount' => $transferAmount,
                                'method' => 'Cheque',
                                'user_id' => auth()->id(),
                                'branch_id' => $branchId,
                            ];
                            if ($hasNoteCol) $hData['note'] = "Pago mixto (Transferencia) - {$resolvedCustomerName}";
                            PaymentHistory::create($hData);
                        }
                    } else {
                        $hData = [
                            'order_id' => $orderId,
                            'amount' => $productSubtotal,
                            'method' => $methodMap[$method] ?? 'HandCash',
                            'user_id' => auth()->id(),
                            'branch_id' => $branchId,
                        ];
                        if ($hasNoteCol) $hData['note'] = "Venta POS Centralizado - {$resolvedCustomerName}";
                        PaymentHistory::create($hData);
                    }
                }

                // 3. Procesar cada ítem de Financieras, Préstamos y Tandas
                foreach ($items as $item) {
                    $section = $item['section'] ?? '';

                    // A) ABONO DE FINANCIERAS POR EQUIPO (Device_id / TAG)
                    if ($section === 'financiera_abono') {
                        $saleId = (int) ($item['sale_id'] ?? 0);
                        $sale = FinSale::where('id', $saleId)->lockForUpdate()->firstOrFail();
                        $abonoAmount = round((float) ($item['amount'] ?? 0), 2);
                        $commAmount = round((float) ($item['commission'] ?? 0), 2);
                        $totalFin = round($abonoAmount + $commAmount, 2);
                        $installmentNo = ((int) $sale->abonos_count) + 1;
                        $receiptCode = 'ABFIN-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                        FinSalePayment::create([
                            'fin_sale_id' => $sale->id,
                            'device_id' => $sale->device_id,
                            'financiera_id' => $sale->financiera_id,
                            'branch_id' => $branchId,
                            'user_id' => auth()->id(),
                            'pos_transaction_id' => $posTx->id,
                            'receipt_code' => $receiptCode,
                            'tag_contrato' => $sale->tag_contrato,
                            'imei' => $sale->device?->imei,
                            'customer_name' => $sale->customer_name,
                            'installment_number' => $installmentNo,
                            'amount' => $abonoAmount,
                            'commission_amount' => $commAmount,
                            'total_amount' => $totalFin,
                            'payment_method' => $method,
                            'payment_breakdown' => [
                                'efectivo' => $cashAmount,
                                'tarjeta' => $cardAmount,
                                'transferencia' => $transferAmount,
                            ],
                            'payment_date' => now(),
                            'notes' => $item['notes'] ?? "Abono #{$installmentNo} registrado desde Módulo Centralizado de Cobros",
                        ]);

                        // Impacto directo en la sección de Clientes/Ventas del módulo de Financieras
                        $sale->total_abonos_paid = round((float) $sale->total_abonos_paid + $abonoAmount, 2);
                        $sale->abonos_count = $installmentNo;
                        $sale->last_abono_at = now();
                        $sale->save();

                        FinSaleNote::create([
                            'sale_id' => $sale->id,
                            'user_id' => auth()->id(),
                            'note' => "Abono #{$installmentNo} registrado ({$receiptCode}): Abono $" . number_format($abonoAmount, 2)
                                . ($commAmount > 0 ? " + Comisión $" . number_format($commAmount, 2) : '')
                                . " | Método: " . strtoupper($method),
                        ]);
                    }

                    // B) ABONO A PRÉSTAMO EXISTENTE
                    elseif ($section === 'prestamo_abono') {
                        $loanId = (int) ($item['loan_id'] ?? 0);
                        $loan = Loan::where('id', $loanId)->lockForUpdate()->firstOrFail();
                        $abonoAmount = round((float) ($item['amount'] ?? 0), 2);
                        $installmentId = !empty($item['installment_id']) ? (int) $item['installment_id'] : null;

                        $remainingToApply = $abonoAmount;
                        $primaryInst = null;
                        $scheduledDate = null;

                        if ($installmentId) {
                            $inst = LoanInstallment::where('loan_id', $loan->id)
                                ->where('id', $installmentId)
                                ->lockForUpdate()
                                ->first();
                            if ($inst && $inst->status !== 'pagado') {
                                $primaryInst = $inst;
                                $scheduledDate = $inst->scheduled_date;
                                $needed = max(0, (float) $inst->scheduled_amount - (float) $inst->paid_amount);
                                $applied = min($remainingToApply, $needed);
                                $inst->paid_amount = round((float) $inst->paid_amount + $applied, 2);
                                $inst->paid_date = now();
                                $inst->status = ($inst->paid_amount >= $inst->scheduled_amount) ? 'pagado' : 'parcial';
                                $inst->save();
                                $remainingToApply = round($remainingToApply - $applied, 2);
                            }
                        }

                        if ($remainingToApply > 0) {
                            $pendingList = LoanInstallment::where('loan_id', $loan->id)
                                ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                                ->orderBy('installment_number')
                                ->lockForUpdate()
                                ->get();
                            foreach ($pendingList as $inst) {
                                if ($remainingToApply <= 0) {
                                    break;
                                }
                                if (!$primaryInst) {
                                    $primaryInst = $inst;
                                    $scheduledDate = $inst->scheduled_date;
                                }
                                $needed = max(0, (float) $inst->scheduled_amount - (float) $inst->paid_amount);
                                if ($needed <= 0) {
                                    continue;
                                }
                                $applied = min($remainingToApply, $needed);
                                $inst->paid_amount = round((float) $inst->paid_amount + $applied, 2);
                                $inst->paid_date = now();
                                $inst->status = ($inst->paid_amount >= $inst->scheduled_amount) ? 'pagado' : 'parcial';
                                $inst->save();
                                $remainingToApply = round($remainingToApply - $applied, 2);
                            }
                        }

                        $loan->paid_amount = round((float) $loan->paid_amount + $abonoAmount, 2);
                        $loan->balance_amount = max(0, round((float) $loan->total_amount - (float) $loan->paid_amount, 2));
                        if ($loan->balance_amount <= 0) {
                            $loan->status = 'liquidado';
                        }
                        $loan->save();

                        LoanPayment::create([
                            'loan_id' => $loan->id,
                            'installment_id' => $primaryInst?->id,
                            'branch_id' => $branchId,
                            'user_id' => auth()->id(),
                            'pos_transaction_id' => $posTx->id,
                            'payment_code' => 'ABPR-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                            'payment_type' => 'abono',
                            'amount' => $abonoAmount,
                            'payment_method' => $method,
                            'payment_breakdown' => [
                                'efectivo' => $cashAmount,
                                'tarjeta' => $cardAmount,
                                'transferencia' => $transferAmount,
                            ],
                            'scheduled_date' => $scheduledDate,
                            'paid_at' => now(),
                            'notes' => $item['notes'] ?? 'Abono registrado en Módulo Centralizado',
                        ]);
                    }

                    // C) CREACIÓN DE NUEVO PRÉSTAMO DESDE EL MÓDULO CENTRALIZADO
                    // (Sin impacto de desembolso en caja; solo entra la comisión por apertura)
                    elseif ($section === 'prestamo_apertura') {
                        $loanCustomerId = (int) ($item['customer_id'] ?? $resolvedCustomerId);
                        $loanTypeId = (int) ($item['loan_type_id'] ?? LoanType::value('id'));
                        $principal = round((float) ($item['principal_amount'] ?? 0), 2);
                        $rate = round((float) ($item['interest_rate'] ?? 15), 2);
                        $interestAmt = round($principal * ($rate / 100), 2);
                        $totalLoan = round($principal + $interestAmt, 2);
                        $periods = max(1, (int) ($item['term_periods'] ?? 4));
                        $freq = $item['payment_frequency'] ?? 'semanal';
                        $quota = round($totalLoan / $periods, 2);
                        $openingComm = round((float) ($item['commission'] ?? 0), 2);
                        $startDate = Carbon::today();
                        $dueDate = match ($freq) {
                            'semanal' => $startDate->copy()->addWeeks($periods),
                            'quincenal' => $startDate->copy()->addDays($periods * 15),
                            'mensual' => $startDate->copy()->addMonths($periods),
                            default => $startDate->copy()->addWeeks($periods),
                        };

                        $newLoan = Loan::create([
                            'loan_code' => 'PREST-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                            'branch_id' => $branchId,
                            'customer_id' => $loanCustomerId,
                            'user_id' => auth()->id(),
                            'loan_type_id' => $loanTypeId,
                            'principal_amount' => $principal,
                            'interest_rate' => $rate,
                            'interest_amount' => $interestAmt,
                            'total_amount' => $totalLoan,
                            'opening_commission' => $openingComm,
                            'paid_amount' => 0,
                            'balance_amount' => $totalLoan,
                            'payment_frequency' => $freq,
                            'term_periods' => $periods,
                            'installment_amount' => $quota,
                            'start_date' => $startDate->toDateString(),
                            'due_date' => $dueDate->toDateString(),
                            'type_specific_data' => $item['type_specific_data'] ?? [],
                            'status' => 'activo',
                            'notes' => $item['notes'] ?? 'Creado desde Módulo Centralizado de Cobros',
                        ]);

                        $acc = 0;
                        for ($i = 1; $i <= $periods; $i++) {
                            $sDate = match ($freq) {
                                'semanal' => $startDate->copy()->addWeeks($i),
                                'quincenal' => $startDate->copy()->addDays($i * 15),
                                'mensual' => $startDate->copy()->addMonths($i),
                                default => $startDate->copy()->addWeeks($i),
                            };
                            $instQuota = ($i === $periods) ? round($totalLoan - $acc, 2) : $quota;
                            $acc += $instQuota;

                            LoanInstallment::create([
                                'loan_id' => $newLoan->id,
                                'installment_number' => $i,
                                'scheduled_date' => $sDate->toDateString(),
                                'scheduled_amount' => $instQuota,
                                'paid_amount' => 0,
                                'status' => 'pendiente',
                            ]);
                        }

                        if ($openingComm > 0) {
                            LoanPayment::create([
                                'loan_id' => $newLoan->id,
                                'branch_id' => $branchId,
                                'user_id' => auth()->id(),
                                'pos_transaction_id' => $posTx->id,
                                'payment_code' => 'COM-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                                'payment_type' => 'comision_apertura',
                                'amount' => $openingComm,
                                'payment_method' => $method,
                                'paid_at' => now(),
                                'notes' => 'Comisión por apertura (' . $newLoan->loan_code . ')',
                            ]);
                        }
                    }

                    // D) PAGO / APORTACIÓN DE TANDA (Con o sin crear nuevo participante básico)
                    elseif ($section === 'tanda_abono') {
                        $tandaId = (int) ($item['tanda_id'] ?? 0);
                        $tanda = Tanda::where('id', $tandaId)->lockForUpdate()->firstOrFail();
                        $participantId = !empty($item['participant_id']) ? (int) $item['participant_id'] : null;
                        $periodNo = max(1, (int) ($item['period_number'] ?? 1));
                        $tandaAmount = round((float) ($item['amount'] ?? $tanda->payment_amount), 2);

                        if ($participantId) {
                            $participant = TandaClient::where('tanda_id', $tanda->id)->where('id', $participantId)->first();
                        } else {
                            // Crear nuevo cliente con información básica y relacionarlo con la tanda
                            $maxPos = (int) TandaClient::where('tanda_id', $tanda->id)->max('position');
                            $participant = TandaClient::create([
                                'tanda_id' => $tanda->id,
                                'customer_id' => null,
                                'customer_name' => $item['customer_name'] ?? $resolvedCustomerName,
                                'customer_phone' => $item['customer_phone'] ?? ($validated['customer_phone'] ?? null),
                                'position' => !empty($item['position']) ? (int) $item['position'] : ($maxPos + 1),
                                'payments' => [],
                                'total_paid' => 0,
                            ]);
                        }

                        if ($participant) {
                            $paymentsMap = is_array($participant->payments)
                                ? $participant->payments
                                : (json_decode($participant->payments ?? '{}', true) ?: []);

                            $prevPeriodAmt = isset($paymentsMap[$periodNo]) ? (float) $paymentsMap[$periodNo] : 0;
                            $paymentsMap[$periodNo] = round($prevPeriodAmt + $tandaAmount, 2);

                            $sumPaid = 0;
                            foreach ($paymentsMap as $val) {
                                if (is_numeric($val)) {
                                    $sumPaid += (float) $val;
                                }
                            }
                            $participant->payments = $paymentsMap;
                            $participant->total_paid = round($sumPaid, 2);
                            $participant->save();

                            TandaPayment::create([
                                'tanda_id' => $tanda->id,
                                'tanda_client_id' => $participant->id,
                                'branch_id' => $branchId,
                                'user_id' => auth()->id(),
                                'pos_transaction_id' => $posTx->id,
                                'receipt_code' => 'TND-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                                'period_number' => $periodNo,
                                'amount' => $tandaAmount,
                                'payment_method' => $method,
                                'payment_breakdown' => [
                                    'efectivo' => $cashAmount,
                                    'tarjeta' => $cardAmount,
                                    'transferencia' => $transferAmount,
                                ],
                                'paid_at' => now(),
                                'notes' => "Aportación Tanda {$tanda->description} - Periodo #{$periodNo}",
                            ]);
                        }
                    }
                }

                // 4. Registrar impacto en Caja (CashFlow) para corte diario con desglose por cliente y método (únicamente el monto cobrado hoy)
                if ($paidToday > 0) {
                    $conceptsSummary = collect($items)->map(fn($i) => $i['title'] ?? $i['section'])->implode(' | ');
                    $cfDesc = "Cobro Centralizado ({$txCode})" . ($dueAmount > 0 ? " [Abono/Saldo Pendiente $" . number_format($dueAmount, 2) . "]" : "") . ": {$conceptsSummary}";
                    CashFlowService::register(
                        'income',
                        $paidToday,
                        Str::limit($cfDesc, 245),
                        $txCode,
                        'Cobro Centralizado',
                        $branchId,
                        [
                            'pos_transaction_id' => $posTx->id,
                            'customer_id' => $resolvedCustomerId,
                            'customer_name' => $resolvedCustomerName,
                            'payment_method' => $method,
                            'cash_amount' => $cashAmount,
                            'card_amount' => $cardAmount,
                            'transfer_amount' => $transferAmount,
                            'commission_amount' => $commissionTotal,
                        ]
                    );
                }

                return $posTx;
            });

            return response()->json([
                'success' => true,
                'message' => "Movimiento {$transaction->transaction_code} procesado correctamente.",
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'receipt_url' => route('central-pos.receipt', $transaction->id),
                'open_drawer' => (bool) $transaction->drawer_opened,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Impresión de ticket unificado del proceso de venta (omite saldo pendiente de préstamos y dispara apertura física de cajón).
     */
    public function printReceipt(PosTransaction $transaction)
    {
        $transaction->load(['branch', 'user', 'financieraPayments.financiera', 'loanPayments.loan', 'tandaPayments.tanda']);
        return view('central-pos.receipt', compact('transaction'));
    }

    /**
     * Endpoint para ejecutar la acción física de abrir caja (pulso ESC/POS a impresora de tickets).
     */
    public function openCashDrawer(Request $request)
    {
        return view('central-pos.open-drawer');
    }

    /**
     * Registro rápido de abono de financiera desde el detalle de Ventas/Clientes de Financieras.
     */
    public function registerDirectFinancieraAbono(Request $request, FinSale $sale)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'commission_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $abono = round((float) $validated['amount'], 2);
        $comm = round((float) ($validated['commission_amount'] ?? 0), 2);
        $total = round($abono + $comm, 2);
        $method = $validated['payment_method'];
        $branchId = auth()->user()->branch_id ?? $sale->branch_id ?? 1;

        $cashAmount = $method === 'mixto' ? round((float) $request->input('cash_amount', 0), 2) : ($method === 'efectivo' ? $total : 0);
        $cardAmount = $method === 'mixto' ? round((float) $request->input('card_amount', 0), 2) : ($method === 'tarjeta' ? $total : 0);
        $transferAmount = $method === 'mixto' ? round((float) $request->input('transfer_amount', 0), 2) : ($method === 'transferencia' ? $total : 0);

        if ($method === 'mixto' && abs(($cashAmount + $cardAmount + $transferAmount) - $total) > 0.05) {
            return back()->with('error', 'En pago mixto la suma de Efectivo + Tarjeta + Transferencia debe igualar el total ($' . number_format($total, 2) . ').');
        }

        $tx = DB::transaction(function () use ($sale, $abono, $comm, $total, $method, $branchId, $cashAmount, $cardAmount, $transferAmount, $validated) {
            $lockedSale = FinSale::where('id', $sale->id)->lockForUpdate()->firstOrFail();
            $installmentNo = ((int) $lockedSale->abonos_count) + 1;
            $receiptCode = 'ABFIN-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            $txCode = 'MOV-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $posTx = PosTransaction::create([
                'transaction_code' => $txCode,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'customer_name' => $lockedSale->customer_name,
                'customer_phone' => $lockedSale->customer_phone,
                'subtotal' => $abono,
                'commission_total' => $comm,
                'total_amount' => $total,
                'paid_amount' => $total,
                'change_amount' => 0,
                'payment_method' => $method,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'transfer_amount' => $transferAmount,
                'items_payload' => [[
                    'section' => 'financiera_abono',
                    'title' => "Abono Financiera #{$installmentNo} - TAG: " . ($lockedSale->tag_contrato ?: $lockedSale->sale_code),
                    'amount' => $abono,
                    'commission' => $comm,
                    'quantity' => 1,
                ]],
                'drawer_opened' => true,
                'status' => 'completado',
            ]);

            FinSalePayment::create([
                'fin_sale_id' => $lockedSale->id,
                'device_id' => $lockedSale->device_id,
                'financiera_id' => $lockedSale->financiera_id,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'pos_transaction_id' => $posTx->id,
                'receipt_code' => $receiptCode,
                'tag_contrato' => $lockedSale->tag_contrato,
                'imei' => $lockedSale->device?->imei,
                'customer_name' => $lockedSale->customer_name,
                'installment_number' => $installmentNo,
                'amount' => $abono,
                'commission_amount' => $comm,
                'total_amount' => $total,
                'payment_method' => $method,
                'payment_breakdown' => [
                    'efectivo' => $cashAmount,
                    'tarjeta' => $cardAmount,
                    'transferencia' => $transferAmount,
                ],
                'payment_date' => now(),
                'notes' => $validated['notes'] ?? "Abono #{$installmentNo} a equipo {$lockedSale->device?->imei}",
            ]);

            $lockedSale->total_abonos_paid = round((float) $lockedSale->total_abonos_paid + $abono, 2);
            $lockedSale->abonos_count = $installmentNo;
            $lockedSale->last_abono_at = now();
            $lockedSale->save();

            FinSaleNote::create([
                'sale_id' => $lockedSale->id,
                'user_id' => auth()->id(),
                'note' => "Abono #{$installmentNo} ({$receiptCode}): $" . number_format($abono, 2)
                    . ($comm > 0 ? " + Comisión $" . number_format($comm, 2) : '')
                    . " | Método: " . strtoupper($method),
            ]);

            CashFlowService::register(
                'income',
                $total,
                "Abono Financiera ({$lockedSale->financiera?->name}) TAG: " . ($lockedSale->tag_contrato ?: $lockedSale->sale_code) . " - {$lockedSale->customer_name}",
                $receiptCode,
                'Financieras (Abono)',
                $branchId,
                [
                    'pos_transaction_id' => $posTx->id,
                    'customer_name' => $lockedSale->customer_name,
                    'payment_method' => $method,
                    'cash_amount' => $cashAmount,
                    'card_amount' => $cardAmount,
                    'transfer_amount' => $transferAmount,
                    'commission_amount' => $comm,
                ]
            );

            return $posTx;
        });

        return back()
            ->with('success', "Abono registrado en Financiera y Caja correctamente (Folio {$tx->transaction_code}).")
            ->with('print_receipt_url', route('central-pos.receipt', $tx->id));
    }
}

