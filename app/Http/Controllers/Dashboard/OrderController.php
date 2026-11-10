<?php

namespace App\Http\Controllers\Dashboard;
use Illuminate\Support\Facades\Storage;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetails;
use App\Models\Movement;
use App\Models\OrderDetailsVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use Illuminate\Support\Facades\Redirect;
use Gloudemans\Shoppingcart\Facades\Cart;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Services\CashFlowService;
use App\Models\DailyCut;
use App\Models\PaymentHistory;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\Customer;
class OrderController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'orders';

    protected array $permissionMapping = [
        'pendingOrders' => 'read',
        'completeOrders' => 'read',
        'stockManage' => 'read',
        'orderDetails' => 'read',
        'invoiceDownload' => 'read',
        'contract' => 'read',
        'pendingDue' => 'read',
        'orderDueAjax' => 'read',
        'printAbonoTicket' => 'read',
        'abonosHistoryAjax' => 'read',
        'storeOrder' => 'create',
        'updateAmount' => 'edit',
        'updateStatus' => 'edit',
        'updateDue' => 'edit',
        'markAsPaid' => 'edit',
        'uploadVideo' => 'edit',
        'destroy' => 'delete',
        'updateDeviceId' => 'edit',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }
    /**
     * Display a listing of the resource.
     */
    public function pendingOrders()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100000) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100000.');
        }

        $orders = Order::where('order_status', 'pending')->sortable()->paginate($row);

        return view('orders.pending-orders', [
            'orders' => $orders
        ]);
    }

    public function destroy($id)
    {
        // Buscar la orden
        $order = Order::findOrFail($id);
        
        // Obtener los detalles de la orden
        $orderDetails = OrderDetails::where('order_id', $id)->get();
    
        // Recorrer cada detalle de la orden y devolver el stock de los productos reales
        foreach ($orderDetails as $detail) {
            // Los productos dinámicos no afectaron el stock, no hay nada que restaurar
            if ($detail->is_dynamic) {
                continue;
            }

            $product = Product::find($detail->product_id);
            
            if ($product) {
                // Incrementar el stock del producto según la cantidad de la orden
                $product->stock_quantity += $detail->quantity;
                $product->save();
            }
        }
        
        // Eliminar los detalles de la orden
        OrderDetails::where('order_id', $id)->delete();
        
        // Eliminar la orden
        $order->delete();
    
        // Redirigir con mensaje de éxito
        return redirect()->route('order.pendingOrders')->with('success', 'La orden ha sido cancelada y el stock ha sido actualizado.');
    }
    

    public function completeOrders()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100000) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100000.');
        }

        $orders = Order::where('order_status', 'complete')->sortable()->paginate($row);

        return view('orders.complete-orders', [
            'orders' => $orders
        ]);
    }

    public function stockManage()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100000) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100000.');
        }

        return view('stock.index', [
            'products' => Product::with(['category', 'supplier'])
                ->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeOrder(Request $request)
    {
        $rules = [
            'customer_id' => 'required|numeric',
            'payment_status' => 'required|string',
            'pay' => 'numeric|nullable',
            'due' => 'numeric|nullable',
        ];

        $invoice_no = IdGenerator::generate([
            'table' => 'orders',
            'field' => 'invoice_no',
            'length' => 10,
            'prefix' => 'INV-'
        ]);

        $validatedData = $request->validate($rules);
        $validatedData['order_date'] = Carbon::now()->format('Y-m-d');
        $validatedData['order_status'] = 'pending';
        $validatedData['total_products'] = Cart::instance('default')->count();
        $validatedData['sub_total'] = Cart::instance('default')->subtotal();
        $validatedData['vat'] = Cart::instance('default')->tax();
        $validatedData['invoice_no'] = $invoice_no;
        $validatedData['total'] = Cart::instance('default')->total();
        $validatedData['due'] = Cart::instance('default')->total() - $validatedData['pay'];
        $validatedData['created_at'] = Carbon::now();

        // Verificar si el stock es suficiente (solo para productos reales, no dinámicos)
        $contents = Cart::instance('default')->content();
        foreach ($contents as $content) {
            // Los productos dinámicos tienen is_dynamic = true en options y no tienen ID numérico
            if ($content->options->get('is_dynamic')) {
                continue;
            }
            $product = Product::find($content->id);
            if ($product && $product->stock_quantity < $content->qty) {
                // Si el stock es insuficiente, redirige con un mensaje de error
                return redirect()->back()->with('error', 'No hay suficiente stock para el producto: ' . $product->product_name);
            }
        }

        $order_id = Order::insertGetId($validatedData);

        // Create Order Details
        $contents = Cart::instance('default')->content();

        foreach ($contents as $content) {
            $isDynamic = (bool) $content->options->get('is_dynamic', false);

            $oDetails = [
                'order_id'   => $order_id,
                'product_id' => $isDynamic ? null : $content->id,
                'quantity'   => $content->qty,
                'unitcost'   => $content->price,
                'total'      => $content->total,
                'created_at' => Carbon::now(),
                'is_dynamic' => $isDynamic,
            ];

            if ($isDynamic) {
                $oDetails['dynamic_product_name']    = $content->name;
                $oDetails['dynamic_brand']           = $content->options->get('brand');
                $oDetails['dynamic_model']           = $content->options->get('model');
                $oDetails['dynamic_imei']            = $content->options->get('serial');
                $oDetails['dynamic_category_status'] = $content->options->get('status');
                $oDetails['dynamic_warranty_time']   = $content->options->get('warranty');
                $oDetails['dynamic_observations']    = $content->options->get('observations');
                $oDetails['dynamic_product_code']    = $content->options->get('dynamic_code');
            }

            OrderDetails::insert($oDetails);
        }

        // Descontar stock solo de los productos reales del inventario
        foreach ($contents as $content) {
            if ($content->options->get('is_dynamic')) {
                continue;
            }
            $product = Product::find($content->id);
            if ($product) {
                $product->stock_quantity -= $content->qty;
                $product->save();
            }
        }

        // Delete Cart Shopping History
        Cart::instance('default')->destroy();

        $branchId = $request->branch_id ?? auth()->user()->branch_id ?? null;

        if (!$branchId) {
            return redirect()->back()->with('error', 'No se especificó la sucursal.');
        }

        CashFlowService::register(
            'income',
            $validatedData['pay'],
            'Pago inicial de la orden #' . $order_id,
            $order_id,
            'Orden',
            $branchId
        );

        return Redirect::route('dashboard')->with('success', 'El pedido se ha creado!');
    }


    /**
     * Display the specified resource.
     */
    public function orderDetails(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('productRelation')
                        ->where('order_id', $order_id)
                        ->orderBy('id', 'DESC')
                        ->get();

        $orderDetailVideo = OrderDetailsVideo::where('order_id', $order->id)->first();

        return view('orders.details-order', [
            'order' => $order,
            'orderDetails' => $orderDetails,
            'orderDetailVideo' => $orderDetailVideo,
        ]);
    }public function updateAmount(Request $request)
{
    $order = Order::findOrFail($request->order_id);

    $previousPay = $order->pay;  // Guardamos el pago anterior

    // Actualizar las cantidades
    $order->pay = $request->pay;
    $order->due = $request->due;
    $order->save();

    // Guardar movimiento en el historial
    Movement::create([
        'order_id' => $order->id,
        'movement_type' => 'Actualización de pago',
        'amount' => $request->due - $request->pay,
        'justification' => $request->justification,
        'user_id' => auth()->id(),
    ]);

    // Registrar movimiento en caja según diferencia de pago
    $deltaPay = $order->pay - $previousPay;

    $branchId = auth()->user()->branch_id ?? null;

    if (!$branchId) {
        return back()->with('error', 'No se especificó la sucursal para registrar movimiento en caja.');
    }

    CashFlowService::register(
        $deltaPay > 0 ? 'income' : 'expense',
        abs($deltaPay),
        'Ajuste de pago orden #' . $order->id,
        $order->id,
        'Orden',
        $branchId
    );


    return back()->with('success', 'Las cantidades se han actualizado correctamente.');
}

    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,avi,mov|max:51200', // 50MB
        ]);
    
        // Encuentra la orden y si ya existe un video, actualízalo
        $orderDetailVideo = OrderDetailsVideo::where('order_id', $request->order_id)->first();
    
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('videos', $filename, 'public');
    
            // Si ya existe un video, lo actualizamos
            if ($orderDetailVideo) {
                // Elimina el video anterior si existe
                Storage::disk('public')->delete('videos/' . $orderDetailVideo->video);
                // Actualiza el video
                $orderDetailVideo->update([
                    'video' => $filename,
                ]);
            } else {
                // Si no existe video, creamos uno nuevo
                OrderDetailsVideo::create([
                    'order_id' => $request->order_id,
                    'video' => $filename,
                    'user_id' => auth()->id(),
                ]);
            }
    
            return back()->with('success', 'Video actualizado correctamente.');
        }
    
        return back()->withErrors(['video' => 'No se pudo subir el video.']);
    }
    
    
    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request)
    {
        $order_id = $request->id;

        // Reduce the stock (solo para productos reales del inventario)
        $products = OrderDetails::where('order_id', $order_id)->get();

        foreach ($products as $product) {
            if ($product->is_dynamic) {
                continue;
            }
            Product::where('id', $product->product_id)
                    ->update(['stock_quantity' => DB::raw('stock_quantity-'.$product->quantity)]);
        }

        Order::findOrFail($order_id)->update(['order_status' => 'complete']);

        return Redirect::route('order.pendingOrders')->with('success', 'El pedido se ha completado.');
    }

    public function invoiceDownload(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('productRelation')
                        ->where('order_id', $order_id)
                        ->orderBy('id', 'DESC')
                        ->get();

        // show data (only for debugging)
        return view('orders.invoice-order', [
            'order' => $order,
            'orderDetails' => $orderDetails,
        ]);
    }

    public function pendingDue()
    {
        $row = (int) request('row', 15);

        if ($row < 1 || $row > 100000) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100000.');
        }

        $canAccessAllBranches = auth()->user()->isSuperAdmin() 
            || auth()->user()->can('financieras.all_branches') 
            || auth()->user()->can('corte.caja.all');

        $branches = $canAccessAllBranches ? Branch::orderBy('name')->get() : collect();

        $selectedBranchId = $canAccessAllBranches ? request('branch_id') : (auth()->user()->branch_id);

        $search = request('search');
        $deviceId = request('device_id');
        $imei = request('imei');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $activeTab = request('tab', 'notas'); // 'notas' or 'prestamos'

        // 1. Query para Notas de Venta con Saldo Pendiente
        $query = Order::with(['customer', 'branch', 'posTransaction.branch', 'orderDetails.product', 'paymentHistories.user'])
            ->where('due', '>', 0);

        if ($selectedBranchId) {
            $query->where(function($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId)
                  ->orWhereHas('posTransaction', fn($p) => $p->where('branch_id', $selectedBranchId))
                  ->orWhereHas('paymentHistories', fn($h) => $h->where('branch_id', $selectedBranchId));
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('tit_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($deviceId) {
            $query->whereHas('orderDetails', fn($q) => $q->where('device_id', 'like', "%{$deviceId}%"));
        }

        if ($imei) {
            $query->where(function ($q) use ($imei) {
                $q->whereHas('orderDetails.product', fn($q2) => $q2->where('imei', 'like', "%{$imei}%"))
                  ->orWhereHas('orderDetails', fn($q2) => $q2->where('dynamic_imei', 'like', "%{$imei}%"));
            });
        }

        if ($startDate) {
            $query->whereDate('order_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('order_date', '<=', $endDate);
        }

        // Totales de KPI de Notas de Venta
        $allPendingOrders = (clone $query)->get();
        $notesKpi = [
            'count' => $allPendingOrders->count(),
            'total' => round((float) $allPendingOrders->sum('total'), 2),
            'pay' => round((float) $allPendingOrders->sum('pay'), 2),
            'due' => round((float) $allPendingOrders->sum('due'), 2),
        ];

        $orders = $query->orderBy('id', 'desc')->paginate($row)->appends(request()->query());

        // 2. Query para Préstamos con Saldo Pendiente
        $loansQuery = Loan::with(['customer', 'loanType', 'branch'])
            ->where('balance_amount', '>', 0)
            ->whereIn('status', ['activo', 'vencido']);

        if ($selectedBranchId) {
            $loansQuery->where('branch_id', $selectedBranchId);
        }
        if ($search) {
            $loansQuery->where(function ($q) use ($search) {
                $q->where('loan_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('tit_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $allPendingLoans = (clone $loansQuery)->get();
        $loansKpi = [
            'count' => $allPendingLoans->count(),
            'total' => round((float) $allPendingLoans->sum('total_amount'), 2),
            'paid' => round((float) $allPendingLoans->sum('paid_amount'), 2),
            'due' => round((float) $allPendingLoans->sum('balance_amount'), 2),
        ];

        $pendingLoans = $loansQuery->orderBy('id', 'desc')->paginate($row, ['*'], 'loans_page')->appends(request()->query());

        return view('orders.pending-due', [
            'orders' => $orders,
            'notesKpi' => $notesKpi,
            'loansKpi' => $loansKpi,
            'pendingLoans' => $pendingLoans,
            'canAccessAllBranches' => $canAccessAllBranches,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'activeTab' => $activeTab,
        ]);
    }

    public function orderDueAjax(Int $id)
    {
        $order = Order::with(['customer', 'branch', 'posTransaction.branch', 'paymentHistories.user'])->findOrFail($id);

        return response()->json([
            'id' => $order->id,
            'invoice_no' => $order->invoice_no,
            'customer_name' => $order->customer?->tit_name ?? 'Cliente Mostrador',
            'customer_phone' => $order->customer?->phone ?? '-',
            'branch_name' => $order->resolved_branch?->name ?? 'Sucursal Principal',
            'order_date' => $order->order_date,
            'total' => (float) $order->total,
            'pay' => (float) $order->pay,
            'due' => (float) $order->due,
            'payment_status' => $order->payment_status,
            'histories' => $order->paymentHistories->map(fn($h) => [
                'id' => $h->id,
                'amount' => (float) $h->amount,
                'method' => $h->method,
                'note' => $h->note,
                'user' => $h->user?->name ?? 'Usuario',
                'date' => $h->created_at ? $h->created_at->format('d/m/Y H:i') : '',
            ]),
        ]);
    }

    public function updateDue(Request $request)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|numeric|exists:orders,id',
            'due' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'cash_received' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $order = Order::with(['customer', 'branch', 'posTransaction.branch'])->findOrFail($validatedData['order_id']);

        $abonoAmount = round((float) $validatedData['due'], 2);

        if ($abonoAmount > (float) $order->due + 0.05) {
            return redirect()->route('order.pendingDue')->with('error', 'El monto ingresado ($' . number_format($abonoAmount, 2) . ') excede el saldo pendiente ($' . number_format($order->due, 2) . ').');
        }

        $method = $validatedData['payment_method'];

        if ($method === 'efectivo') {
            $cashAmount = isset($validatedData['cash_amount']) && $validatedData['cash_amount'] !== null && $validatedData['cash_amount'] !== '' 
                ? round((float) $validatedData['cash_amount'], 2) 
                : $abonoAmount;
            $cardAmount = 0;
            $transferAmount = 0;
            $abonoAmount = min((float) $order->due, $cashAmount);
        } elseif ($method === 'tarjeta') {
            $cardAmount = isset($validatedData['card_amount']) && $validatedData['card_amount'] !== null && $validatedData['card_amount'] !== '' 
                ? round((float) $validatedData['card_amount'], 2) 
                : $abonoAmount;
            $cashAmount = 0;
            $transferAmount = 0;
            $abonoAmount = min((float) $order->due, $cardAmount);
        } elseif ($method === 'transferencia') {
            $transferAmount = isset($validatedData['transfer_amount']) && $validatedData['transfer_amount'] !== null && $validatedData['transfer_amount'] !== '' 
                ? round((float) $validatedData['transfer_amount'], 2) 
                : $abonoAmount;
            $cashAmount = 0;
            $cardAmount = 0;
            $abonoAmount = min((float) $order->due, $transferAmount);
        } else { // mixto
            $cashAmount = round((float) ($validatedData['cash_amount'] ?? 0), 2);
            $cardAmount = round((float) ($validatedData['card_amount'] ?? 0), 2);
            $transferAmount = round((float) ($validatedData['transfer_amount'] ?? 0), 2);
            $sumMix = round($cashAmount + $cardAmount + $transferAmount, 2);

            if (abs($sumMix - $abonoAmount) > 0.05) {
                $faltante = round($abonoAmount - $sumMix, 2);
                if ($faltante > 0) {
                    return redirect()->route('order.pendingDue')->with('error', 'En pago mixto, falta asignar $' . number_format($faltante, 2) . ' para cubrir el abono de $' . number_format($abonoAmount, 2) . '.');
                } else {
                    return redirect()->route('order.pendingDue')->with('error', 'En pago mixto, la suma asignada excede el abono por $' . number_format(abs($faltante), 2) . '.');
                }
            }
        }

        $branchId = $order->branch_id 
            ?? $order->posTransaction?->branch_id 
            ?? (auth()->user()->branch_id ?? Branch::value('id'));

        $paymentHistory = DB::transaction(function () use (
            $order,
            $abonoAmount,
            $method,
            $cashAmount,
            $cardAmount,
            $transferAmount,
            $branchId,
            $validatedData
        ) {
            $newDue = max(0, round((float) $order->due - $abonoAmount, 2));
            $newPay = round((float) $order->pay + $abonoAmount, 2);

            $order->update([
                'due' => $newDue,
                'pay' => $newPay,
                'order_status' => ($newDue <= 0) ? 'complete' : $order->order_status,
                'branch_id' => $order->branch_id ?: $branchId,
            ]);

            $methodMap = [
                'efectivo' => 'HandCash',
                'transferencia' => 'Cheque',
                'tarjeta' => 'Due',
                'mixto' => 'HandCash',
            ];

            $note = $validatedData['note'] ?? "Abono a Nota #{$order->invoice_no} ({$method})";

            $histData = [
                'order_id' => $order->id,
                'amount' => $abonoAmount,
                'method' => $methodMap[$method] ?? 'HandCash',
                'user_id' => auth()->id(),
                'branch_id' => $branchId,
            ];
            if (Schema::hasColumn('payment_histories', 'note')) {
                $histData['note'] = $note;
            }
            if (Schema::hasColumn('payment_histories', 'cash_amount')) {
                $histData['cash_amount'] = $cashAmount;
            }
            if (Schema::hasColumn('payment_histories', 'card_amount')) {
                $histData['card_amount'] = $cardAmount;
            }
            if (Schema::hasColumn('payment_histories', 'transfer_amount')) {
                $histData['transfer_amount'] = $transferAmount;
            }

            $history = PaymentHistory::create($histData);

            $customerName = $order->customer?->tit_name ?? 'Cliente Mostrador';

            CashFlowService::register(
                'income',
                $abonoAmount,
                "Abono Nota #{$order->invoice_no}: {$customerName} (Método: " . strtoupper($method) . ")",
                $order->invoice_no,
                'Abono Nota Venta',
                $branchId,
                [
                    'user_id' => auth()->id(),
                    'customer_id' => $order->customer_id,
                    'customer_name' => $customerName,
                    'payment_method' => $method,
                    'cash_amount' => $cashAmount,
                    'card_amount' => $cardAmount,
                    'transfer_amount' => $transferAmount,
                ]
            );

            return $history;
        });

        $msg = 'Abono de $' . number_format($abonoAmount, 2) . ' registrado exitosamente.';
        if ($order->fresh()->due <= 0) {
            $msg .= ' ¡La nota de venta ha sido COMPLETAMENTE LIQUIDADA!';
        }

        return redirect()->route('order.pendingDue')
            ->with('success', $msg)
            ->with('print_ticket_url', route('order.abonoTicket', ['order' => $order->id, 'historyId' => $paymentHistory->id]));
    }

    public function printAbonoTicket(Order $order, $historyId)
    {
        $order->load(['customer', 'branch', 'posTransaction.branch', 'orderDetails.product']);
        $history = PaymentHistory::with(['user', 'branch'])->where('order_id', $order->id)->findOrFail($historyId);
        $branch = $order->resolved_branch ?? Branch::find(auth()->user()->branch_id) ?? Branch::first();

        return view('orders.abono-ticket', [
            'order' => $order,
            'history' => $history,
            'branch' => $branch,
        ]);
    }

    public function abonosHistoryAjax(Order $order)
    {
        $histories = PaymentHistory::with(['user', 'branch'])
            ->where('order_id', $order->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'amount' => (float) $h->amount,
                    'method' => $h->method,
                    'note' => $h->note,
                    'user_name' => $h->user?->name ?? 'Usuario',
                    'branch_name' => $h->branch?->name ?? 'Sucursal',
                    'date' => $h->created_at ? $h->created_at->format('d/m/Y H:i') : '',
                    'ticket_url' => route('order.abonoTicket', ['order' => $h->order_id, 'historyId' => $h->id]),
                ];
            });

        return response()->json([
            'order' => [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'customer_name' => $order->customer?->tit_name ?? 'Cliente Mostrador',
                'customer_phone' => $order->customer?->phone ?? '-',
                'total' => (float) $order->total,
                'pay' => (float) $order->pay,
                'due' => (float) $order->due,
                'order_date' => $order->order_date,
            ],
            'histories' => $histories,
        ]);
    }


public function markAsPaid($orderId)
{
    $order = Order::findOrFail($orderId);
    $order->update(['order_status' => 'paid']);

    $branchId = auth()->user()->branch_id ?? null;
    if (!$branchId) {
        return redirect()->back()->with('error', 'No se pudo determinar la sucursal para registrar el pago.');
    }

    CashFlowService::register(
        'income',
        $order->total_amount,
        'Pago de orden #' . $order->id,
        $order->id,
        'ventas',
        $branchId  // Aquí pasas la sucursal
    );

    return redirect()->back()->with('success', 'Orden pagada y registrada en caja.');
}

public function updateDeviceId(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'device_id' => 'required|string|max:255',
    ]);

    $order = Order::findOrFail($request->order_id);
    $order->device_id = $request->device_id;
    $order->save();

    return back()->with('success', 'Device ID actualizado correctamente.');
}

public function contract(Int $order_id)
{
    $order = Order::with(['customer', 'user'])->findOrFail($order_id);

    // Datos del préstamo (ejemplo, cámbialo según tu lógica real)
    $loan_amount = $order->total;
    $installments = 12; // Número de pagos
    $installment_amount = $loan_amount / $installments;

    return view('orders.contract', [
        'order' => $order,
        'loan_amount' => $loan_amount,
        'installments' => $installments,
        'installment_amount' => $installment_amount,
    ]);
}


}
