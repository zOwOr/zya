@extends('dashboard.body.main')

@section('container')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                @if (session()->has('success'))
                    <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                        <div class="iq-alert-text">{{ session('success') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                        <div class="iq-alert-text">{{ session('error') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                @endif
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4" style="gap: 12px;">
                    <div>
                        <h4 class="mb-2">Lista de productos</h4>
                        <p class="mb-0 text-muted">Administra el catálogo de productos por sucursal, existencias, precios e importación masiva.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                        @can('product.import')
                            <a href="{{ route('products.downloadTemplate') }}" class="btn btn-outline-success">
                                <i class="fa-solid fa-file-arrow-down mr-1"></i> Plantilla Excel
                            </a>
                            <a href="{{ route('products.importView') }}" class="btn btn-success add-list">
                                <i class="fa-solid fa-file-import mr-1"></i> Importar
                            </a>
                        @endcan
                        @can('product.export')
                            <a href="{{ route('products.exportData', array_filter(request()->only(['search', 'branch_id', 'category_id']))) }}" class="btn btn-warning add-list">
                                <i class="fa-solid fa-file-excel mr-1"></i> Exportar
                            </a>
                        @endcan
                        @can('product.create')
                            <a href="{{ route('products.create') }}" class="btn btn-primary add-list">
                                <i class="fa-solid fa-plus mr-1"></i> Nuevo Producto
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <form action="{{ route('products.index') }}" method="get">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 12px;">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 12px;">
                            <div class="form-group mb-0 d-flex align-items-center" style="gap: 8px;">
                                <label for="row" class="mb-0 font-weight-bold">Filas:</label>
                                <select class="form-control" name="row" id="row" onchange="this.form.submit()" style="min-width: 90px;">
                                    <option value="10" @if (request('row') == '10') selected @endif>10</option>
                                    <option value="25" @if (request('row') == '25') selected @endif>25</option>
                                    <option value="50" @if (request('row') == '50') selected @endif>50</option>
                                    <option value="100" @if (request('row') == '100') selected @endif>100</option>
                                    <option value="100000" @if (request('row') == '100000') selected @endif>Todos</option>
                                </select>
                            </div>

                            @if ($canAllBranches)
                                <div class="form-group mb-0 d-flex align-items-center" style="gap: 8px;">
                                    <label for="branch_id" class="mb-0 font-weight-bold">Sucursal:</label>
                                    <select class="form-control" name="branch_id" id="branch_id" onchange="this.form.submit()" style="min-width: 185px;">
                                        <option value="">Todas las sucursales</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" @if ((string) $selectedBranchId === (string) $branch->id) selected @endif>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif ($branches->isNotEmpty())
                                <span class="badge badge-info p-2">
                                    <i class="fa-solid fa-store mr-1"></i> Sucursal: {{ $branches->first()->name }}
                                </span>
                            @endif

                            <div class="form-group mb-0 d-flex align-items-center" style="gap: 8px;">
                                <label for="category_id" class="mb-0 font-weight-bold">Categoría:</label>
                                <select class="form-control" name="category_id" id="category_id" onchange="this.form.submit()" style="min-width: 170px;">
                                    <option value="">Todas</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @if ((string) request('category_id') === (string) $category->id) selected @endif>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-0" style="min-width: 280px;">
                            <div class="input-group">
                                <input type="text" id="search" class="form-control" name="search"
                                    placeholder="Nombre, código, IMEI o marca..." value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="input-group-text bg-primary text-white" title="Buscar">
                                        <i class="fa-solid fa-magnifying-glass font-size-20"></i>
                                    </button>
                                    <a href="{{ route('products.index') }}" class="input-group-text bg-danger text-white" title="Limpiar filtros">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-12">
                <div class="table-responsive rounded mb-3">
                    <table class="table mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('product.export') ? 'true' : 'false' }}">
                        <thead class="bg-white text-uppercase">
                            <tr class="ligth ligth-data">
                                <th>No.</th>
                                <th>Foto</th>
                                <th>Sucursal</th>
                                <th>Código / IMEI</th>
                                <th>@sortablelink('product_name', 'Nombre')</th>
                                <th>@sortablelink('category.name', 'Categoría')</th>
                                <th>@sortablelink('supplier.name', 'Proveedor')</th>
                                <th>@sortablelink('selling_price', 'Precio')</th>
                                <th>@sortablelink('stock_quantity', 'Stock')</th>
                                <th>Estatus</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody class="ligth-body">
                            @forelse ($products as $product)
                                <tr>
                                    <td>{{ ($products->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>
                                        <img class="avatar-60 rounded"
                                            src="{{ $product->product_image ? asset('storage/products/' . $product->product_image) : asset('assets/images/product/default.webp') }}">
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">{{ $product->branch?->name ?? 'Sin sucursal' }}</span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $product->product_code ?: '—' }}</div>
                                        @if ($product->imei)
                                            <div class="small text-muted">IMEI: {{ $product->imei }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $product->product_name }}</div>
                                        @if ($product->brand || $product->model)
                                            <div class="small text-muted">{{ trim(($product->brand ?? '') . ' ' . ($product->model ?? '')) }}</div>
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
                                        @if (empty($product->expire_date))
                                            <span class="badge rounded-pill bg-info text-white">Vigente</span>
                                        @elseif ($product->expire_date >= now()->format('Y-m-d'))
                                            <span class="badge rounded-pill bg-success">Válido</span>
                                        @else
                                            <span class="badge rounded-pill bg-danger">Expirado</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                            style="margin-bottom: 5px" id="deleteForm-{{ $product->id }}">
                                            @method('delete')
                                            @csrf
                                            <div class="d-flex align-items-center list-action">
                                                <a class="btn btn-info mr-2" data-toggle="tooltip" data-placement="top"
                                                    title="Ver" href="{{ route('products.show', $product->id) }}"><i
                                                        class="ri-eye-line mr-0"></i>
                                                </a>
                                                <a class="btn btn-primary mr-2" data-toggle="tooltip" data-placement="top"
                                                    title="Imprimir Etiquetas" href="{{ route('products.barcodeLabels', $product->id) }}"><i
                                                        class="fa-solid fa-barcode mr-0"></i>
                                                </a>
                                                @can('product.edit')
                                                    <a class="btn btn-success mr-2" data-toggle="tooltip"
                                                        data-placement="top" title="Editar"
                                                        href="{{ route('products.edit', $product->id) }}"><i
                                                            class="ri-pencil-line mr-0"></i>
                                                    </a>
                                                @endcan
                                                @can('product.delete')
                                                    <button type="button" class="btn btn-warning mr-2 border-0"
                                                        onclick="confirmDelete('{{ addslashes($product->product_name) }}', '{{ $product->id }}')"
                                                        data-toggle="tooltip" data-placement="top" title="Eliminar">
                                                        <i class="ri-delete-bin-line mr-0"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="alert text-white bg-danger mb-0" role="alert">
                                            <div class="iq-alert-text">No se encontraron productos para esta consulta.</div>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(productName, productId) {
            Swal.fire({
                title: "¿Deseas eliminar este producto?",
                text: `¿Estás seguro de que quieres borrar el producto "${productName}"?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Confirmar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("deleteForm-" + productId).submit();
                }
            });
        }
    </script>
@endsection
