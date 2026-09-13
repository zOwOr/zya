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
                        <h4 class="mb-0 font-weight-bold mr-3">Reporte de Robo {{ $theftReport->report_code }}</h4>
                        @switch($theftReport->status)
                            @case('reportado')
                                <span class="badge badge-danger font-size-14 px-3 py-1">REPORTADO</span>
                                @break
                            @case('en_investigacion')
                                <span class="badge badge-warning text-white font-size-14 px-3 py-1">EN INVESTIGACIÓN</span>
                                @break
                            @case('recuperado')
                                <span class="badge badge-success font-size-14 px-3 py-1">RECUPERADO</span>
                                @break
                            @case('cerrado')
                                <span class="badge badge-secondary font-size-14 px-3 py-1">CERRADO</span>
                                @break
                        @endswitch
                    </div>
                    <p class="text-muted mb-0 font-size-13 mt-1">
                        Incidente registrado el {{ $theftReport->incident_date ? $theftReport->incident_date->format('d/m/Y H:i') : '' }} | Sucursal: <strong>{{ $theftReport->branch?->name }}</strong>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('financieras.index', ['tab' => 'robos']) }}" class="btn btn-outline-secondary btn-sm mr-1">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Reportes de Robo
                    </a>
                    @if ($theftReport->sale)
                        <a href="{{ route('financieras.ventas.show', $theftReport->sale->id) }}" class="btn btn-primary btn-sm mr-1">
                            <i class="fa-solid fa-file-invoice-dollar mr-1"></i>Ver Venta ({{ $theftReport->sale->sale_code }})
                        </a>
                    @endif
                    <a href="{{ route('financieras.inventario.history', $theftReport->device_id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i>Historial IMEI
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Columna Izquierda: Información de Hechos y Actualización de Estado -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-danger">
                                <i class="fa-solid fa-file-shield mr-2"></i>Narrativa de los Hechos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="text-muted font-size-12 d-block">Número de Acta / Denuncia</span>
                                <span class="font-weight-bold font-size-16 text-dark">{{ $theftReport->police_report_number ?: 'Sin folio legal o acta capturada' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted font-size-12 d-block">Descripción del Incidente</span>
                                <p class="font-size-14 text-dark mb-0" style="white-space: pre-wrap;">{{ $theftReport->description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actualizar Estado -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-danger text-white py-2">
                            <h6 class="mb-0 text-white font-weight-bold">
                                <i class="fa-solid fa-rotate mr-2"></i>Actualizar Estado / Seguimiento Legal
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('financieras.robos.status.update', $theftReport->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold">Nuevo Estado del Reporte</label>
                                        <select name="status" id="theftStatusSelect" class="form-control" required>
                                            <option value="reportado" {{ $theftReport->status == 'reportado' ? 'selected' : '' }}>Reportado</option>
                                            <option value="en_investigacion" {{ $theftReport->status == 'en_investigacion' ? 'selected' : '' }}>En Investigación</option>
                                            <option value="recuperado" {{ $theftReport->status == 'recuperado' ? 'selected' : '' }}>Recuperado</option>
                                            <option value="cerrado" {{ $theftReport->status == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3" id="returnStatusDiv" style="display: {{ $theftReport->status == 'recuperado' ? 'block' : 'none' }};">
                                        <label class="font-size-12 text-muted">Si el equipo fue recuperado, cambiar a:</label>
                                        <select name="return_device_status" class="form-control">
                                            <option value="{{ $theftReport->sale_id ? 'vendido' : 'disponible' }}">
                                                {{ $theftReport->sale_id ? 'Vendido (Entregar a Cliente)' : 'Disponible (Regresar a Almacén)' }}
                                            </option>
                                            <option value="disponible">Disponible</option>
                                            <option value="vendido">Vendido</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="font-weight-bold">Notas del Movimiento / Avance de Investigación <span class="text-danger">*</span></label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Agregue información proporcionada por fiscalía, recuperación de dispositivo o resolución..." required></textarea>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-danger font-weight-bold">
                                            <i class="fa-solid fa-save mr-1"></i>Actualizar Estado
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Bitácora de Movimientos -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-dark">
                                <i class="fa-solid fa-timeline mr-2 text-primary"></i>Historial de Movimientos y Notas
                            </h6>
                        </div>
                        <div class="card-body">
                            @forelse ($theftReport->logs as $log)
                                <div class="border-left border-danger pl-3 pb-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-bold font-size-14 text-danger">
                                            {{ $log->status_change ?: 'Nota de seguimiento' }}
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

                <!-- Columna Derecha: Dispositivo y Cliente -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-mobile-screen mr-2"></i>Dispositivo Reportado
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush font-size-14">
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">IMEI</span>
                                    <span class="font-weight-bold font-size-16 text-danger">{{ $theftReport->device?->imei ?? 'N/A' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Marca / Modelo</span>
                                    <span class="font-weight-bold">{{ $theftReport->device?->brand?->name }} {{ $theftReport->device?->model }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Color</span>
                                    <span class="font-weight-bold">{{ $theftReport->device?->color ?: 'No especificado' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Reportado por</span>
                                    <span class="font-weight-bold">{{ $theftReport->reporter?->name ?? 'Usuario del sistema' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    @if ($theftReport->sale)
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold mb-0 text-success">
                                    <i class="fa-solid fa-user-shield mr-2"></i>Cliente Afectado (Venta)
                                </h6>
                                <a href="{{ route('financieras.ventas.show', $theftReport->sale->id) }}" class="btn btn-xs btn-outline-success">
                                    <i class="fa-solid fa-eye mr-1"></i>Ver Venta
                                </a>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush font-size-14">
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Folio de Venta</span>
                                        <span class="font-weight-bold text-success">{{ $theftReport->sale->sale_code }}</span>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Cliente</span>
                                        <span class="font-weight-bold">{{ $theftReport->sale->customer_name ?: 'Sin registrar' }}</span>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Teléfono</span>
                                        <a href="tel:{{ $theftReport->sale->customer_phone }}" class="font-weight-bold text-primary">
                                            {{ $theftReport->sale->customer_phone }}
                                        </a>
                                    </li>
                                    <li class="list-group-item px-0 py-2">
                                        <span class="text-muted font-size-12 d-block">Financiera</span>
                                        <span class="badge badge-light border">{{ $theftReport->sale->financiera?->name ?? 'Directo' }}</span>
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

@section('specificpagescripts')
<script>
$(document).ready(function() {
    $('#theftStatusSelect').on('change', function() {
        if ($(this).val() === 'recuperado') {
            $('#returnStatusDiv').slideDown();
        } else {
            $('#returnStatusDiv').slideUp();
        }
    });
});
</script>
@endsection
