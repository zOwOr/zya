@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session()->has('success'))
                <div class="alert text-white bg-success" role="alert">
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert text-white bg-danger" role="alert">
                    <div class="iq-alert-text">{{ session('error') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-3">Lista de productos en stock</h4>
                    <p class="mb-0">Consulta existencias disponibles por sucursal, categoría, proveedor y ubicación en almacén.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <form action="{{ route('order.stockManage') }}" method="get">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 12px;">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 12px;">
                        <div class="form-group mb-0 d-flex align-items-center" style="gap: 8px;">
                            <label for="row" class="mb-0 font-weight-bold">Filas:</label>
                            <select class="form-control" name="row" id="row" onchange="this.form.submit()" style="min-width: 90px;">
                                <option value="10" @if(request('row') == '10') selected @endif>10</option>
                                <option value="25" @if(request('row') == '25') selected @endif>25</option>
                                <option value="50" @if(request('row') == '50') selected @endif>50</option>
                                <option value="100" @if(request('row') == '100') selected @endif>100</option>
                                <option value="100000" @if(request('row') == '100000') selected @endif>Todos</option>
                            </select>
                        </div>

                        @if($canAllBranches)
                            <div class="form-group mb-0 d-flex align-items-center" style="gap: 8px;">
                                <label for="branch_id" class="mb-0 font-weight-bold">Sucursal:</label>
                                <select class="form-control" name="branch_id" id="branch_id" onchange="this.form.submit()" style="min-width: 190px;">
                                    <option value="">Todas las sucursales</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @if((string) $selectedBranchId === (string) $branch->id) selected @endif>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($branches->isNotEmpty())
                            <span class="badge badge-info p-2">
                                <i class="fa-solid fa-store mr-1"></i> Sucursal: {{ $branches->first()->name }}
                            </span>
                        @endif
                    </div>

                    <div class="form-group mb-0" style="min-width: 280px;">
                        <div class="input-group">
                            <input type="text" id="search" class="form-control" name="search" placeholder="Buscar producto, SKU, IMEI..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="input-group-text bg-primary text-white"><i class="fa-solid fa-magnifying-glass font-size-20"></i></button>
                                <a href="{{ route('order.stockManage') }}" class="input-group-text bg-danger text-white" title="Limpiar filtros"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive rounded mb-3">
                <table class="table mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('stock.export') ? 'true' : 'false' }}">
                    <thead class="bg-white text-uppercase">
                        <tr class="ligth ligth-data">
                            <th>No.</th>
                            <th>Foto</th>
                            <th>Sucursal</th>
                            <th>@sortablelink('product_name', 'Nombre')</th>
                            <th>@sortablelink('category.name', 'Categoria')</th>
                            <th>@sortablelink('supplier.name', 'Proveedor')</th>
                            <th>@sortablelink('selling_price', 'Precio')</th>
                            <th>@sortablelink('stock_quantity', 'Stock')</th>
                            <th>Tienda</th>
                            <th>Etiquetas</th>
                        </tr>
                    </thead>
                    <tbody class="ligth-body">
                        @forelse ($products as $product)
                        <tr>
                            <td>{{ ($products->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <img class="avatar-60 rounded" src="{{ $product->product_image ? asset('storage/products/'.$product->product_image) : asset('assets/images/product/default.webp') }}">
                            </td>
                            <td><span class="badge badge-light border">{{ $product->branch?->name ?? 'Sin sucursal' }}</span></td>
                            <td>
                                <strong>{{ $product->product_name }}</strong>
                                @if($product->product_code)
                                    <div class="small text-muted">SKU: {{ $product->product_code }}</div>
                                @endif
                            </td>
                            <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                            <td>{{ $product->supplier?->name ?? 'Sin proveedor' }}</td>
                            <td>${{ number_format((float) $product->selling_price, 2) }}</td>
                            <td>
                                <span class="badge {{ $product->stock_quantity > 0 ? 'badge-success' : 'badge-danger' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                            <td>
                                <span class="btn btn-warning btn-sm text-white">{{ $product->product_store ?: '-' }}</span>
                            </td>
                            <td>
                                @if (auth()->user()?->can('product.read') || auth()->user()?->can('product.menu'))
                                    <a href="{{ route('products.barcodeLabels', ['product' => $product->id, 'mode' => 'stock']) }}"
                                       class="btn btn-primary btn-sm"
                                       title="Imprimir etiquetas según stock">
                                        <i class="fa-solid fa-barcode"></i>
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="alert text-white bg-danger mb-0" role="alert">
                                    <div class="iq-alert-text">No se encontraron productos en stock para esta consulta.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
