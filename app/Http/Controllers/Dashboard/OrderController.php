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
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Gloudemans\Shoppingcart\Facades\Cart;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Services\CashFlowService;
use App\Models\DailyCut;
use App\Models\PaymentHistory;
class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function pendingOrders()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
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
    
        // Recorrer cada detalle de la orden y devolver el stock de los productos
        foreach ($orderDetails as $detail) {
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

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        $orders = Order::where('order_status', 'complete')->sortable()->paginate($row);

        return view('orders.complete-orders', [
            'orders' => $orders
        ]);
    }

    public function stockManage()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
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
        $validatedData['total_products'] = Cart::count();
        $validatedData['sub_total'] = Cart::subtotal();
        $validatedData['vat'] = Cart::tax();
        $validatedData['invoice_no'] = $invoice_no;
        $validatedData['total'] = Cart::total();
        $validatedData['due'] = Cart::total() - $validatedData['pay'];
        $validatedData['created_at'] = Carbon::now();

            // Verificar si el stock es suficiente
        $contents = Cart::content();
        foreach ($contents as $content) {
            $product = Product::find($content->id);
            if ($product && $product->stock_quantity < $content->qty) {
                // Si el stock es insuficiente, redirige con un mensaje de error
                return redirect()->back()->with('error', 'No hay suficiente stock para el producto: ' . $product->name);
            }
        }

        $order_id = Order::insertGetId($validatedData);

        // Create Order Details
        $contents = Cart::content();
        $oDetails = array();

        foreach ($contents as $content) {
            $oDetails['order_id'] = $order_id;
            $oDetails['product_id'] = $content->id;
            $oDetails['quantity'] = $content->qty;
            $oDetails['unitcost'] = $content->price;
            $oDetails['total'] = $content->total;
            $oDetails['created_at'] = Carbon::now();

            OrderDetails::insert($oDetails);
        }

        foreach ($contents as $content) {
            $product = Product::find($content->id);
            if ($product) {
                $product->stock_quantity -= $content->qty;
                $product->save();
            }
        }
        // Delete Cart Sopping History
        Cart::destroy();

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
            $branchId  // aquí pasa la sucursal
        );




        return Redirect::route('dashboard')->with('success', 'El pedido se ha creado!');
    }

    /**
     * Display the specified resource.
     */
    public function orderDetails(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('product')
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

        // Reduce the stock
        $products = OrderDetails::where('order_id', $order_id)->get();

        foreach ($products as $product) {
            Product::where('id', $product->product_id)
                    ->update(['stock_quantity' => DB::raw('stock_quantity-'.$product->quantity)]);
        }

        Order::findOrFail($order_id)->update(['order_status' => 'complete']);

        return Redirect::route('order.pendingOrders')->with('success', 'El pedido se ha completado.');
    }

    public function invoiceDownload(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('product')
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
    $row = (int) request('row', 10);

    if ($row < 1 || $row > 100) {
        abort(400, 'The per-page parameter must be an integer between 1 and 100.');
    }

    $search = request('search');
    $deviceId = request('device_id');
    $imei = request('imei');
    $productName = request('product');

    $query = Order::where('due', '>', 0);

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('invoice_no', 'like', "%{$search}%")
              ->orWhereHas('customer', fn($q2) => $q2->where('tit_name', 'like', "%{$search}%"));
        });
    }

    if ($deviceId) {
        $query->whereHas('orderDetails', fn($q) => $q->where('device_id', 'like', "%{$deviceId}%"));
    }

    if ($imei) {
        $query->whereHas('orderDetails.product', fn($q) => $q->where('imei', 'like', "%{$imei}%"));
    }



    $orders = $query->sortable()->paginate($row)->appends(request()->query());

    return view('orders.pending-due', [
        'orders' => $orders
    ]);
}


    public function orderDueAjax(Int $id)
    {
        $order = Order::findOrFail($id);

        return response()->json($order);
    }public function updateDue(Request $request)
{
    $validatedData = $request->validate([
        'order_id' => 'required|numeric',
        'due' => 'required|numeric',
        'payment_method' => 'required|in:HandCash,Cheque,Due',
    ]);

    $order = Order::findOrFail($validatedData['order_id']);

    // Detectar si la orden tiene productos con proveedores Payjoy o KrediYa
    $hasSpecialProvider = \App\Models\OrderDetails::where('order_id', $order->id)
        ->whereHas('product.supplier', function($query) {
            $query->whereIn('name', ['Payjoy', 'KrediYa']);
        })->exists();

    if ($validatedData['due'] > $order->due) {
        return redirect()->route('order.pendingDue')->with('error', 'El monto ingresado excede la cantidad debida.');
    }

    // Actualizar pagos en la orden
    $paid_due = $order->due - $validatedData['due'];
    $paid_pay = $order->pay + $validatedData['due'];

    $order->update([
        'due' => $paid_due,
        'pay' => $paid_pay,
    ]);

    // Registrar historial siempre
    PaymentHistory::create([
        'order_id' => $order->id,
        'amount' => $validatedData['due'],
        'method' => $validatedData['payment_method'],
        'user_id' => auth()->id(),
        'branch_id' => auth()->user()->branch_id ?? null,
    ]);

    // Si el proveedor es especial, no registrar en banco nunca
    if ($hasSpecialProvider) {
        if ($validatedData['payment_method'] === 'HandCash') {
            // Registrar solo en caja (CashFlow)
            CashFlowService::register(
                'income',
                $validatedData['due'],
                'Pago efectivo orden #' . $order->id . ' - proveedor especial',
                $order->id,
                'Orden',
                auth()->user()->branch_id
            );
        }
        // Para cheque o due no registrar nada en caja ni banco, solo historial (ya hecho)
    } else {
        // Para otras órdenes, comportamiento normal
        if ($validatedData['payment_method'] === 'HandCash') {
            // Registrar en caja y banco (depende de cómo lo tengas)
            CashFlowService::register(
                'income',
                $validatedData['due'],
                'Pago efectivo orden #' . $order->id,
                $order->id,
                'Orden',
                auth()->user()->branch_id
            );
            // Aquí deberías incluir el registro en banco si tienes servicio/modelo para ello
        } elseif (in_array($validatedData['payment_method'], ['Cheque', 'Due'])) {
            // No registrar en caja ni banco, solo historial
        }
    }

    return redirect()->route('order.pendingDue')->with('success', 'Pago registrado correctamente.');
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
