@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <div class="d-flex align-items-center">
                        <h4 class="mb-0 font-weight-bold mr-3">Trazabilidad del IMEI: {{ $device->imei }}</h4>
                        @switch($device->status)
                            @case('disponible')
                                <span class="badge badge-success font-size-14 px-3 py-1">DISPONIBLE</span>
                                @break
                            @case('vendido')
                                <span class="badge badge-secondary font-size-14 px-3 py-1">VENDIDO</span>
                                @break
                            @case('en_garantia')
                                <span class="badge badge-warning text-white font-size-14 px-3 py-1">EN GARANTÍA</span>
                                @break
                            @case('robado')
                                <span class="badge badge-danger font-size-14 px-3 py-1">ROBADO</span>
                                @break
                        @endswitch
                    </div>
                    <p class="text-muted mb-0 font-size-13 mt-1">
                        {{ $device->brand?->name }} {{ $device->model }}
                        @if($device->storage) | Capacidad: <strong>{{ $device->storage }}</strong> @endif
                        @if($device->color) | Color: <strong>{{ $device->color }}</strong> @endif
                        | Sucursal Actual: <strong>{{ $device->branch?->name }}</strong>
                        @if($device->supplier) | Proveedor: <strong>{{ $device->supplier->name }}</strong> @endif
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-outline-secondary btn-sm mr-1">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Inventario
                    </a>
                    @if ($device->latestSale)
                        <a href="{{ route('financieras.ventas.show', $device->latestSale->id) }}" class="btn btn-primary btn-sm mr-1">
                            <i class="fa-solid fa-file-invoice-dollar mr-1"></i>Ver Venta ({{ $device->latestSale->sale_code }})
                        </a>
                    @endif
                    <a href="{{ route('financieras.garantias.create', ['imei' => $device->imei]) }}" class="btn btn-outline-warning btn-sm mr-1">
                        <i class="fa-solid fa-wrench mr-1"></i>Garantía
                    </a>
                    <a href="{{ route('financieras.robos.create', ['imei' => $device->imei]) }}" class="btn btn-outline-danger btn-sm mr-1">
                        <i class="fa-solid fa-shield-halved mr-1"></i>Reportar Robo
                    </a>
                    @if (auth()->user()->can('financieras.inventario.edit'))
                        <a href="{{ route('financieras.inventario.edit', $device->id) }}" class="btn btn-light border btn-sm">
                            <i class="fa-solid fa-pen mr-1"></i>Editar
                        </a>
                    @endif
                </div>
            </div>

            <!-- Fichas de Historial -->
            <div class="row">
                <!-- 1. Traspasos entre sucursales -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-right-left mr-2"></i>Historial de Traspasos ({{ $device->transfers->count() }})
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($device->transfers as $tr)
                                <div class="border-left border-primary pl-3 pb-3 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="font-weight-bold font-size-14">
                                            {{ $tr->fromBranch?->name }} <i class="fa-solid fa-arrow-right text-muted mx-1"></i> {{ $tr->toBranch?->name }}
                                        </span>
                                        <small class="text-muted">{{ $tr->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <small class="text-muted d-block">Por: {{ $tr->user?->name ?? 'Sistema' }}</small>
                                    @if ($tr->notes)
                                        <p class="mb-0 font-size-13 text-secondary mt-1"><em>"{{ $tr->notes }}"</em></p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted font-size-13 mb-0 text-center py-4">No se han registrado traspasos para este equipo.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 2. Ventas vinculadas -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-success">
                                <i class="fa-solid fa-cart-shopping mr-2"></i>Registro de Ventas ({{ $device->sales->count() }})
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($device->sales as $s)
                                <div class="border rounded p-3 mb-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="{{ route('financieras.ventas.show', $s->id) }}" class="font-weight-bold text-success font-size-15">
                                            {{ $s->sale_code }}
                                        </a>
                                        @if ($s->status === 'activa')
                                            <span class="badge badge-success">Activa</span>
                                        @else
                                            <span class="badge badge-danger">Cancelada</span>
                                        @endif
                                    </div>
                                    <div class="font-size-13 text-dark">
                                        <strong>Cliente:</strong> {{ $s->customer_name ?: 'Sin registrar' }} ({{ $s->customer_phone }})<br>
                                        <strong>Financiera:</strong> {{ $s->financiera?->name ?? 'Directo' }} | <strong>Vendedor:</strong> {{ $s->seller?->name }}<br>
                                        <strong>Monto:</strong> ${{ number_format($s->price, 2) }} | <strong>Enganche:</strong> ${{ number_format($s->down_payment, 2) }}
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('financieras.ventas.show', $s->id) }}" class="btn btn-xs btn-outline-primary">
                                            <i class="fa-solid fa-eye mr-1"></i>Ver Detalle Completo
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted font-size-13 mb-0 text-center py-4">Este equipo no tiene ventas asociadas.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 3. Garantías vinculadas -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-warning">
                                <i class="fa-solid fa-wrench mr-2"></i>Garantías Reportadas ({{ $device->warranties->count() }})
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($device->warranties as $w)
                                <div class="border rounded p-3 mb-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="{{ route('financieras.garantias.show', $w->id) }}" class="font-weight-bold text-warning font-size-15">
                                            {{ $w->warranty_code }}
                                        </a>
                                        <span class="badge badge-light border">{{ ucfirst($w->status) }}</span>
                                    </div>
                                    <div class="font-size-13 text-dark">
                                        <strong>Etapa Actual:</strong> <span class="badge badge-secondary">{{ $w->currentStage?->name }}</span><br>
                                        <strong>Falla:</strong> {{ $w->issue_description }}<br>
                                        <small class="text-muted">Abierta el {{ $w->opened_at->format('d/m/Y') }}</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('financieras.garantias.show', $w->id) }}" class="btn btn-xs btn-outline-warning">
                                            <i class="fa-solid fa-eye mr-1"></i>Ver Garantía
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted font-size-13 mb-0 text-center py-4">Sin reportes de garantía para este IMEI.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 4. Reportes de Robo -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-danger">
                                <i class="fa-solid fa-shield-halved mr-2"></i>Reportes de Robo ({{ $device->theftReports->count() }})
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($device->theftReports as $tr)
                                <div class="border rounded p-3 mb-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="{{ route('financieras.robos.show', $tr->id) }}" class="font-weight-bold text-danger font-size-15">
                                            {{ $tr->report_code }}
                                        </a>
                                        <span class="badge badge-danger">{{ ucfirst($tr->status) }}</span>
                                    </div>
                                    <div class="font-size-13 text-dark">
                                        <strong>Acta / Folio:</strong> {{ $tr->police_report_number ?: 'Sin acta' }}<br>
                                        <strong>Fecha Incidente:</strong> {{ $tr->incident_date ? $tr->incident_date->format('d/m/Y') : '' }}<br>
                                        <strong>Descripción:</strong> {{ $tr->description }}
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('financieras.robos.show', $tr->id) }}" class="btn btn-xs btn-outline-danger">
                                            <i class="fa-solid fa-eye mr-1"></i>Ver Reporte de Robo
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted font-size-13 mb-0 text-center py-4">Sin reportes de robo registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
