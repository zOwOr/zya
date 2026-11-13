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
            @if (session()->has('info'))
                <div class="alert text-white bg-info" role="alert">
                    <div class="iq-alert-text">{{ session('info') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1"><i class="fa-solid fa-user-tag text-warning mr-2"></i>Catálogo de Clientes Externos</h4>
                    <p class="mb-0 text-muted">
                        Personas externas que acuden a sucursal a pagar financieras (PayJoy, Credimás, Creditienda, etc.) por equipos de otras empresas, con trazabilidad en caja y promoción bajo demanda al padrón interno.
                    </p>
                </div>
                <div class="d-flex align-items-center mt-2 mt-md-0" style="gap: 8px;">
                    @can('external_customer.create')
                        <a href="{{ route('external-customers.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus mr-2"></i>Nuevo Cliente Externo
                        </a>
                    @endcan
                    <a href="{{ route('external-customers.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-rotate mr-1"></i>Limpiar
                    </a>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <div class="card border-left-primary shadow-sm h-100 py-1">
                        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase">Total Registrados</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['total'] }}</div>
                            </div>
                            <i class="fa-solid fa-users-viewfinder fa-lg text-muted"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="card border-left-warning shadow-sm h-100 py-1">
                        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase">Externos Activos</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['active'] }}</div>
                            </div>
                            <i class="fa-solid fa-user-clock fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="card border-left-success shadow-sm h-100 py-1">
                        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase">Promovidos a Internos</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['converted'] }}</div>
                            </div>
                            <i class="fa-solid fa-user-check fa-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <form action="{{ route('external-customers.index') }}" method="get" class="card mb-3 shadow-none border">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-2 form-group mb-2 mb-md-0">
                            <label class="small font-weight-bold text-muted mb-1">Filas</label>
                            <select class="form-control form-control-sm" name="row" onchange="this.form.submit()">
                                @foreach ([15, 25, 50, 100] as $r)
                                    <option value="{{ $r }}" {{ (int) request('row', 15) === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-2 mb-md-0">
                            <label class="small font-weight-bold text-muted mb-1">Estado</label>
                            <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                                <option value="">-- Todos --</option>
                                <option value="activo" {{ request('status') === 'activo' ? 'selected' : '' }}>Activos (Externos)</option>
                                <option value="convertido" {{ request('status') === 'convertido' ? 'selected' : '' }}>Promovidos a Interno</option>
                                <option value="inactivo" {{ request('status') === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-7 form-group mb-0">
                            <label class="small font-weight-bold text-muted mb-1">Buscar por Nombre / Teléfono / Email / Ciudad</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Escribe nombre, teléfono o ciudad...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary font-weight-bold">
                                        <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-uppercase font-size-12">
                                <tr>
                                    <th>#</th>
                                    <th>Titular Externo</th>
                                    <th>Contacto / Ciudad</th>
                                    <th>Sucursal Alta</th>
                                    <th class="text-center">Movimientos POS</th>
                                    <th class="text-right">Total Cobrado</th>
                                    <th class="text-center">Estado / Promoción</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($externalCustomers as $ext)
                                    <tr>
                                        <td class="align-middle text-muted font-weight-bold">#{{ $ext->id }}</td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold text-dark">{{ $ext->tit_name }}</div>
                                            <small class="text-muted">FB: {{ $ext->tit_facebook }} | Estatus: {{ ucfirst($ext->tit_status) }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold">{{ $ext->tit_phone }}</div>
                                            <small class="text-muted d-block">{{ $ext->tit_email }}</small>
                                            <small class="text-muted">{{ $ext->tit_city }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-light border">{{ $ext->branch?->name ?? 'General' }}</span>
                                            <small class="d-block text-muted mt-1">{{ optional($ext->created_at)->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-info font-size-12">{{ $ext->pos_transactions_count }} POS</span>
                                            <small class="d-block text-muted">{{ $ext->financiera_payments_count }} fin. / {{ $ext->cash_flows_count }} caja</small>
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-success">
                                            ${{ number_format((float) ($ext->total_paid_pos ?? 0), 2) }}
                                        </td>
                                        <td class="align-middle text-center">
                                            @if ($ext->isConverted())
                                                <span class="badge badge-success px-2 py-1">
                                                    <i class="fa-solid fa-user-check mr-1"></i>Convertido a Interno
                                                </span>
                                                @if ($ext->converted_customer_id)
                                                    <small class="d-block mt-1">
                                                        @can('customer.read')
                                                            <a href="{{ route('customers.show', $ext->converted_customer_id) }}" class="font-weight-bold text-success">
                                                                Ver Cliente #{{ $ext->converted_customer_id }}
                                                            </a>
                                                        @else
                                                            Cliente Interno #{{ $ext->converted_customer_id }}
                                                        @endcan
                                                    </small>
                                                @endif
                                            @elseif ($ext->status === 'inactivo')
                                                <span class="badge badge-secondary px-2 py-1">Inactivo</span>
                                            @else
                                                <span class="badge badge-warning text-dark px-2 py-1">
                                                    <i class="fa-solid fa-user-tag mr-1"></i>Externo Activo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-right text-nowrap">
                                            <div class="btn-group btn-group-sm">
                                                @can('external_customer.read')
                                                    <a href="{{ route('external-customers.show', $ext) }}" class="btn btn-outline-info" title="Ver Expediente e Historial">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('external_customer.edit')
                                                    <a href="{{ route('external-customers.edit', $ext) }}" class="btn btn-outline-success" title="Editar">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                @endcan
                                                @can('external_customer.convert')
                                                    @if (!$ext->isConverted())
                                                        <form action="{{ route('external-customers.convert', $ext) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('¿Convertir a {{ addslashes($ext->tit_name) }} en Cliente Interno del padrón principal? Se conservará íntegro su historial previo como externo.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary" title="Convertir / Habilitar como Cliente Interno">
                                                                <i class="fa-solid fa-user-plus"></i> Convertir
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                                @can('external_customer.delete')
                                                    <form action="{{ route('external-customers.destroy', $ext) }}" method="POST" class="d-inline"
                                                        onsubmit="return confirm('¿Eliminar este registro de cliente externo? Sus movimientos previos en caja y cortes permanecerán auditables.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No hay clientes externos registrados con los filtros actuales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($externalCustomers->hasPages())
                    <div class="card-footer bg-white">
                        {{ $externalCustomers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

