@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold"><i class="fa-solid fa-piggy-bank text-primary mr-2"></i>Módulo de Tandas</h4>
                    <p class="mb-0 text-muted font-size-13">
                        Gestión ágil de tandas con registro directo de clientes participantes (desacoplado del módulo general de clientes).
                    </p>
                </div>
                <div class="d-flex align-items-center">
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('tandas.all_branches'))
                        <form method="GET" action="{{ route('tandas.index') }}" class="form-inline mr-2">
                            <select name="branch_id" class="form-control form-control-sm mr-1" onchange="this.form.submit()">
                                <option value="">Todas las Sucursales</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string)$branchId === (string)$branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    @if (auth()->user()->can('tandas.create'))
                        <a href="{{ route('tandas.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus mr-1"></i>Crear Nueva Tanda
                        </a>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('tandas.export') ? 'true' : 'false' }}">
                            <thead class="bg-light">
                                <tr>
                                    <th>Descripción / Nombre</th>
                                    <th>Sucursal</th>
                                    <th>Monto Total</th>
                                    <th>Frecuencia</th>
                                    <th>Aportación por Periodo</th>
                                    <th>Participantes</th>
                                    <th>Recaudado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tandas as $tanda)
                                    @php
                                        $totalCollected = $tanda->participants->sum('total_paid');
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold">
                                            <a href="{{ route('tandas.show', $tanda) }}" class="text-primary">{{ $tanda->description }}</a>
                                            <small class="d-block text-muted">Inicio: {{ $tanda->start_date ? $tanda->start_date->format('d/m/Y') : $tanda->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td>{{ $tanda->branch?->name ?? 'General' }}</td>
                                        <td class="font-weight-bold">${{ number_format($tanda->total_amount, 2) }}</td>
                                        <td><span class="badge badge-light border">{{ ucfirst($tanda->payment_period) }} ({{ $tanda->periods->count() }} periodos)</span></td>
                                        <td>${{ number_format($tanda->payment_amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $tanda->participants->count() }} participante(s)</span>
                                        </td>
                                        <td class="text-success font-weight-bold">${{ number_format($totalCollected, 2) }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('tandas.show', $tanda) }}" class="btn btn-sm btn-info mr-1">
                                                <i class="fa-solid fa-table-cells mr-1"></i>Tablero y Pagos
                                            </a>
                                            @if (auth()->user()->can('tandas.edit'))
                                                <a href="{{ route('tandas.edit', $tanda) }}" class="btn btn-sm btn-warning">
                                                    <i class="fa-solid fa-users-gear mr-1"></i>Participantes
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No hay tandas registradas en esta sucursal.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
