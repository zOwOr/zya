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
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-3">Lista de productos</h4>
                    <p class="mb-0">Un Dashboard de productos le permite recopilar y visualizar fácilmente los datos de los productos a partir de la optimización de <br>
                        la experiencia del producto, garantizando su retención. </p>
                </div>
                <div>
                <a href="{{ route('products.importView') }}" class="btn btn-success add-list">Importar</a>
                <a href="{{ route('products.exportData') }}" class="btn btn-warning add-list">Exportar</a>
                <a href="{{ route('products.create') }}" class="btn btn-primary add-list">Nuevo Producto</a>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <form action="{{ route('products.index') }}" method="get">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="form-group row">
                        <label for="row" class="col-sm-3 align-self-center">Row:</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="row">
                                <option value="10" @if(request('row') == '10')selected="selected"@endif>10</option>
                                <option value="25" @if(request('row') == '25')selected="selected"@endif>25</option>
                                <option value="50" @if(request('row') == '50')selected="selected"@endif>50</option>
                                <option value="100" @if(request('row') == '100')selected="selected"@endif>100</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-sm-3 align-self-center" for="search">Buscar:</label>
                        <div class="input-group col-sm-8">
                            <input type="text" id="search" class="form-control" name="search" placeholder="Buscar producto" value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="input-group-text bg-primary"><i class="fa-solid fa-magnifying-glass font-size-20"></i></button>
                                <a href="{{ route('products.index') }}" class="input-group-text bg-danger"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive rounded mb-3">
                <table class="table mb-0">
                    <thead class="bg-white text-uppercase">
                        <tr class="ligth ligth-data">
                            <th>No.</th>
                            <th>Foto</th>
                            <th>@sortablelink('product_name', 'Nombre')</th>
                            <th>@sortablelink('category.name', 'Categoria')</th>
                            <th>@sortablelink('supplier.name', 'Proveedor')</th>
                            <th>@sortablelink('selling_price', 'Precio')</th>
                            <th>Estatus</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody class="ligth-body">
                        @forelse ($products as $product)
                        <tr>
                            <td>{{ (($products->currentPage() * 10) - 10) + $loop->iteration  }}</td>
                            <td>
                                <img class="avatar-60 rounded" src="{{ $product->product_image ? asset('storage/products/'.$product->product_image) : asset('assets/images/product/default.webp') }}">
                            </td>
                            <td>{{ $product->product_name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>{{ $product->supplier->name }}</td>
                            <td>{{ $product->selling_price }}</td>
                            <td>
                                @if ($product->expire_date > Carbon\Carbon::now()->format('Y-m-d'))
                                    <span class="badge rounded-pill bg-success">Valido</span>
                                @else
                                    <span class="badge rounded-pill bg-danger">Invalido</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="margin-bottom: 5px" id="deleteForm-{{ $product->id }}">
                                    @method('delete')
                                    @csrf
                                    <div class="d-flex align-items-center list-action">
                                        <a class="btn btn-info mr-2" data-toggle="tooltip" data-placement="top" title="Ver"
                                            href="{{ route('products.show', $product->id) }}"><i class="ri-eye-line mr-0"></i>
                                        </a>
                                        <a class="btn btn-success mr-2" data-toggle="tooltip" data-placement="top" title="Editar"
                                            href="{{ route('products.edit', $product->id) }}"><i class="ri-pencil-line mr-0"></i>
                                        </a>
                                        <button type="button" class="btn btn-warning mr-2 border-0" 
                                            onclick="confirmDelete('{{ $product->product_name }}', '{{ $product->id }}')" 
                                            data-toggle="tooltip" data-placement="top" title="Eliminar">
                                            <i class="ri-delete-bin-line mr-0"></i>
                                        </button>

                                        
                                    </div>
                                </form>
                                
                            </td>
                        </tr>

                        @empty
                        <div class="alert text-white bg-danger" role="alert">
                            <div class="iq-alert-text">Registros no encontrados.</div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <i class="ri-close-line"></i>
                            </button>
                        </div>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
    <!-- Page end  -->
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmDelete(productName, productId) {
    Swal.fire({
        title: "¿Deseas eliminar este producto?",
        text: `¿Estás seguro de que quieres borrar el producto "${productName}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",  // Rojo (para la eliminación)
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Confirmar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            // Enviar el formulario para eliminar el producto
            document.getElementById("deleteForm-" + productId).submit();
        }
    });
}


</script>


@endsection
