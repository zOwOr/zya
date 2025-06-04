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

        return Redirect::route('dashboard')->with('success', 'Order has been created!');
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
    }
    public function updateAmount(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

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
            'user_id' => auth()->id(), // Asegúrate de que se esté asignando el valor correcto
        ]);
        

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

        return Redirect::route('order.pendingOrders')->with('success', 'Order has been completed!');
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

        $orders = Order::where('due', '>', '0')
            ->sortable()
            ->paginate($row);

        return view('orders.pending-due', [
            'orders' => $orders
        ]);
    }

    public function orderDueAjax(Int $id)
    {
        $order = Order::findOrFail($id);

        return response()->json($order);
    }

    public function updateDue(Request $request)
    {
            // ✅ Validar si el corte del día ya fue aplicado
        if (DailyCut::where('date', Carbon::today())->exists()) {
            return redirect()->route('order.pendingDue')->with('error', 'No se puede registrar el pago. El corte diario ya fue aplicado.');
        }




        $rules = [
            'order_id' => 'required|numeric',
            'due' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        $order = Order::findOrFail($request->order_id);

        if ($validatedData['due'] > $order->due) {
            return redirect()->route('order.pendingDue')->with('error', 'El monto ingresado excede la cantidad debida.');
        }
        if ($validatedData['due'] <= 0) {
            return redirect()->route('order.pendingDue')->with('error', 'El monto a pagar debe ser mayor que cero.');
        }


        $mainPay = $order->pay;
        $mainDue = $order->due;

        $paid_due = $mainDue - $validatedData['due'];
        $paid_pay = $mainPay + $validatedData['due'];

        Order::findOrFail($request->order_id)->update([
            'due' => $paid_due,
            'pay' => $paid_pay,
        ]);


    $paidAmount = $paid_pay - $mainPay;

    // Registrar en caja solo si pagó algo
    if ($paidAmount > 0) {
        CashFlowService::register(
            'income',
            $paidAmount,  // Registrar solo lo pagado en esta operación
            'Pago parcial de orden #' . $order->id,
            $order->id,
            'Orden'
        );
    }

    return Redirect::route('order.pendingDue')->with('success', 'Due Amount Updated Successfully!');
}

    public function markAsPaid($orderId)
{
    $order = Order::findOrFail($orderId);
    $order->update(['order_status' => 'paid']);

    CashFlowService::register(
        'income',
        $order->total_amount,
        'Pago de orden #' . $order->id,
        $order->id,
        'ventas'
    );

    return redirect()->back()->with('success', 'Orden pagada y registrada en caja.');
}
}
