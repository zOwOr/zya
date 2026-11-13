<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ExternalCustomer;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSaleNote;
use App\Models\FinSalePayment;
use App\Models\Gift;
use App\Models\GiftMovement;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UnifiedPosController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'cobros';

    protected array $permissionMapping = [
        'index' => 'read',
        'lookupFinanciera' => 'read',
        'lookupCustomerLoans' => 'read',
        'lookupCustomerPendingNotes' => 'read',
        'lookupTanda' => 'read',
        'lookupProducts' => 'read',
        'lookupGifts' => 'read',
        'recentTransactions' => 'read',
        'lookupExternalCustomers' => 'external_customers',
        'storeExternalCustomer' => 'external_customers',
        'checkout' => 'create',
        'printReceipt' => 'read',
        'openCashDrawer' => 'open_drawer',
        'registerDirectFinancieraAbono' => 'financieras',
    ];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) {
                abort(401);
            }

            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            $action = $request->route()?->getActionMethod();

            // 1. Acceso a la Consola POS, Consulta de Notas Pendientes y Reimpresión de Recibo
            if (in_array($action, ['index', 'printReceipt', 'lookupCustomerPendingNotes'])) {
                if (!$user->can('cobros.read') && !$user->can('cobros.menu') && !$user->can('pos.read') && !$user->can('pos.menu')) {
                    abort(403, 'No tienes permisos para acceder al Punto de Venta.');
                }
            }

            // 2. Procesar Cobro / Checkout
            if ($action === 'checkout') {
                if (!$user->can('cobros.create') && !$user->can('pos.create') && !$user->can('orders.create')) {
                    abort(403, 'No tienes permisos para procesar cobros y ventas.');
                }
            }

            // 3. Abrir Cajón Físico
            if ($action === 'openCashDrawer') {
                if (!$user->can('cobros.open_drawer') && !$user->can('cash.open_drawer')) {
                    abort(403, 'No tienes permisos para abrir el cajón de dinero.');
                }
            }

            // 4. Abono Rápido de Financieras
            if ($action === 'registerDirectFinancieraAbono') {
                if (!$user->can('cobros.financieras') && !$user->can('financieras.ventas.abonar')) {
                    abort(403, 'No tienes permisos para registrar abonos de financieras.');
                }
            }

            // 5. Uso y captura de Clientes Externos en POS (exclusivo para financieras)
            if (in_array($action, ['lookupExternalCustomers', 'storeExternalCustomer'], true)) {
                if (!$user->can('cobros.external_customers')) {
                    abort(403, 'No tienes permisos para operar con Clientes Externos en el Punto de Venta.');
                }
            }

            return $next($request);
        });
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
        $canPosExternalCustomers = Schema::hasTable('external_customers')
            && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.external_customers'));
        $externalCustomers = $canPosExternalCustomers
            ? ExternalCustomer::selectableInPos()->orderBy('tit_name')->get(['id', 'tit_name', 'tit_phone', 'tit_email', 'tit_city', 'tit_status'])
            : collect();
        $extCustomerStatusOptions = ExternalCustomer::TIT_STATUS_OPTIONS;
        $tandas = Tanda::with(['participants', 'periods'])
            ->when(!$this->canAccessAllBranches() && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->get();

        $products = Product::orderBy('product_name')
            ->take(100)
            ->get(['id', 'product_name', 'product_code', 'selling_price', 'stock_quantity', 'brand', 'model', 'imei', 'category_status', 'warranty_time']);

        $gifts = Gift::with(['supplier', 'finSupplier'])
            ->where('branch_id', $branchId)
            ->where('status', 'activo')
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get();

        $branches = Branch::orderBy('name')->get();

        // Movimientos recientes del día en curso
        $recentTransactions = PosTransaction::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        return view('central-pos.index', compact(
            'financieras',
            'loanTypes',
            'customers',
            'externalCustomers',
            'canPosExternalCustomers',
            'extCustomerStatusOptions',
            'tandas',
            'products',
            'gifts',
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
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('cobros.financieras') && !$user->can('financieras.read') && !$user->can('financieras.ventas.read')) {
            return response()->json(['results' => [], 'message' => 'Sin permisos para consultar financieras.'], 403);
        }

        $query = trim((string) $request->input('q', ''));
        $financieraId = $request->input('financiera_id');

        if ($query === '' && !$financieraId) {
            return response()->json(['results' => []]);
        }

        $sales = FinSale::with(['device.brand', 'financiera', 'branch', 'payments'])
            ->where('status', '!=', 'cancelada')
            ->where(function ($q) {
                $q->whereNull('sale_type')
                    ->orWhere('sale_type', '!=', 'contado');
            })
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
            $effectiveDownPayment = (!is_null($sale->enganche_descuento) && $sale->enganche_descuento !== '')
                ? (float) $sale->enganche_descuento
                : (float) $sale->down_payment;
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
                'enganche_descuento' => (!is_null($sale->enganche_descuento) && $sale->enganche_descuento !== '') ? (float) $sale->enganche_descuento : null,
                'effective_down_payment' => $effectiveDownPayment,
                'credit_amount' => $creditAmount,
                'abono_semanal' => (float) ($sale->abono_semanal ?: 0),
                'total_abonos_paid' => $totalAbonos,
                'abonos_count' => (int) $sale->abonos_count,
                'remaining_balance' => $remaining,
                'next_installment' => ((int) $sale->abonos_count) + 1,
                'branch_id' => $sale->branch_id,
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
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('cobros.prestamos') && !$user->can('prestamos.read')) {
            return response()->json(['open_loans' => [], 'has_open_loans' => false, 'message' => 'Sin permisos para consultar préstamos.'], 403);
        }

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
                'operation_type' => $loan->operation_type ?? 'prestamo',
                'type_name' => $loan->isArticleCredit()
                    ? ('Crédito Artículo (' . $loan->article_summary_label . ')')
                    : ($loan->loanType?->name ?? 'General'),
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
     * Consultar Notas de Venta con Saldo Pendiente (orders.due > 0) de un cliente
     * desde el Punto de Venta, soportando cliente del catálogo o nombre/teléfono escrito.
     */
    public function lookupCustomerPendingNotes(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('cobros.read') && !$user->can('cobros.menu') && !$user->can('pos.read') && !$user->can('pos.menu')) {
            return response()->json([
                'searched' => false,
                'has_pending_notes' => false,
                'pending_notes_count' => 0,
                'total_due' => 0,
                'pending_notes' => [],
                'message' => 'Sin permisos para consultar notas pendientes.',
            ], 403);
        }

        $customerId = (int) $request->input('customer_id', 0);
        $rawName = trim((string) $request->input('customer_name', ''));
        $searchName = mb_strtolower($rawName, 'UTF-8') === 'cliente mostrador' ? '' : $rawName;
        $searchPhone = trim((string) $request->input('customer_phone', ''));
        $phoneDigits = preg_replace('/\D+/', '', $searchPhone);

        if ($customerId <= 0 && mb_strlen($searchName, 'UTF-8') < 3 && mb_strlen($phoneDigits, 'UTF-8') < 4) {
            return response()->json([
                'searched' => false,
                'has_pending_notes' => false,
                'pending_notes_count' => 0,
                'total_due' => 0,
                'pending_notes' => [],
            ]);
        }

        $query = Order::with(['customer', 'branch', 'posTransaction.branch', 'orderDetails.product'])
            ->where('due', '>', 0)
            ->where('order_status', '!=', 'cancelado')
            ->whereDoesntHave('posTransaction', fn($tx) => $tx->where('status', 'cancelado'));

        if ($customerId > 0) {
            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'searched' => true,
                    'has_pending_notes' => false,
                    'pending_notes_count' => 0,
                    'total_due' => 0,
                    'pending_notes' => [],
                ]);
            }

            $custName = trim((string) $customer->tit_name);
            $custNameLower = mb_strtolower($custName, 'UTF-8');

            $query->where(function ($q) use ($customerId, $custName, $custNameLower) {
                $q->where(function ($direct) use ($customerId, $custName, $custNameLower) {
                    $direct->where('customer_id', $customerId)
                        ->where(function ($verifyTx) use ($customerId, $custName, $custNameLower) {
                            $verifyTx->whereDoesntHave('posTransaction')
                                ->orWhereHas('posTransaction', function ($tx) use ($customerId, $custName, $custNameLower) {
                                    $tx->where('customer_id', $customerId)
                                        ->orWhere(function ($unlinked) use ($custName, $custNameLower) {
                                            $unlinked->whereNull('customer_id')
                                                ->where(function ($nameCheck) use ($custName, $custNameLower) {
                                                    $nameCheck->whereNull('customer_name')
                                                        ->orWhere('customer_name', '')
                                                        ->orWhere('customer_name', 'Cliente Mostrador');
                                                    if ($custName !== '' && $custNameLower !== 'cliente mostrador') {
                                                        $nameCheck->orWhereRaw('LOWER(TRIM(customer_name)) = ?', [$custNameLower]);
                                                    }
                                                });
                                        });
                                });
                        });
                })->orWhereHas('posTransaction', function ($tx) use ($customerId, $custName, $custNameLower) {
                    $tx->where('customer_id', $customerId);
                    if ($custName !== '' && $custNameLower !== 'cliente mostrador') {
                        $tx->orWhere(function ($unlinkedMatch) use ($custNameLower) {
                            $unlinkedMatch->whereNull('customer_id')
                                ->whereRaw('LOWER(TRIM(customer_name)) = ?', [$custNameLower]);
                        });
                    }
                });
            });
        } else {
            $query->where(function ($q) use ($searchName, $searchPhone, $phoneDigits) {
                if (mb_strlen($searchName, 'UTF-8') >= 3) {
                    $q->whereHas('posTransaction', fn($tx) => $tx->where('customer_name', 'like', "%{$searchName}%"))
                        ->orWhere(function ($custMatch) use ($searchName) {
                            $custMatch->whereHas('customer', fn($c) => $c->where('tit_name', 'like', "%{$searchName}%"))
                                ->where(function ($noContradiction) use ($searchName) {
                                    $noContradiction->whereDoesntHave('posTransaction')
                                        ->orWhereHas('posTransaction', function ($tx) use ($searchName) {
                                            $tx->whereNotNull('customer_id')
                                                ->orWhere('customer_name', 'like', "%{$searchName}%")
                                                ->orWhere('customer_name', 'Cliente Mostrador')
                                                ->orWhereNull('customer_name')
                                                ->orWhere('customer_name', '');
                                        });
                                });
                        });
                }

                if (mb_strlen($phoneDigits, 'UTF-8') >= 4) {
                    $phoneNeedle = mb_strlen($searchPhone, 'UTF-8') >= 4 ? $searchPhone : $phoneDigits;
                    $q->orWhereHas('posTransaction', fn($tx) => $tx->where('customer_phone', 'like', "%{$phoneNeedle}%"))
                        ->orWhereHas('customer', function ($c) use ($phoneNeedle) {
                            $c->where('tit_phone', 'like', "%{$phoneNeedle}%")
                                ->orWhere('alternate_phone', 'like', "%{$phoneNeedle}%");
                        });
                }
            });
        }

        $orders = $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $canViewOrders = $user->isSuperAdmin() || $user->can('orders.menu') || $user->can('orders.read');

        $formatted = $orders->map(function (Order $order) use ($canViewOrders) {
            $conceptsList = $order->orderDetails->map(function ($detail) {
                $name = $detail->is_dynamic
                    ? ($detail->dynamic_product_name ?: 'Producto Libre')
                    : ($detail->productRelation?->product_name ?? 'Producto');
                $qty = max(1, (int) $detail->quantity);
                return $qty > 1 ? "{$name} (x{$qty})" : $name;
            })->filter()->values();

            $concepts = $conceptsList->isNotEmpty()
                ? $conceptsList->take(3)->implode(', ') . ($conceptsList->count() > 3 ? ' (+' . ($conceptsList->count() - 3) . ' más)' : '')
                : 'Nota de venta';

            $displayCustomer = ($order->posTransaction && !$order->posTransaction->customer_id && trim((string) $order->posTransaction->customer_name) !== '')
                ? $order->posTransaction->customer_name
                : ($order->customer?->tit_name ?? $order->posTransaction?->customer_name ?? 'Cliente');

            return [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'order_date' => $order->order_date,
                'order_date_formatted' => $order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : '-',
                'branch_name' => $order->resolved_branch?->name ?? 'Sucursal Matriz',
                'customer_name' => $displayCustomer,
                'concepts' => $concepts,
                'total' => round((float) $order->total, 2),
                'pay' => round((float) $order->pay, 2),
                'due' => round((float) $order->due, 2),
                'payment_status' => $order->payment_status,
                'detail_url' => $canViewOrders ? route('order.orderDetails', $order->id) : null,
                'pending_due_url' => $canViewOrders ? route('order.pendingDue', ['search' => $order->invoice_no]) : null,
            ];
        });

        return response()->json([
            'searched' => true,
            'has_pending_notes' => $formatted->isNotEmpty(),
            'pending_notes_count' => $formatted->count(),
            'total_due' => round((float) $formatted->sum('due'), 2),
            'pending_notes' => $formatted->values(),
        ]);
    }

    /**
     * Buscar tandas activas para aportaciones en el Punto de Venta.
     */
    public function lookupTanda(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('cobros.tandas') && !$user->can('tandas.read')) {
            return response()->json(['tandas' => [], 'message' => 'Sin permisos para consultar tandas.'], 403);
        }

        $branchId = $this->canAccessAllBranches() && $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : (auth()->user()->branch_id ?? Branch::value('id'));

        $q = trim((string) $request->input('q', ''));
        $tandas = Tanda::with(['participants', 'periods'])
            ->when(!$this->canAccessAllBranches() && $branchId, fn($query) => $query->where('branch_id', $branchId))
            ->when($q !== '', fn($query) => $query->where('description', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['tandas' => $tandas]);
    }

    /**
     * Buscar productos de inventario por código de barras, IMEI o nombre.
     */
    public function lookupProducts(Request $request)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('cobros.productos') && !$user->can('pos.read') && !$user->can('product.read')) {
            return response()->json(['products' => [], 'message' => 'Sin permisos para consultar productos.'], 403);
        }

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
     * Buscar regalos / promocionales disponibles en la sucursal activa para agregarlos en $0.00 al POS.
     */
    public function lookupGifts(Request $request)
    {
        $user = auth()->user();
        if (
            !$user->isSuperAdmin()
            && !$user->can('cobros.productos')
            && !$user->can('cobros.read')
            && !$user->can('pos.read')
            && !$user->can('gifts.read')
            && !$user->can('gifts.deliver')
        ) {
            return response()->json(['gifts' => [], 'message' => 'Sin permisos para consultar regalos.'], 403);
        }

        $branchId = ($this->canAccessAllBranches() && $request->filled('branch_id'))
            ? (int) $request->input('branch_id')
            : ($user->branch_id ?? Branch::value('id'));

        $q = trim((string) $request->input('q', ''));
        $gifts = Gift::with(['supplier', 'finSupplier'])
            ->where('branch_id', $branchId)
            ->where('status', 'activo')
            ->where('stock_quantity', '>', 0)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('supplier_name', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(function (Gift $g) {
                return [
                    'id' => $g->id,
                    'code' => $g->code,
                    'name' => $g->name,
                    'brand' => $g->brand,
                    'stock_quantity' => (int) $g->stock_quantity,
                    'supplier_name' => $g->supplier_display_name,
                    'branch_id' => $g->branch_id,
                ];
            });

        return response()->json(['gifts' => $gifts]);
    }

    /**
     * Buscar clientes externos activos para selección rápida en POS (financieras).
     */
    public function lookupExternalCustomers(Request $request)
    {
        if (!Schema::hasTable('external_customers')) {
            return response()->json([
                'external_customers' => [],
                'message' => 'Falta ejecutar "php artisan migrate" para habilitar el catálogo de Clientes Externos.',
            ], 422);
        }

        $q = trim((string) $request->input('q', ''));

        $results = ExternalCustomer::selectableInPos()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('tit_name', 'like', "%{$q}%")
                        ->orWhere('tit_phone', 'like', "%{$q}%")
                        ->orWhere('tit_email', 'like', "%{$q}%");
                });
            })
            ->orderBy('tit_name')
            ->limit(50)
            ->get(['id', 'tit_name', 'tit_phone', 'tit_email', 'tit_city', 'tit_status']);

        return response()->json(['external_customers' => $results]);
    }

    /**
     * Captura y guardado rápido de Cliente Externo directamente desde el POS (catálogo separado).
     */
    public function storeExternalCustomer(Request $request)
    {
        if (!Schema::hasTable('external_customers')) {
            return response()->json([
                'success' => false,
                'message' => 'Falta ejecutar "php artisan migrate" para guardar Clientes Externos.',
            ], 422);
        }

        $validated = $request->validate(
            ExternalCustomer::validationRules(),
            ExternalCustomer::validationMessages()
        );

        $branchId = ($this->canAccessAllBranches() && $request->filled('branch_id'))
            ? (int) $request->input('branch_id')
            : (auth()->user()->branch_id ?? Branch::value('id'));

        $validated['branch_id'] = $branchId;
        $validated['created_by'] = auth()->id();
        $validated['status'] = ExternalCustomer::STATUS_ACTIVE;

        $externalCustomer = ExternalCustomer::create($validated);

        $existingInternal = Customer::where('tit_phone', $externalCustomer->tit_phone)->first(['id', 'tit_name', 'tit_phone']);

        return response()->json([
            'success' => true,
            'message' => "Cliente externo '{$externalCustomer->tit_name}' guardado correctamente.",
            'external_customer' => [
                'id' => $externalCustomer->id,
                'tit_name' => $externalCustomer->tit_name,
                'tit_phone' => $externalCustomer->tit_phone,
                'tit_email' => $externalCustomer->tit_email,
                'tit_city' => $externalCustomer->tit_city,
                'tit_status' => $externalCustomer->tit_status,
            ],
            'internal_match' => $existingInternal ? [
                'id' => $existingInternal->id,
                'tit_name' => $existingInternal->tit_name,
                'tit_phone' => $existingInternal->tit_phone,
            ] : null,
        ], 201);
    }

    /**
     * Historial de tickets recientes para reimpresión o vista previa (WhatsApp / sin papel / sin luz).
     */
    public function recentTransactions(Request $request)
    {
        $user = auth()->user();
        if (
            !$user->isSuperAdmin()
            && !$user->can('cobros.read')
            && !$user->can('cobros.menu')
            && !$user->can('pos.read')
            && !$user->can('pos.menu')
        ) {
            return response()->json(['transactions' => []], 403);
        }

        $branchId = ($this->canAccessAllBranches() && $request->filled('branch_id'))
            ? (int) $request->input('branch_id')
            : ($user->branch_id ?? Branch::value('id'));

        $date = trim((string) $request->input('date', Carbon::today()->format('Y-m-d')));
        $search = trim((string) $request->input('search', ''));

        $transactions = PosTransaction::with('user')
            ->when(!$this->canAccessAllBranches() || $request->filled('branch_id'), fn($q) => $q->where('branch_id', $branchId))
            ->when($date !== '' && $search === '', fn($q) => $q->whereDate('created_at', $date))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('items_payload', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(150)
            ->get()
            ->map(function (PosTransaction $tx) {
                $concepts = $tx->concepts_summary;
                return [
                    'id' => $tx->id,
                    'transaction_code' => $tx->transaction_code,
                    'customer_name' => $tx->customer_name ?: 'Cliente Mostrador',
                    'is_external_customer' => !empty($tx->external_customer_id),
                    'total_amount' => number_format((float) $tx->total_amount, 2),
                    'payment_method' => strtoupper((string) $tx->payment_method),
                    'status' => $tx->status,
                    'user_name' => $tx->user?->name ?? 'Sistema',
                    'created_at' => $tx->created_at ? $tx->created_at->format('d/m/Y H:i') : '',
                    'concepts' => Str::limit($concepts, 70),
                    'receipt_url' => route('central-pos.receipt', $tx->id),
                ];
            });

        return response()->json(['transactions' => $transactions]);
    }

    /**
     * Procesar el cierre de un registro de pago unificado (interactuando con las 4 secciones:
     * Financieras, Préstamos, Tandas y Venta de Productos con stock / dinámicos sin stock).
     */
    public function checkout(Request $request)
    {
        $hasExternalCustomersTable = Schema::hasTable('external_customers');

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'external_customer_id' => $hasExternalCustomersTable ? 'nullable|exists:external_customers,id' : 'nullable',
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

        if (!$hasExternalCustomersTable && !empty($validated['external_customer_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Falta ejecutar "php artisan migrate" para asociar Clientes Externos en el Punto de Venta.',
            ], 422);
        }

        if (!empty($validated['customer_id']) && !empty($validated['external_customer_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'No es posible asociar simultáneamente un Cliente Interno y un Cliente Externo al mismo movimiento.',
            ], 422);
        }

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

        // Validar permisos granulares de las 4 secciones del Punto de Venta
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            foreach ($items as $item) {
                $sec = $item['section'] ?? '';
                if (PosTransaction::isFinancieraSection($sec)) {
                    if (!$user->can('cobros.financieras') && !$user->can('financieras.ventas.abonar')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para registrar abonos de Financieras en el Punto de Venta.',
                        ], 403);
                    }
                } elseif ($sec === 'prestamo_abono' || $sec === 'prestamo_apertura') {
                    if (!$user->can('cobros.prestamos') && !$user->can('prestamos.abonar') && !$user->can('prestamos.create')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para crear o abonar Préstamos en el Punto de Venta.',
                        ], 403);
                    }
                } elseif ($sec === 'tanda_abono') {
                    if (!$user->can('cobros.tandas') && !$user->can('tandas.abonar')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para registrar aportaciones de Tandas en el Punto de Venta.',
                        ], 403);
                    }
                } elseif ($sec === 'producto_inventario' || $sec === 'producto_dinamico') {
                    if (!$user->can('cobros.productos') && !$user->can('pos.create') && !$user->can('orders.create')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para vender Productos en el Punto de Venta.',
                        ], 403);
                    }
                } elseif ($sec === 'gift') {
                    if (!$user->can('cobros.productos') && !$user->can('pos.create') && !$user->can('orders.create') && !$user->can('gifts.deliver')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para entregar Regalos / Promocionales en el Punto de Venta.',
                        ], 403);
                    }
                }
            }
        }

        // Validar uso de Cliente Externo (solo con permiso y únicamente cuando hay conceptos de Financiera)
        $resolvedExternalCustomerId = !empty($validated['external_customer_id']) ? (int) $validated['external_customer_id'] : null;
        $hasFinancieraConcept = PosTransaction::containsFinancieraConcept($items);
        $hasExternalCompanyConcept = collect($items)->contains(fn($i) => ($i['section'] ?? '') === 'financiera_externa');

        if ($hasExternalCompanyConcept && (!$hasExternalCustomersTable || !Schema::hasColumn('fin_sale_payments', 'is_external_company'))) {
            return response()->json([
                'success' => false,
                'message' => 'Falta ejecutar "php artisan migrate" para registrar abonos de financieras por equipos externos.',
            ], 422);
        }

        if ($resolvedExternalCustomerId !== null) {
            if (!$user->isSuperAdmin() && !$user->can('cobros.external_customers')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para registrar cobros con Cliente Externo en el POS.',
                ], 403);
            }

            if (!$hasFinancieraConcept) {
                return response()->json([
                    'success' => false,
                    'message' => 'La opción de Cliente Externo solo está habilitada cuando el carrito contiene al menos un pago de Financiera.',
                ], 422);
            }

            $externalCustomer = ExternalCustomer::find($resolvedExternalCustomerId);
            if (!$externalCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente externo seleccionado no existe o fue eliminado.',
                ], 422);
            }

            if ($externalCustomer->isConverted() && $externalCustomer->converted_customer_id) {
                return response()->json([
                    'success' => false,
                    'message' => "El cliente externo '{$externalCustomer->tit_name}' ya fue promovido al catálogo de Clientes Internos. Selecciónalo desde el selector estándar de clientes.",
                ], 422);
            }
        }

        // Calcular totales del movimiento
        $subtotal = 0;
        $commissionTotal = 0;
        $resolvedCustomerName = trim((string) ($validated['customer_name'] ?? ''));
        $resolvedCustomerPhone = $validated['customer_phone'] ?? null;
        $resolvedCustomerId = $validated['customer_id'] ?? null;

        if ($resolvedExternalCustomerId) {
            $resolvedCustomerId = null;
            $extCust = $externalCustomer ?? ExternalCustomer::find($resolvedExternalCustomerId);
            if ($extCust) {
                if ($resolvedCustomerName === '' || strcasecmp($resolvedCustomerName, 'Cliente Mostrador') === 0) {
                    $resolvedCustomerName = $extCust->tit_name;
                }
                if (empty($resolvedCustomerPhone)) {
                    $resolvedCustomerPhone = $extCust->tit_phone;
                }
            }
        } elseif ($resolvedCustomerId && $resolvedCustomerName === '') {
            $cust = Customer::find($resolvedCustomerId);
            if ($cust) {
                $resolvedCustomerName = $cust->tit_name;
                if (empty($resolvedCustomerPhone)) {
                    $resolvedCustomerPhone = $cust->tit_phone;
                }
            }
        }

        foreach ($items as $idx => $item) {
            $section = $item['section'] ?? '';
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unitAmount = round((float) ($item['amount'] ?? 0), 2);
            $comm = round((float) ($item['commission'] ?? 0), 2);

            if ($section === 'prestamo_apertura') {
                // En apertura de préstamo, el capital prestado NO entra ni sale de caja; SOLO se cobra la comisión por apertura
                $commissionTotal += $comm;
            } elseif ($section === 'gift') {
                // Los regalos/promocionales siempre salen en $0.00 en ticket y sin cargo al total ni proveedor visible
                $items[$idx]['amount'] = 0;
                $items[$idx]['commission'] = 0;
                $sanitizedSub = PosTransaction::sanitizeGiftSubtitle($item['subtitle'] ?? null);
                if ($sanitizedSub !== null) {
                    $items[$idx]['subtitle'] = $sanitizedSub;
                } else {
                    unset($items[$idx]['subtitle']);
                }
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

        $hasFinancieraAbono = PosTransaction::containsFinancieraConcept($items);
        if ($hasFinancieraAbono && $dueAmount > 0.009) {
            return response()->json([
                'success' => false,
                'message' => 'Los abonos a financieras no permiten pago parcial ni dejar saldo pendiente en nota. Cubre el total completo o edita directamente el monto del abono.',
            ], 422);
        }

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
                $resolvedExternalCustomerId,
                $resolvedCustomerName,
                $resolvedCustomerPhone
            ) {
                // 1. Validar stock real primero para productos de inventario y regalos
                $inventoryItems = [];
                $dynamicItems = [];
                $giftItems = [];

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
                    } elseif ($section === 'gift') {
                        $giftId = (int) ($item['gift_id'] ?? 0);
                        $qty = max(1, (int) ($item['quantity'] ?? 1));
                        $gift = Gift::where('id', $giftId)->lockForUpdate()->first();
                        if (!$gift || $gift->status !== 'activo') {
                            throw new \RuntimeException("El regalo seleccionado no existe o no está activo.");
                        }
                        if ($gift->branch_id && (int) $gift->branch_id !== (int) $branchId) {
                            throw new \RuntimeException("El regalo '{$gift->name}' pertenece a otra sucursal y no puede entregarse desde esta caja.");
                        }
                        if ($gift->stock_quantity < $qty) {
                            throw new \RuntimeException("Stock insuficiente del regalo '{$gift->name}' (Disponible: {$gift->stock_quantity}, Solicitado: {$qty}).");
                        }
                        $giftItems[] = ['gift' => $gift, 'item' => $item, 'qty' => $qty];
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
                    'external_customer_id' => $resolvedExternalCustomerId,
                    'customer_name' => $resolvedCustomerName,
                    'customer_phone' => $resolvedCustomerPhone,
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
                    'drawer_opened' => (($cashAmount > 0 || ($method === 'efectivo' && ($paidToday > 0 || $changeAmount > 0))) && (bool) ($validated['open_drawer'] ?? true)),
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

                // 2B. Descontar inventario y registrar entrega de Regalos / Gifts (siempre $0.00)
                foreach ($giftItems as $giftData) {
                    /** @var Gift $gift */
                    $gift = $giftData['gift'];
                    $qty = $giftData['qty'];
                    $prevStock = (int) $gift->stock_quantity;
                    $newStock = max(0, $prevStock - $qty);

                    $gift->stock_quantity = $newStock;
                    $gift->save();

                    GiftMovement::create([
                        'gift_id' => $gift->id,
                        'branch_id' => $gift->branch_id ?: $branchId,
                        'user_id' => auth()->id(),
                        'movement_type' => 'entrega_cliente',
                        'quantity' => $qty,
                        'previous_stock' => $prevStock,
                        'new_stock' => $newStock,
                        'supplier_name' => $gift->supplier_display_name ?: $gift->supplier_name,
                        'customer_name' => $resolvedCustomerName,
                        'customer_phone' => $validated['customer_phone'] ?? null,
                        'reference_module' => 'central_pos',
                        'reference_code' => $txCode,
                        'notes' => $giftData['item']['notes'] ?? "Regalo entregado en POS 4 en 1 ({$txCode})",
                        'movement_date' => now(),
                    ]);
                }

                // 3. Procesar cada ítem de Financieras, Préstamos y Tandas
                $collectingBranchName = Branch::find($branchId)?->name ?? 'General';

                foreach ($items as $item) {
                    $section = $item['section'] ?? '';

                    // A) ABONO DE FINANCIERAS POR EQUIPO (Device_id / TAG)
                    if ($section === 'financiera_abono') {
                        $saleId = (int) ($item['sale_id'] ?? 0);
                        $sale = FinSale::where('id', $saleId)->lockForUpdate()->firstOrFail();

                        if ($sale->status === 'cancelada') {
                            throw new \RuntimeException("No es posible registrar abonos sobre la venta {$sale->sale_code} porque se encuentra cancelada.");
                        }
                        if ($sale->isContado()) {
                            throw new \RuntimeException("La venta {$sale->sale_code} es de contado y no admite abonos en el POS.");
                        }

                        $abonoAmount = round((float) ($item['amount'] ?? 0), 2);
                        $commAmount = round((float) ($item['commission'] ?? 0), 2);
                        $totalFin = round($abonoAmount + $commAmount, 2);
                        $installmentNo = ((int) $sale->abonos_count) + 1;
                        $receiptCode = 'ABFIN-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                        $finBreakdown = $this->allocatePaymentBreakdown(
                            $totalFin,
                            $paidToday,
                            $cashAmount,
                            $cardAmount,
                            $transferAmount
                        );

                        FinSalePayment::create([
                            'fin_sale_id' => $sale->id,
                            'external_customer_id' => $resolvedExternalCustomerId,
                            'device_id' => $sale->device_id,
                            'financiera_id' => $sale->financiera_id,
                            'branch_id' => $branchId,
                            'user_id' => auth()->id(),
                            'pos_transaction_id' => $posTx->id,
                            'receipt_code' => $receiptCode,
                            'tag_contrato' => $sale->tag_contrato,
                            'imei' => $sale->device?->imei,
                            'customer_name' => $sale->customer_name,
                            'is_external_company' => false,
                            'installment_number' => $installmentNo,
                            'amount' => $abonoAmount,
                            'commission_amount' => $commAmount,
                            'total_amount' => $totalFin,
                            'payment_method' => $method,
                            'payment_breakdown' => $finBreakdown,
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
                                . " | Método: " . strtoupper($method)
                                . " | Sucursal cobro: {$collectingBranchName}",
                        ]);
                    }

                    // A2) ABONO DE FINANCIERA POR EQUIPO EXTERNO (adquirido en otra empresa)
                    elseif ($section === 'financiera_externa') {
                        $finId = (int) ($item['financiera_id'] ?? 0);
                        $financiera = FinFinanciera::find($finId);
                        if (!$financiera) {
                            throw new \RuntimeException('Selecciona una financiera válida para registrar el cobro de equipo externo.');
                        }

                        $abonoAmount = round((float) ($item['amount'] ?? 0), 2);
                        $commAmount = round((float) ($item['commission'] ?? 0), 2);
                        $totalFin = round($abonoAmount + $commAmount, 2);
                        $receiptCode = 'ABEXT-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                        $finBreakdown = $this->allocatePaymentBreakdown(
                            $totalFin,
                            $paidToday,
                            $cashAmount,
                            $cardAmount,
                            $transferAmount
                        );

                        FinSalePayment::create([
                            'fin_sale_id' => null,
                            'external_customer_id' => $resolvedExternalCustomerId,
                            'device_id' => null,
                            'financiera_id' => $financiera->id,
                            'branch_id' => $branchId,
                            'user_id' => auth()->id(),
                            'pos_transaction_id' => $posTx->id,
                            'receipt_code' => $receiptCode,
                            'tag_contrato' => trim((string) ($item['tag_contrato'] ?? ($item['reference'] ?? 'EXTERNO'))) ?: 'EXTERNO',
                            'imei' => !empty($item['imei']) ? trim((string) $item['imei']) : null,
                            'customer_name' => $resolvedCustomerName,
                            'is_external_company' => true,
                            'installment_number' => null,
                            'amount' => $abonoAmount,
                            'commission_amount' => $commAmount,
                            'total_amount' => $totalFin,
                            'payment_method' => $method,
                            'payment_breakdown' => $finBreakdown,
                            'payment_date' => now(),
                            'notes' => $item['notes'] ?? ($item['subtitle'] ?? "Pago financiera externa ({$financiera->name}) en sucursal {$collectingBranchName}"),
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
                            'notes' => $item['notes'] ?? ($loan->isArticleCredit()
                                ? 'Abono Crédito Artículo registrado en Módulo Centralizado'
                                : 'Abono registrado en Módulo Centralizado'),
                        ]);
                    }

                    // C) CREACIÓN DE NUEVO PRÉSTAMO DESDE EL MÓDULO CENTRALIZADO
                    // (Sin impacto de desembolso en caja; solo entra la comisión por apertura)
                    elseif ($section === 'prestamo_apertura') {
                        $loanCustomerId = (int) ($item['customer_id'] ?? $resolvedCustomerId);
                        $loanTypeId = !empty($item['loan_type_id']) ? (int) $item['loan_type_id'] : null;
                        $principal = round((float) ($item['principal_amount'] ?? 0), 2);
                        $periods = max(1, (int) ($item['term_periods'] ?? 4));
                        $freq = $item['payment_frequency'] ?? 'semanal';

                        if (!empty($item['installment_amount']) && (float) $item['installment_amount'] > 0) {
                            $quota = round((float) $item['installment_amount'], 2);
                            $totalLoan = round($quota * $periods, 2);
                            $interestAmt = max(0, round($totalLoan - $principal, 2));
                            $rate = $principal > 0 ? round(($interestAmt / $principal) * 100, 2) : 0;
                        } else {
                            $rate = round((float) ($item['interest_rate'] ?? 15), 2);
                            $interestAmt = round($principal * ($rate / 100), 2);
                            $totalLoan = round($principal + $interestAmt, 2);
                            $quota = round($totalLoan / $periods, 2);
                        }

                        $openingComm = round((float) ($item['commission'] ?? 0), 2);
                        $startDate = Carbon::today();
                        $installmentDates = Loan::calculateInstallmentDates($startDate, $freq, $periods);
                        $dueDate = Carbon::parse(end($installmentDates));

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
                            $sDate = $installmentDates[$i - 1];
                            $instQuota = ($i === $periods) ? round($totalLoan - $acc, 2) : $quota;
                            $acc += $instQuota;

                            LoanInstallment::create([
                                'loan_id' => $newLoan->id,
                                'installment_number' => $i,
                                'scheduled_date' => $sDate,
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
                            'external_customer_id' => $resolvedExternalCustomerId,
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
        $relations = ['branch', 'user', 'customer', 'financieraPayments.financiera', 'loanPayments.loan', 'tandaPayments.tanda'];
        if (Schema::hasTable('external_customers') && Schema::hasColumn('pos_transactions', 'external_customer_id')) {
            $relations[] = 'externalCustomer';
        }
        $transaction->load($relations);
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
            'branch_id' => 'nullable|exists:branches,id',
            'amount' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($sale->status === 'cancelada') {
            return back()->with('error', "La venta {$sale->sale_code} está cancelada y no permite registrar abonos.");
        }
        if ($sale->isContado()) {
            return back()->with('error', "La venta {$sale->sale_code} es de contado y no admite abonos de crédito.");
        }

        $abono = round((float) $validated['amount'], 2);
        $comm = round((float) ($validated['commission_amount'] ?? 0), 2);
        $total = round($abono + $comm, 2);
        $method = $validated['payment_method'];

        $canChooseBranch = $this->canAccessAllBranches()
            || auth()->user()->can('financieras.all_branches')
            || auth()->user()->can('cash.all_branches');

        $branchId = ($canChooseBranch && !empty($validated['branch_id']))
            ? (int) $validated['branch_id']
            : (int) (auth()->user()->branch_id ?? $sale->branch_id ?? Branch::value('id') ?? 1);
        $collectingBranchName = Branch::find($branchId)?->name ?? 'General';

        $cashAmount = $method === 'mixto' ? round((float) $request->input('cash_amount', 0), 2) : ($method === 'efectivo' ? $total : 0);
        $cardAmount = $method === 'mixto' ? round((float) $request->input('card_amount', 0), 2) : ($method === 'tarjeta' ? $total : 0);
        $transferAmount = $method === 'mixto' ? round((float) $request->input('transfer_amount', 0), 2) : ($method === 'transferencia' ? $total : 0);

        if ($method === 'mixto' && abs(($cashAmount + $cardAmount + $transferAmount) - $total) > 0.05) {
            return back()->with('error', 'En pago mixto la suma de Efectivo + Tarjeta + Transferencia debe igualar el total ($' . number_format($total, 2) . ').');
        }

        $tx = DB::transaction(function () use ($sale, $abono, $comm, $total, $method, $branchId, $collectingBranchName, $cashAmount, $cardAmount, $transferAmount, $validated) {
            $lockedSale = FinSale::where('id', $sale->id)->lockForUpdate()->firstOrFail();

            if ($lockedSale->status === 'cancelada' || $lockedSale->isContado()) {
                throw new \RuntimeException('La venta seleccionada no admite abonos de crédito.');
            }

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
                    . " | Método: " . strtoupper($method)
                    . " | Sucursal cobro: {$collectingBranchName}",
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

    /**
     * Prorratea el desglose efectivo/tarjeta/transferencia para conceptos individuales cuando un ticket incluye múltiples ítems.
     */
    protected function allocatePaymentBreakdown(
        float $itemTotal,
        float $paidToday,
        float $cashAmount,
        float $cardAmount,
        float $transferAmount
    ): array {
        if ($itemTotal <= 0 || $paidToday <= 0) {
            return ['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0];
        }

        if (abs($paidToday - $itemTotal) <= 0.01) {
            return [
                'efectivo' => round($cashAmount, 2),
                'tarjeta' => round($cardAmount, 2),
                'transferencia' => round($transferAmount, 2),
            ];
        }

        $ratio = min(1.0, max(0.0, $itemTotal / $paidToday));
        $effCash = round($cashAmount * $ratio, 2);
        $effCard = round($cardAmount * $ratio, 2);
        $effTransfer = round($transferAmount * $ratio, 2);

        $diff = round($itemTotal - ($effCash + $effCard + $effTransfer), 2);
        if (abs($diff) > 0.0) {
            if ($effCash >= $effCard && $effCash >= $effTransfer && $cashAmount > 0) {
                $effCash = round($effCash + $diff, 2);
            } elseif ($effCard >= $effTransfer && $cardAmount > 0) {
                $effCard = round($effCard + $diff, 2);
            } elseif ($transferAmount > 0) {
                $effTransfer = round($effTransfer + $diff, 2);
            }
        }

        return [
            'efectivo' => max(0.0, $effCash),
            'tarjeta' => max(0.0, $effCard),
            'transferencia' => max(0.0, $effTransfer),
        ];
    }
}

