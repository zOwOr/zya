@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <h4 class="card-title">Código de barras</h4>
                    </div>
                    <div class="d-flex" style="gap: 8px;">
                        <a href="{{ route('products.barcodeLabels', $product->id) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-barcode mr-1"></i> Imprimir Etiquetas
                        </a>
                        @can('product.edit')
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-success btn-sm">Editar</a>
                        @endcan
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="form-group col-md-4">
                            <label>Código del Producto</label>
                            <input type="text" class="form-control bg-white font-weight-bold" value="{{ $barcodeCode ?? ($product->product_code ?: '—') }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Sucursal</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->branch?->name ?? 'Sin sucursal' }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Código de barras (Code-128)</label>
                            <div class="p-2 border rounded bg-white text-center overflow-auto">
                                {!! $barcodeSvg ?? $barcode !!}
                                <div class="small font-weight-bold text-dark mt-1">{{ $barcodeCode ?? $product->product_code }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Información del Producto</h4>
                    </div>
                </div>

                <div class="card-body">
                    <div class="form-group row align-items-center">
                        <div class="col-md-12">
                            <div class="profile-img-edit">
                                <div class="crm-profile-img-edit">
                                    <img class="crm-profile-pic rounded-circle avatar-100" id="image-preview" src="{{ $product->product_image ? asset('storage/products/'.$product->product_image) : asset('assets/images/product/default.webp') }}" alt="profile-pic">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="form-group col-md-8">
                            <label>Nombre del Producto</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->product_name }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Stock</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->stock_quantity }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>IMEI / Serie</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->imei ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Categoría</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->category?->name ?? 'Sin categoría' }}" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Proveedor</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->supplier?->name ?? 'Sin proveedor' }}" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Marca</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->brand ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Modelo</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->model ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Condición</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->category_status ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Garantía</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->warranty_time ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Almacén / Pasillo</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->product_garage ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sección / Tienda</label>
                            <input type="text" class="form-control bg-white" value="{{ $product->product_store ?: '—' }}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Fecha de Compra</label>
                            <input class="form-control bg-white" value="{{ $product->buying_date ?: '—' }}" readonly/>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Fecha de Expiración</label>
                            <input class="form-control bg-white" value="{{ $product->expire_date ?: '—' }}" readonly />
                        </div>
                        <div class="form-group col-md-6">
                            <label>Precio de Compra</label>
                            <input type="text" class="form-control bg-white" value="${{ number_format((float) $product->buying_price, 2) }}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Precio de Venta</label>
                            <input type="text" class="form-control bg-white" value="${{ number_format((float) $product->selling_price, 2) }}" readonly>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Observaciones</label>
                            <textarea class="form-control bg-white" rows="2" readonly>{{ $product->observations ?: '—' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
