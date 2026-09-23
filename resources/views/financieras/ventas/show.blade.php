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

            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <div class="d-flex align-items-center">
                        <h4 class="mb-0 font-weight-bold mr-3">Venta {{ $sale->sale_code }}</h4>
                        @if ($sale->status === 'activa')
                            <span class="badge badge-success font-size-14 px-3 py-1">ACTIVA</span>
                        @else
                            <span class="badge badge-danger font-size-14 px-3 py-1">CANCELADA</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 font-size-13 mt-1">Registrada el {{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : '-' }}</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-outline-secondary btn-sm mr-1">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Ventas
                    </a>
                    <a href="{{ route('financieras.ventas.pdf', $sale->id) }}" class="btn btn-secondary btn-sm mr-1" target="_blank">
                        <i class="fa-solid fa-file-pdf mr-1"></i>Póliza / Recibo PDF
                    </a>
                    @if ($sale->status === 'activa' && auth()->user()->can('financieras.ventas.edit'))
                        <a href="{{ route('financieras.ventas.edit', $sale->id) }}" class="btn btn-warning btn-sm mr-1">
                            <i class="fa-solid fa-pen-to-square mr-1"></i>Editar
                        </a>
                    @endif
                    @if ($sale->status === 'activa' && auth()->user()->can('financieras.ventas.delete'))
                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#cancelModal">
                            <i class="fa-solid fa-ban mr-1"></i>Cancelar Venta
                        </button>
                    @endif
                </div>
            </div>

            <!-- Cancellation alert if cancelled -->
            @if ($sale->status === 'cancelada')
                <div class="alert alert-danger mb-4 shadow-sm border-0">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-xmark fa-2x mr-3"></i>
                        <div>
                            <h6 class="font-weight-bold text-danger mb-1">Esta venta fue cancelada el {{ $sale->cancelled_at ? $sale->cancelled_at->format('d/m/Y H:i') : '' }}</h6>
                            <p class="mb-0 font-size-13"><strong>Cancelada por:</strong> {{ $sale->canceller?->name ?? 'Usuario del sistema' }} | <strong>Motivo:</strong> {{ $sale->cancellation_reason }}</p>
                            <span class="badge badge-light mt-1 text-dark border font-size-12">El dispositivo {{ $sale->device?->imei }} fue devuelto al inventario disponible.</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <!-- Columna Izquierda: Detalles del Equipo y Financiamiento -->
                <div class="col-lg-8">
                    <!-- Tarjeta Dispositivo -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-mobile-screen mr-2"></i>Dispositivo Vinculado
                            </h6>
                            <div>
                                <a href="{{ route('financieras.inventario.history', $sale->device_id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-clock-rotate-left mr-1"></i>Historial del IMEI
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <span class="text-muted font-size-12 d-block">IMEI</span>
                                    <span class="font-weight-bold font-size-18 text-dark">{{ $sale->device?->imei ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <span class="text-muted font-size-12 d-block">Marca / Modelo</span>
                                    <span class="font-weight-bold font-size-15">{{ $sale->device?->brand?->name }} {{ $sale->device?->model }}</span>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <span class="text-muted font-size-12 d-block">Color</span>
                                    <span class="font-weight-bold">{{ $sale->device?->color ?: 'No especificado' }}</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <span class="text-muted font-size-12 d-block">Sucursal</span>
                                    <span class="badge badge-info">{{ $sale->branch?->name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <span class="text-muted font-size-12 d-block">Vendedor Asignado</span>
                                    <span class="font-weight-bold">{{ $sale->seller_display_name }}</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <span class="text-muted font-size-12 d-block">Acciones cruzadas</span>
                                    <a href="{{ route('financieras.garantias.create', ['imei' => $sale->device?->imei]) }}" class="btn btn-xs btn-outline-warning mr-1">
                                        <i class="fa-solid fa-wrench mr-1"></i>Garantía
                                    </a>
                                    <a href="{{ route('financieras.robos.create', ['imei' => $sale->device?->imei]) }}" class="btn btn-xs btn-outline-danger">
                                        <i class="fa-solid fa-shield-halved mr-1"></i>Robo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta Condiciones Financieras -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-success">
                                <i class="fa-solid fa-hand-holding-dollar mr-2"></i>Condiciones de Financiamiento
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Financiera</span>
                                    <span class="badge badge-light border font-size-14 text-primary font-weight-bold">
                                        {{ $sale->financiera?->name ?? 'Venta Directa' }}
                                    </span>
                                </div>
                                @if($sale->tag_contrato)
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">TAG / Contrato</span>
                                    <span class="font-weight-bold text-dark">{{ $sale->tag_contrato }}</span>
                                </div>
                                @endif
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Precio Total</span>
                                    <span class="font-weight-bold font-size-18 text-dark">${{ number_format($sale->price, 2) }}</span>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Enganche</span>
                                    <span class="font-weight-bold font-size-18 text-success">${{ number_format($sale->down_payment, 2) }}</span>
                                </div>
                                @if($sale->enganche_descuento)
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Enganche c/ Descuento</span>
                                    <span class="font-weight-bold font-size-16 text-warning">${{ number_format($sale->enganche_descuento, 2) }}</span>
                                </div>
                                @endif
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Monto Financiado</span>
                                    <span class="font-weight-bold font-size-18 text-primary">${{ number_format($sale->credit_amount, 2) }}</span>
                                </div>
                                @if($sale->abono_semanal)
                                <div class="col-md-3 col-6 mb-3">
                                    <span class="text-muted font-size-12 d-block">Abono Semanal</span>
                                    <span class="font-weight-bold font-size-16 text-info">${{ number_format($sale->abono_semanal, 2) }}</span>
                                </div>
                                @endif
                                @if($sale->term_weeks)
                                <div class="col-md-3 col-6 mb-2">
                                    <span class="text-muted font-size-12 d-block">Plazo en Semanas</span>
                                    <span class="font-weight-bold">{{ $sale->term_weeks }} semanas</span>
                                </div>
                                @endif
                                <div class="col-md-3 col-6 mb-2">
                                    <span class="text-muted font-size-12 d-block">Plazo en Meses</span>
                                    <span class="font-weight-bold">{{ $sale->term_months ? $sale->term_months . ' Meses' : 'No especificado' }}</span>
                                </div>
                                <div class="col-md-6 col-12 mb-2">
                                    <span class="text-muted font-size-12 d-block">Pago mensual estimado</span>
                                    @if ($sale->term_months && $sale->term_months > 0 && $sale->credit_amount > 0)
                                        <span class="font-weight-bold text-info font-size-16">${{ number_format($sale->credit_amount / $sale->term_months, 2) }} / mes</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bitácora de Notas Libres -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-dark">
                                <i class="fa-solid fa-comment-dots mr-2 text-primary"></i>Notas y Seguimiento
                            </h6>
                            <span class="badge badge-secondary">{{ $sale->notes->count() }} notas</span>
                        </div>
                        <div class="card-body">
                            <!-- Formulario nueva nota -->
                            <form action="{{ route('financieras.ventas.notes.store', $sale->id) }}" method="POST" class="mb-4">
                                @csrf
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold font-size-13">Agregar Nota Libre a la Venta</label>
                                    <textarea name="note" class="form-control" rows="2" placeholder="Escriba aquí notas de seguimiento, acuerdos o incidencias..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-paper-plane mr-1"></i>Guardar Nota
                                </button>
                            </form>

                            <!-- Lista de notas -->
                            <div class="notes-timeline">
                                @forelse ($sale->notes as $note)
                                    <div class="border rounded p-3 mb-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="font-weight-bold text-primary">{{ $note->user?->name ?? 'Usuario' }}</span>
                                            <span class="text-muted font-size-12">{{ $note->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <p class="mb-0 text-dark font-size-14" style="white-space: pre-wrap;">{{ $note->note }}</p>
                                    </div>
                                @empty
                                    <p class="text-muted font-size-13 mb-0 text-center py-3">No hay notas registradas aún para esta venta.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Datos del Cliente y Resumen -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-info">
                                <i class="fa-solid fa-user mr-2"></i>Datos del Cliente
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush font-size-14">
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Nombre</span>
                                    <span class="font-weight-bold">{{ $sale->customer_name ?: 'No especificado' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Teléfono</span>
                                    @if ($sale->customer_phone)
                                        <a href="tel:{{ $sale->customer_phone }}" class="font-weight-bold text-primary">
                                            <i class="fa-solid fa-phone mr-1"></i>{{ $sale->customer_phone }}
                                        </a>
                                        <a href="https://wa.me/52{{ preg_replace('/[^0-9]/', '', $sale->customer_phone) }}" target="_blank" class="badge badge-success ml-2">
                                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                        </a>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Email</span>
                                    <span class="font-weight-bold">{{ $sale->customer_email ?: 'No especificado' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">INE / Folio</span>
                                    <span class="font-weight-bold">{{ $sale->customer_ine ?: 'No especificado' }}</span>
                                </li>
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Domicilio</span>
                                    <span class="font-weight-bold">{{ $sale->customer_address ?: 'No especificado' }}</span>
                                </li>
                                @if($sale->customer_chip)
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Chip Ingresado (SIM)</span>
                                    <span class="font-weight-bold">{{ $sale->customer_chip }}</span>
                                </li>
                                @endif
                                @if($sale->customer_facebook)
                                <li class="list-group-item px-0 py-2">
                                    <span class="text-muted font-size-12 d-block">Facebook</span>
                                    <span class="font-weight-bold">{{ $sale->customer_facebook }}</span>
                                </li>
                                @endif
                            </ul>

                            @if($sale->ref1_name || $sale->ref2_name || $sale->ref3_name)
                            <div class="mt-3 pt-2 border-top">
                                <span class="text-muted font-size-12 font-weight-bold d-block mb-2"><i class="fa-solid fa-users mr-1"></i>REFERENCIAS</span>
                                @if($sale->ref1_name)
                                <div class="mb-1">
                                    <span class="badge badge-light border text-dark">#1</span>
                                    <span class="font-weight-bold ml-1">{{ $sale->ref1_name }}</span>
                                    @if($sale->ref1_phone)
                                        &mdash; <a href="tel:{{ $sale->ref1_phone }}" class="text-primary">{{ $sale->ref1_phone }}</a>
                                    @endif
                                </div>
                                @endif
                                @if($sale->ref2_name)
                                <div class="mb-1">
                                    <span class="badge badge-light border text-dark">#2</span>
                                    <span class="font-weight-bold ml-1">{{ $sale->ref2_name }}</span>
                                    @if($sale->ref2_phone)
                                        &mdash; <a href="tel:{{ $sale->ref2_phone }}" class="text-primary">{{ $sale->ref2_phone }}</a>
                                    @endif
                                </div>
                                @endif
                                @if($sale->ref3_name)
                                <div class="mb-1">
                                    <span class="badge badge-light border text-dark">#3</span>
                                    <span class="font-weight-bold ml-1">{{ $sale->ref3_name }}</span>
                                    @if($sale->ref3_phone)
                                        &mdash; <a href="tel:{{ $sale->ref3_phone }}" class="text-primary">{{ $sale->ref3_phone }}</a>
                                    @endif
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Garantías y Robos asociados -->
                    @if ($sale->warranties->isNotEmpty() || $sale->theftReports->isNotEmpty())
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="font-weight-bold mb-0 text-dark">
                                    <i class="fa-solid fa-link mr-2 text-warning"></i>Movimientos Vinculados
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @foreach ($sale->warranties as $w)
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div>
                                                <i class="fa-solid fa-wrench text-warning mr-1"></i>
                                                <a href="{{ route('financieras.garantias.show', $w->id) }}" class="font-weight-bold">
                                                    {{ $w->warranty_code }}
                                                </a>
                                                <small class="d-block text-muted">{{ $w->currentStage?->name }}</small>
                                            </div>
                                            <span class="badge badge-light border">{{ ucfirst($w->status) }}</span>
                                        </li>
                                    @endforeach
                                    @foreach ($sale->theftReports as $tr)
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div>
                                                <i class="fa-solid fa-shield-halved text-danger mr-1"></i>
                                                <a href="{{ route('financieras.robos.show', $tr->id) }}" class="font-weight-bold">
                                                    {{ $tr->report_code }}
                                                </a>
                                                <small class="d-block text-muted">{{ $tr->incident_date ? $tr->incident_date->format('d/m/Y') : '' }}</small>
                                            </div>
                                            <span class="badge badge-danger">{{ ucfirst($tr->status) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cancelar Venta -->
@if ($sale->status === 'activa')
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.ventas.cancel', $sale->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-ban mr-2"></i>Cancelar Venta {{ $sale->sale_code }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Confirma que desea cancelar esta venta?</p>
                    <div class="alert alert-warning py-2 font-size-13 mb-3">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        El dispositivo con IMEI <strong>{{ $sale->device?->imei }}</strong> volverá de inmediato al inventario como <strong>disponible</strong>.
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Motivo de Cancelación <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Indique claramente la razón de la cancelación..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban mr-1"></i>Confirmar Cancelación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
