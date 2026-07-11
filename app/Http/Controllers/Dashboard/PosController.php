<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use Illuminate\Support\Facades\Redirect;
use Gloudemans\Shoppingcart\Facades\Cart;

class PosController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'pos';

    protected array $permissionMapping = [
        'index' => 'read',
        'addCart' => 'create',
        'updateCart' => 'edit',
        'deleteCart' => 'delete',
        'createInvoice' => 'create',
        'showInvoice' => 'read',
        'printInvoice' => 'read',
        'scanBarcode' => 'create',
        'addDynamicProduct' => 'create',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }
    public function index()
    {
        $todayDate = Carbon::now();
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100000) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100000.');
        }

        return view('pos.index', [
            'customers' => Customer::all()->sortBy('name'),
            'productItem' => Cart::instance('default')->content(),
            'products' => Product::where('expire_date', '>', $todayDate)->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query()),
        ]);
    }

    public function addCart(Request $request)
    {
        $rules = [
            'id' => 'required|numeric',
            'name' => 'required|string',
            'price' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        Cart::instance('default')->add([
            'id' => $validatedData['id'],
            'name' => $validatedData['name'],
            'qty' => 1,
            'price' => $validatedData['price'],
            'options' => ['size' => 'large']
        ]);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function updateCart(Request $request, $rowId)
    {
        $rules = [
            'qty' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        Cart::instance('default')->update($rowId, $validatedData['qty']);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function deleteCart(String $rowId)
    {
        Cart::instance('default')->remove($rowId);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function createInvoice(Request $request)
    {
        $rules = [
            'customer_id' => 'required'
        ];

        $validatedData = $request->validate($rules);
        $customer = Customer::where('id', $validatedData['customer_id'])->first();

        if (!$customer) {
            return Redirect::back()->with('warning', 'Cliente no encontrado.');
        }

        $contents = Cart::instance('default')->content();

        if ($contents->isEmpty()) {
            return Redirect::back()->with('warning', 'El carrito está vacío.');
        }

        // Verificación del stock solo para productos reales del inventario
        foreach ($contents as $item) {
            // Los productos dinámicos no tienen stock en inventario, omitir
            if ($item->options->get('is_dynamic')) {
                continue;
            }
            $product = Product::find($item->id);
            if ($product && $product->stock_quantity < $item->qty) {
                return Redirect::back()->with(['warning' => 'No hay suficiente stock para el producto: ' . $product->product_name]);
            }
        }

        // Guardamos el customer_id en sesión para recuperarlo en la vista GET
        session(['pos_invoice_customer_id' => $customer->id]);

        return Redirect::route('pos.showInvoice');
    }

    public function showInvoice()
    {
        $customerId = session('pos_invoice_customer_id');

        if (!$customerId) {
            return Redirect::route('pos.index')->with('warning', 'Selecciona un cliente antes de continuar.');
        }

        $customer = Customer::find($customerId);
        $content  = Cart::instance('default')->content();

        if (!$customer) {
            return Redirect::route('pos.index')->with('warning', 'Cliente no encontrado.');
        }

        // Si el carrito está vacío redirigir al POS
        if ($content->isEmpty()) {
            return Redirect::route('pos.index')->with('warning', 'El carrito está vacío.');
        }

        return view('pos.create-invoice', [
            'customer' => $customer,
            'content'  => $content,
        ]);
    }

    public function printInvoice(Request $request)
    {
        $rules = [
            'customer_id' => 'required'
        ];

        $validatedData = $request->validate($rules);
        $customer = Customer::where('id', $validatedData['customer_id'])->first();
        $content = Cart::instance('default')->content();

        return view('pos.print-invoice', [
            'customer' => $customer,
            'content' => $content
        ]);
    }
    public function scanBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $barcode = $request->input('barcode');

        // Buscar producto por código de barras (ajustar columna si es necesario)
        $product = Product::where('product_code', $barcode)->first();

        if (!$product) {
            return redirect()->back()->with('warning', 'Producto no encontrado.');
        }

        // Añadir producto al carrito
        Cart::instance('default')->add([
            'id' => $product->id,
            'name' => $product->product_name,
            'qty' => 1,
            'price' => $product->selling_price,
            'options' => ['image' => $product->product_image]
        ]);

        return redirect()->back()->with('success', 'Producto añadido correctamente.');
    }

    public function addDynamicProduct(Request $request)
    {
        $validatedData = $request->validate([
            'product_name'    => 'required|string|max:255',
            'selling_price'   => 'required|numeric',
            'brand'           => 'nullable|string|max:255',
            'model'           => 'nullable|string|max:255',
            'imei'            => 'nullable|string|max:255',
            'category_status' => 'required|string|max:255',
            'warranty_time'   => 'nullable|string|max:255',
            'stock_quantity'  => 'required|numeric|min:1',
            'observations'    => 'nullable|string',
        ]);

        // Generamos un ID único temporal con prefijo DYN- para no colisionar
        // con IDs reales de productos. Este ID NO se persiste en la tabla products.
        $dynamicId = 'DYN-' . time() . '-' . rand(1000, 9999);

        Cart::instance('default')->add([
            'id'    => $dynamicId,
            'name'  => $validatedData['product_name'],
            'qty'   => (int) $validatedData['stock_quantity'],
            'price' => $validatedData['selling_price'],
            'options' => [
                'is_dynamic'      => true,
                'image'           => null,
                'brand'           => $validatedData['brand'] ?? null,
                'model'           => $validatedData['model'] ?? null,
                'serial'          => $validatedData['imei'] ?? null,
                'status'          => $validatedData['category_status'],
                'warranty'        => $validatedData['warranty_time'] ?? null,
                'observations'    => $validatedData['observations'] ?? null,
                'dynamic_code'    => $dynamicId,
            ]
        ]);

        return redirect()->back()->with('success', 'Producto personalizado añadido al carrito correctamente.');
    }
}
