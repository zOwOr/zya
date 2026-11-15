@extends('dashboard.body.main')

@section('specificpagestyles')
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>
    <link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Editar Producto</h4>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                        <div class="form-group row align-items-center">
                            <div class="col-md-12">
                                <div class="profile-img-edit">
                                    <div class="crm-profile-img-edit">
                                        <img class="crm-profile-pic rounded-circle avatar-100" id="image-preview" src="{{ $product->product_image ? asset('storage/products/'.$product->product_image) : asset('assets/images/product/default.webp') }}" alt="profile-pic">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-group mb-4 col-lg-6">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('product_image') is-invalid @enderror" id="image" name="product_image" accept=".pdf, image/*" onchange="previewImage();">
                                    <label class="custom-file-label" for="image">Elegir imagen</label>
                                </div>
                                @error('product_image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="form-group col-md-8">
                                <label for="product_name">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('product_name') is-invalid @enderror" id="product_name" name="product_name" value="{{ old('product_name', $product->product_name) }}" required>
                                @error('product_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="product_code">Código / SKU</label>
                                <input type="text" class="form-control @error('product_code') is-invalid @enderror" id="product_code" name="product_code" value="{{ old('product_code', $product->product_code) }}">
                                @error('product_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="branch_id">Sucursal <span class="text-danger">*</span></label>
                                @if ($canAllBranches)
                                    <select class="form-control @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                        <option value="">-- Seleccionar Sucursal --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id', $product->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ $product->branch?->name ?? $userBranch?->name ?? 'Sin sucursal' }}" readonly>
                                    <input type="hidden" name="branch_id" value="{{ $product->branch_id ?? $userBranch?->id }}">
                                @endif
                                @error('branch_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="stock_quantity">Stock <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                                @error('stock_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="category_id">Categoría <span class="text-danger">*</span></label>
                                <select class="form-control select2-autocomplete @error('category_id') is-invalid @enderror" name="category_id" data-placeholder="-- Seleccionar Categoría --" required>
                                    <option value="">-- Seleccionar Categoría --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="supplier_id">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-control select2-autocomplete @error('supplier_id') is-invalid @enderror" name="supplier_id" data-placeholder="-- Seleccionar Proveedor --" required>
                                    <option value="">-- Seleccionar Proveedor --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="imei">IMEI / Serie</label>
                                <input type="text" class="form-control @error('imei') is-invalid @enderror" id="imei" name="imei" value="{{ old('imei', $product->imei) }}">
                                @error('imei')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="brand">Marca</label>
                                <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ old('brand', $product->brand) }}">
                                @error('brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="model">Modelo</label>
                                <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model', $product->model) }}">
                                @error('model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="category_status">Condición</label>
                                <input type="text" class="form-control @error('category_status') is-invalid @enderror" id="category_status" name="category_status" value="{{ old('category_status', $product->category_status) }}">
                                @error('category_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="warranty_time">Garantía</label>
                                <input type="text" class="form-control @error('warranty_time') is-invalid @enderror" id="warranty_time" name="warranty_time" value="{{ old('warranty_time', $product->warranty_time) }}">
                                @error('warranty_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="product_garage">Almacén / Pasillo</label>
                                <input type="text" class="form-control @error('product_garage') is-invalid @enderror" id="product_garage" name="product_garage" value="{{ old('product_garage', $product->product_garage) }}">
                                @error('product_garage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="product_store">Sección / Tienda</label>
                                <input type="text" class="form-control @error('product_store') is-invalid @enderror" id="product_store" name="product_store" value="{{ old('product_store', $product->product_store) }}">
                                @error('product_store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="buying_date">Fecha de Compra</label>
                                <input id="buying_date" class="form-control @error('buying_date') is-invalid @enderror" name="buying_date" value="{{ old('buying_date', $product->buying_date) }}" />
                                @error('buying_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="expire_date">Fecha de Expiración</label>
                                <input id="expire_date" class="form-control @error('expire_date') is-invalid @enderror" name="expire_date" value="{{ old('expire_date', $product->expire_date) }}" />
                                @error('expire_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="buying_price">Precio de Compra <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('buying_price') is-invalid @enderror" id="buying_price" name="buying_price" value="{{ old('buying_price', $product->buying_price) }}" required>
                                @error('buying_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="selling_price">Precio de Venta <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required>
                                @error('selling_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-12">
                                <label for="observations">Observaciones</label>
                                <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="2">{{ old('observations', $product->observations) }}</textarea>
                                @error('observations')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary mr-2">Guardar</button>
                            <a class="btn bg-danger" href="{{ route('products.index') }}">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#buying_date').datepicker({
        uiLibrary: 'bootstrap4',
        format: 'yyyy-mm-dd'
    });
    $('#expire_date').datepicker({
        uiLibrary: 'bootstrap4',
        format: 'yyyy-mm-dd'
    });
</script>
@include('components.preview-img-form')
@endsection
