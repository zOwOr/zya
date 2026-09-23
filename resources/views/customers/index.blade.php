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
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-3">Lista de Clientes</h4>
                        <p class="mb-0">Un Dashboard de clientes le permite recopilar y visualizar fácilmente los datos de
                            los clientes a partir de la optimización de <br>
                            la experiencia del cliente, garantizando su retención. </p>
                    </div>
                    <div>
                        @can('customer.export')
                        <a href="{{ route('customers.exportData') }}" class="btn btn-success add-list mr-2"><i
                                class="fa-solid fa-file-excel mr-2"></i>Exportar Excel</a>
                        @endcan
                        @can('customer.create')
                        <a href="{{ route('customers.create') }}" class="btn btn-primary add-list"><i
                                class="fa-solid fa-plus mr-3"></i>Nuevo Cliente</a>
                        @endcan
                        <a href="{{ route('customers.index') }}" class="btn btn-danger add-list"><i
                                class="fa-solid fa-trash mr-3"></i>Limpiar Busqueda</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <form action="{{ route('customers.index') }}" method="get">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="form-group row">
                            <label for="row" class="col-sm-3 align-self-center">Row:</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="row" onchange="this.form.submit()">
                                    <option value="10" @if (request('row') == '10') selected="selected" @endif>10
                                    </option>
                                    <option value="25" @if (request('row') == '25') selected="selected" @endif>25
                                    </option>
                                    <option value="50" @if (request('row') == '50') selected="selected" @endif>50
                                    </option>
                                    <option value="100" @if (request('row') == '100') selected="selected" @endif>100
                                    </option>
                                    <option value="100000" @if(request('row') == '100000') selected="selected" @endif>Todos</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="control-label col-sm-3 align-self-center" for="search">Buscar:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" id="search" class="form-control" name="search"
                                        placeholder="Buscar cliente" value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="input-group-text bg-primary"><i
                                                class="fa-solid fa-magnifying-glass font-size-20"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-12">
                <div class="table-responsive rounded mb-3">
                    <table class="table mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('customer.export') ? 'true' : 'false' }}">
                        <thead class="bg-white text-uppercase">
                            <tr class="ligth ligth-data">
                                <th>No.</th>
                                <th>Foto</th>
                                <th>@sortablelink('Nombre')</th>
                                <th>@sortablelink('Email')</th>
                                <th>@sortablelink('Teléfono')</th>
                                <th>@sortablelink('Facebook')</th>
                                @canany(['customer.read', 'customer.edit', 'customer.delete'])
                                <th>Acción</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody class="ligth-body">
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>{{ $customers->currentPage() * 10 - 10 + $loop->iteration }}</td>
                                    <td>
                                        <img class="avatar-50 rounded"
                                            src="{{ $customer->tit_photo ? asset('storage/customers/' . $customer->tit_photo) : asset('assets/images/user/1.png') }}">
                                    </td>
                                    <td>{{ $customer->tit_name }}</td>
                                    <td>{{ $customer->tit_email }}</td>
                                    <td>{{ $customer->tit_phone }}</td>
                                    <td>{{ $customer->tit_facebook }}</td>
                                    @canany(['customer.read', 'customer.edit', 'customer.delete'])
                                    <td>
                                        <div class="d-flex align-items-center list-action">
                                            @can('customer.read')
                                            <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top"
                                                title="" data-original-title="Ver"
                                                href="{{ route('customers.show', $customer->id) }}"><i
                                                    class="ri-eye-line mr-0"></i>
                                            </a>
                                            @endcan

                                            @can('customer.edit')
                                            <a class="badge bg-success mr-2" data-toggle="tooltip" data-placement="top"
                                                title="" data-original-title="Editar"
                                                href="{{ route('customers.edit', $customer->id) }}"><i
                                                    class="ri-pencil-line mr-0"></i>
                                            </a>
                                            @endcan

                                            @can('customer.delete')
                                            <form action="{{ route('customers.destroy', $customer->id) }}"
                                                method="POST" style="margin-bottom: 5px">
                                                @method('delete')
                                                @csrf
                                                <button type="submit" class="badge bg-warning mr-2 border-none"
                                                    onclick="return confirm('¿Estás seguro de eliminar este registro?')"
                                                    data-toggle="tooltip" data-placement="top" title=""
                                                    data-original-title="Eliminar"><i
                                                        class="ri-delete-bin-line mr-0"></i></button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                    @endcanany
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $customers->links() }}
            </div>
        </div>
        <!-- Page end  -->
    </div>
@endsection

