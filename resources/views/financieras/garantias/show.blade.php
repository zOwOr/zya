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
                        <h4 class="mb-0 font-weight-bold mr-3">Garantía {{ $warranty->warranty_code }}</h4>
                        <span class="badge text-white font-size-14 px-3 py-1" style="background-color: {{ $warranty->currentStage?->color ?? '#6c757d' }};">
                            {{ $warranty->currentStage?->name }}
                        </span>
                    </div>
                    <p class="text-muted mb-0 font-size-13 mt-1">
                        Abierta el {{ $warranty->opened_at->format('d/m/Y H:i') }} | Sucursal: <strong>{{ $warranty->branch?->name }}</strong>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}" class="btn btn-outline-secondary btn-sm mr-1">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Garantías
                    </a>
                    @if ($warranty->sale)
                        <a href="{{ route('financieras.ventas.show', $warranty->sale->id) }}" class="btn btn-primary btn-sm mr-1">
                            <i class="fa-solid fa-file-invoice-dollar mr-1"></i>Ver Venta ({{ $warranty->sale->sale_code }})
                        </a>
                    @endif
                    <a href="{{ route('financieras.inventario.history', $warranty->device_id) }}" class="btn btn-outline-primary btn-sm mr-1">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i>Historial IMEI
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Columna Izquierda: Información y Avance de Etapa -->
                <div class="col-lg-7">
                    <!-- Tarjeta de Falla y Resolución -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-dark">
                                <i class="fa-solid fa-circle-exclamation mr-2 text-warning"></i>Descripción del Problema
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="font-size-15 text-dark mb-3" style="white-space: pre-wrap;">{{ $warranty->issue_description }}</p>

                            @if ($warranty->resolution_notes)
                                <div class="alert alert-success border-0 mb-0">
                                    <h6 class="font-weight-bold text-success mb-1">
                                        <i class="fa-solid fa-check-double mr-1"></i>Notas de Resolución / Diagnóstico
                                    </h6>
                                    <p class="mb-0 font-size-14">{{ $warranty->resolution_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Formulario para Cambiar de Etapa -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0 text-white font-weight-bold">
                                <i class="fa-solid fa-forward-step mr-2"></i>Actualizar Etapa del Servicio
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('financieras.garantias.stage.update', $warranty->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold">Nueva Etapa</label>
                                        <select name="to_stage_id" class="form-control" required>
                                            @foreach ($stages as $s)
                                                <option value="{{ $s->id }}" {{ $warranty->current_stage_id == $s->id ? 'selected' : '' }}>
                                                    {{ $s->name }} {{ $s->is_final ? '(Etapa Final)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold">Estado del Caso</label>
                                        <select name="status" class="form-control">
                                            <option value="en_proceso" {{ $warranty->status == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                                            <option value="resuelta" {{ $warranty->status == 'resuelta' ? 'selected' : '' }}>Resuelta / Cerrada</option>
                                            <option value="rechazada" {{ $warranty->status == 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="font-weight-bold">Notas de la Etapa / Diagnóstico</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Describa el trabajo realizado, refacciones usadas o estado del equipo..."></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="font-size-12 text-muted">Si se concluye la garantía, devolver dispositivo a:</label>
                                        <select name="return_device_status" class="form-control form-control-sm">
                                            <option value="{{ $warranty->sale_id ? 'vendido' : 'disponible' }}">
                                                {{ $warranty->sale_id ? 'Vendido (Entregar a cliente)' : 'Disponible (Regresar a almacén)' }}
                                            </option>
                                            <option value="disponible">Disponible</option>
                                            <option value="vendido">Vendido</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end mb-3">
                                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                                            <i class="fa-solid fa-save mr-1"></i>Guardar Avance de Etapa
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Bitácora de Movimientos (Logs) -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-dark">
                                <i class="fa-solid fa-timeline mr-2 text-primary"></i>Historial de Etapas y Movimientos
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($warranty->logs as $log)
                                <div class="border-left border-warning pl-3 pb-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-bold font-size-14">
                                            @if ($log->fromStage)
                                                <span class="text-muted">{{ $log->fromStage->name }}</span>
                                                <i class="fa-solid fa-arrow-right text-muted mx-1"></i>
                                            @endif
                                            <span class="text-dark">{{ $log->toStage?->name }}</span>
                                        </span>
                                        <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <small class="text-muted d-block mb-1">Registrado por: {{ $log->user?->name ?? 'Sistema' }}</small>
                                    <p class="mb-0 font-size-13 text-secondary" style="white-space: pre-wrap;">{{ $log->notes }}</p>
                                </div>
                            @empty
                                <p class="text-muted font-size-13 mb-0 text-center py-3">Sin movimientos registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen del Equipo y Cliente -->
                <div class="col-lg-5">
                    <!-- Dispositivo -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-mobile-screen mr-2"></i>Dispositivo en Garantía
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush font-size-14">
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">IMEI</span>
                                    <span class="font-weight-bold font-size-16">{{ $warranty->device?->imei ?? 'N/A' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Marca y Modelo</span>
                                    <span class="font-weight-bold">{{ $warranty->device?->brand?->name }} {{ $warranty->device?->model }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Color</span>
                                    <span class="font-weight-bold">{{ $warranty->device?->color ?: 'No especificado' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Venta y Cliente (si existe) -->
                    @if ($warranty->sale)
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold mb-0 text-success">
                                    <i class="fa-solid fa-user-check mr-2"></i>Cliente y Venta Vinculada
                                </h6>
                                <a href="{{ route('financieras.ventas.show', $warranty->sale->id) }}" class="btn btn-xs btn-outline-success">
                                    <i class="fa-solid fa-eye mr-1"></i>Ver Venta
                                </a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush font-size-14">
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Folio de Venta</span>
                                        <span class="font-weight-bold text-success">{{ $warranty->sale->sale_code }}</span>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Cliente</span>
                                        <span class="font-weight-bold">{{ $warranty->sale->customer_name ?: 'Sin registrar' }}</span>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Teléfono</span>
                                        <a href="tel:{{ $warranty->sale->customer_phone }}" class="font-weight-bold text-primary">
                                            {{ $warranty->sale->customer_phone }}
                                        </a>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Financiera</span>
                                        <span class="badge badge-light border">{{ $warranty->sale->financiera?->name ?? 'Directo' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
