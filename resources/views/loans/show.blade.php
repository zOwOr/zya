@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                        @if (session('print_ticket_url'))
                            <button type="button" onclick="window.printTicketDirectly('{{ session('print_ticket_url') }}')" class="btn btn-sm btn-light text-dark font-weight-bold ml-3">
                                <i class="fa-solid fa-print mr-1"></i>Imprimir Ticket (Sin Saldo Pendiente)
                            </button>
                        @endif
                    </div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

            @if ($otherOpenLoans->isNotEmpty())
                <div class="alert alert-warning border-warning shadow-sm mb-3">
                    <i class="fa-solid fa-bell text-danger mr-2"></i>
                    <strong>Aviso:</strong> Este cliente también tiene otro(s) {{ $otherOpenLoans->count() }} préstamo(s) abierto(s):
                    @foreach ($otherOpenLoans as $other)
                        <a href="{{ route('loans.show', $other->id) }}" class="badge badge-danger ml-1">{{ $other->loan_code }} (Saldo: ${{ number_format($other->balance_amount, 2) }})</a>
                    @endforeach
                </div>
            @endif

            <!-- Cabecera del Préstamo -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <div class="d-flex align-items-center">
                        <h4 class="mb-0 font-weight-bold mr-3">Préstamo {{ $loan->loan_code }}</h4>
                        @if ($loan->status === 'activo')
                            <span class="badge badge-primary px-3 py-1">ACTIVO</span>
                        @elseif ($loan->status === 'mora')
                            <span class="badge badge-danger px-3 py-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i>EN MORA</span>
                        @elseif ($loan->status === 'liquidado')
                            <span class="badge badge-success px-3 py-1">LIQUIDADO</span>
                        @elseif ($loan->status === 'cerrado_manual')
                            <span class="badge badge-info px-3 py-1">CERRADO MANUALMENTE</span>
                        @endif
                    </div>
                    <p class="mb-0 text-muted font-size-13 mt-1">
                        Cliente: <strong>{{ $loan->customer?->tit_name }}</strong> ({{ $loan->customer?->tit_phone ?? 'Sin teléfono' }}) |
                        Tipo: <strong>{{ $loan->loanType?->name ?? 'General' }}</strong> |
                        Sucursal: <strong>{{ $loan->branch?->name ?? 'General' }}</strong>
                    </p>
                </div>
                <div class="d-flex flex-wrap mt-2 mt-md-0">
                    <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-1">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver
                    </a>
                    <a href="{{ route('loans.pagare.print', $loan->id) }}" target="_blank" class="btn btn-dark btn-sm mr-2 mb-1">
                        <i class="fa-solid fa-print mr-1"></i>Imprimir Pagaré para Firma
                    </a>
                    @if ($loan->isOpen() && auth()->user()->can('prestamos.abonar'))
                        <button type="button" class="btn btn-success btn-sm mr-2 mb-1" data-toggle="modal" data-target="#registerPaymentModal">
                            <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Registrar Abono
                        </button>
                    @endif
                    @if ($loan->isOpen() && (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.close_manual')))
                        <button type="button" class="btn btn-warning btn-sm mb-1" data-toggle="modal" data-target="#manualCloseModal">
                            <i class="fa-solid fa-lock mr-1"></i>Cerrar Préstamo Manualmente
                        </button>
                    @endif
                </div>
            </div>

            @if ($loan->status === 'cerrado_manual')
                <div class="alert alert-info border-info shadow-sm mb-4">
                    <h6 class="font-weight-bold mb-1"><i class="fa-solid fa-circle-info mr-1"></i>Préstamo Cerrado de Forma Manual</h6>
                    <p class="mb-0 font-size-13">
                        Cerrado el {{ $loan->closed_manually_at ? $loan->closed_manually_at->format('d/m/Y H:i') : '-' }}
                        por <strong>{{ $loan->closer?->name ?? 'Administrador' }}</strong> |
                        Total Abonado al Cierre: <strong>${{ number_format($loan->closing_final_paid ?? $loan->paid_amount, 2) }}</strong> (de ${{ number_format($loan->total_amount, 2) }}).
                        <br><strong>Motivo / Justificación:</strong> {{ $loan->closing_reason }}
                    </p>
                </div>
            @endif

            <div class="row">
                <!-- Columna Principal: Tabla de Pagos Pendientes (Día Programado vs Día Real de Pago) -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-calendar-check mr-2"></i>Tabla de Pagos del Préstamo (Día Programado vs. Día de Pago Real)
                            </h6>
                            <span class="badge badge-light border">
                                {{ $loan->installments->where('status', 'pagado')->count() }} / {{ $loan->installments->count() }} pagados
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th># Pago</th>
                                            <th>Día Programado de Pago</th>
                                            <th>Día en que se Realizó el Pago</th>
                                            <th>Monto Programado</th>
                                            <th>Monto Abonado</th>
                                            <th>Pendiente</th>
                                            <th>Estado</th>
                                            <th class="text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($loan->installments as $inst)
                                            <tr class="{{ $inst->is_overdue ? 'table-danger' : ($inst->status === 'pagado' ? 'table-success' : '') }}">
                                                <td class="font-weight-bold">Pago #{{ $inst->installment_number }}</td>
                                                <td>
                                                    <i class="fa-regular fa-calendar mr-1 text-muted"></i>
                                                    <strong>{{ $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-' }}</strong>
                                                </td>
                                                <td>
                                                    @if ($inst->paid_date)
                                                        <span class="text-success font-weight-bold">
                                                            <i class="fa-solid fa-check mr-1"></i>{{ $inst->paid_date->format('d/m/Y H:i') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">Pendiente de pago</span>
                                                    @endif
                                                </td>
                                                <td>${{ number_format($inst->scheduled_amount, 2) }}</td>
                                                <td class="text-success font-weight-bold">${{ number_format($inst->paid_amount, 2) }}</td>
                                                <td class="text-danger font-weight-bold">${{ number_format($inst->remaining_amount, 2) }}</td>
                                                <td>
                                                    @if ($inst->status === 'pagado')
                                                        <span class="badge badge-success">PAGADO</span>
                                                    @elseif ($inst->status === 'parcial')
                                                        <span class="badge badge-info">PARCIAL</span>
                                                    @elseif ($inst->status === 'cerrado_manual')
                                                        <span class="badge badge-secondary">CERRADO MANUAL</span>
                                                    @elseif ($inst->is_overdue)
                                                        <span class="badge badge-danger">VENCIDO</span>
                                                    @else
                                                        <span class="badge badge-warning">PENDIENTE</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    @if ($loan->isOpen() && $inst->remaining_amount > 0 && auth()->user()->can('prestamos.abonar'))
                                                        <button type="button"
                                                            class="btn btn-xs btn-primary btn-pay-installment"
                                                            data-id="{{ $inst->id }}"
                                                            data-number="{{ $inst->installment_number }}"
                                                            data-amount="{{ $inst->remaining_amount }}"
                                                            data-scheduled="{{ $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '' }}">
                                                            Cobrar
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Pagos Realizados y Reimpresión de Ticket (Sin Saldo Pendiente) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-receipt mr-2"></i>Historial de Pagos y Tickets Emitidos
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Folio</th>
                                            <th>Concepto</th>
                                            <th>Día Programado</th>
                                            <th>Día Real de Pago</th>
                                            <th>Método</th>
                                            <th>Monto</th>
                                            <th>Cajero</th>
                                            <th class="text-right">Ticket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($loan->payments as $pmt)
                                            <tr>
                                                <td class="font-weight-bold">{{ $pmt->payment_code }}</td>
                                                <td>
                                                    @if ($pmt->payment_type === 'comision_apertura')
                                                        <span class="badge badge-info">Comisión Apertura</span>
                                                    @elseif ($pmt->payment_type === 'cierre_manual')
                                                        <span class="badge badge-warning">Cierre Manual</span>
                                                    @else
                                                        <span class="badge badge-success">Abono {{ $pmt->installment ? '#' . $pmt->installment->installment_number : '' }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $pmt->scheduled_date ? $pmt->scheduled_date->format('d/m/Y') : '-' }}</td>
                                                <td>{{ $pmt->paid_at ? $pmt->paid_at->format('d/m/Y H:i') : '-' }}</td>
                                                <td>{{ strtoupper($pmt->payment_method) }}</td>
                                                <td class="font-weight-bold text-success">${{ number_format($pmt->amount, 2) }}</td>
                                                <td>{{ $pmt->user?->name ?? 'Sistema' }}</td>
                                                <td class="text-right">
                                                    <button type="button" onclick="window.printTicketDirectly('{{ route('loans.ticket', $pmt->id) }}')" class="btn btn-xs btn-outline-dark" title="Imprimir ticket sin saldo pendiente">
                                                        <i class="fa-solid fa-print mr-1"></i>Ticket
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-3 text-muted">Aún no se han registrado pagos en este préstamo.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen Financiero, Pagaré y Video (SuperAdmin) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-primary">Resumen del Préstamo</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Modalidad de Pago:</span>
                                <strong>{{ $loan->payment_frequency_label }} ({{ $loan->term_periods }} pagos de ${{ number_format($loan->installment_amount, 2) }})</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Capital Prestado:</span>
                                <strong>${{ number_format($loan->principal_amount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Interés ({{ $loan->interest_rate }}%):</span>
                                <strong>${{ number_format($loan->interest_amount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Monto Total del Préstamo:</span>
                                <strong class="text-primary">${{ number_format($loan->total_amount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Comisión por Apertura (Caja):</span>
                                <strong class="text-info">${{ number_format($loan->opening_commission, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Abonado:</span>
                                <strong class="text-success font-size-16">${{ number_format($loan->paid_amount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Saldo Pendiente (Interno):</span>
                                <strong class="text-danger font-size-16">${{ number_format($loan->balance_amount, 2) }}</strong>
                            </div>

                            @if (!empty($loan->type_specific_data))
                                <hr>
                                <h6 class="font-weight-bold font-size-13 text-dark mb-2">Información Específica ({{ $loan->loanType?->name ?? 'Préstamo' }})</h6>
                                @foreach ($loan->type_specific_data as $key => $val)
                                    @if (!empty($val))
                                        <div class="d-flex justify-content-between font-size-13 mb-1">
                                            <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            <strong>{{ $val }}</strong>
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            @if ($loan->notes)
                                <hr>
                                <small class="text-muted d-block">Observaciones:</small>
                                <p class="font-size-13 mb-0">{{ $loan->notes }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Sección de Pagaré (Imprimir para firma y Subir archivo firmado) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-file-signature mr-1"></i>Expediente de Pagaré
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <a href="{{ route('loans.pagare.print', $loan->id) }}" target="_blank" class="btn btn-outline-dark btn-block btn-sm mb-2">
                                    <i class="fa-solid fa-print mr-1"></i>Imprimir Pagaré para Firma del Cliente
                                </a>
                                <a href="{{ route('loans.pagare.print', ['loan' => $loan->id, 'pdf' => 1]) }}" target="_blank" class="btn btn-outline-secondary btn-block btn-sm">
                                    <i class="fa-solid fa-file-pdf mr-1"></i>Descargar Pagaré en PDF
                                </a>
                            </div>

                            @if ($loan->promissory_note_path)
                                <div class="alert alert-success py-2 px-3 mb-3 font-size-13">
                                    <i class="fa-solid fa-check-circle mr-1"></i>Pagaré firmado cargado en el sistema.
                                    <a href="{{ route('loans.pagare.download', $loan->id) }}" class="d-block font-weight-bold text-success mt-1">
                                        <i class="fa-solid fa-download mr-1"></i>Descargar Pagaré Firmado
                                    </a>
                                </div>
                            @else
                                <div class="alert alert-warning py-2 px-3 mb-3 font-size-12">
                                    Pendiente subir el archivo del Pagaré firmado por el cliente.
                                </div>
                            @endif

                            <form action="{{ route('loans.pagare.upload', $loan->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group mb-2">
                                    <label class="font-size-12 font-weight-bold">Subir / Actualizar Pagaré Firmado (PDF o Imagen)</label>
                                    <input type="file" name="promissory_note_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="fa-solid fa-upload mr-1"></i>Guardar Pagaré Firmado
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sección de Video: EXCLUSIVA PARA ROL SUPERADMIN -->
                    @if (auth()->user()->isSuperAdmin())
                        <div class="card border-dark shadow-sm mb-4">
                            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold mb-0 text-white">
                                    <i class="fa-solid fa-video mr-1"></i>Video de Evidencia (Solo SuperAdmin)
                                </h6>
                                <span class="badge badge-warning">PRIVADO</span>
                            </div>
                            <div class="card-body">
                                @if ($loan->video_path)
                                    <video controls class="w-100 rounded mb-3" style="max-height: 240px; background:#000;">
                                        <source src="{{ route('loans.video.stream', $loan->id) }}" type="video/mp4">
                                        Tu navegador no soporta reproducción de video.
                                    </video>
                                    <form action="{{ route('loans.video.delete', $loan->id) }}" method="POST" class="mb-3" onsubmit="return confirm('¿Eliminar video de evidencia?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm btn-block">
                                            <i class="fa-solid fa-trash mr-1"></i>Eliminar Video Actual
                                        </button>
                                    </form>
                                @else
                                    <p class="text-muted font-size-13 mb-3">No se ha subido video de evidencia para este préstamo.</p>
                                @endif

                                <form action="{{ route('loans.video.update', $loan->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label class="font-size-12 font-weight-bold">{{ $loan->video_path ? 'Reemplazar Video' : 'Subir Video de Evidencia' }}</label>
                                        <input type="file" name="video_evidence" class="form-control-file" accept="video/mp4,video/webm,video/quicktime" required>
                                    </div>
                                    <button type="submit" class="btn btn-dark btn-sm btn-block">
                                        <i class="fa-solid fa-cloud-arrow-up mr-1"></i>{{ $loan->video_path ? 'Actualizar Video' : 'Subir Video' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Abono -->
<div class="modal fade" id="registerPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white"><i class="fa-solid fa-hand-holding-dollar mr-2"></i>Registrar Abono de Préstamo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('loans.payments.store', $loan->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Seleccionar Pago Pendiente Programado</label>
                        <select name="installment_id" id="modalInstallmentSelect" class="form-control select2-autocomplete" data-placeholder="Aplicar automáticamente al pago más antiguo pendiente">
                            <option value="">Aplicar automáticamente al pago más antiguo pendiente</option>
                            @foreach ($loan->installments->whereIn('status', ['pendiente', 'parcial', 'vencido']) as $inst)
                                <option value="{{ $inst->id }}" data-amount="{{ $inst->remaining_amount }}">
                                    Pago #{{ $inst->installment_number }} — Programado: {{ $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-' }} (Pendiente: ${{ number_format($inst->remaining_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Monto a Abonar ($) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="modalPaymentAmount" class="form-control font-weight-bold" value="{{ $loan->installment_amount }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Día de Realización del Pago *</label>
                            <input type="datetime-local" name="paid_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Método de Pago (Impacta Caja y Corte) *</label>
                        <select name="payment_method" id="modalPaymentMethod" class="form-control" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="mixto">Pago Mixto (Efectivo + Tarjeta / Transferencia)</option>
                        </select>
                    </div>

                    <div id="modalMixedPaymentFields" class="row d-none bg-light p-2 rounded border mb-3">
                        <div class="col-4">
                            <label class="font-size-12 font-weight-bold">Efectivo ($)</label>
                            <input type="number" step="0.01" min="0" name="cash_amount" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-4">
                            <label class="font-size-12 font-weight-bold">Tarjeta ($)</label>
                            <input type="number" step="0.01" min="0" name="card_amount" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-4">
                            <label class="font-size-12 font-weight-bold">Transferencia ($)</label>
                            <input type="number" step="0.01" min="0" name="transfer_amount" class="form-control form-control-sm" value="0">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Notas del Abono</label>
                        <input type="text" name="notes" class="form-control" placeholder="Opcional...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check mr-1"></i>Registrar Abono e Imprimir Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cerrar Préstamo de Forma Manual -->
<div class="modal fade" id="manualCloseModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold"><i class="fa-solid fa-lock mr-2"></i>Cerrar Préstamo de Forma Manual</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('loans.close-manual', $loan->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-secondary font-size-13">
                        Puedes cerrar este préstamo de forma manual <strong>sin importar si la cantidad total abonada (${{ number_format($loan->paid_amount, 2) }}) es inferior o superior al monto total (${{ number_format($loan->total_amount, 2) }})</strong>.
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Pago Adicional al Cerrar ($)</label>
                            <input type="number" step="0.01" min="0" name="additional_payment" class="form-control" value="0">
                            <small class="text-muted">Dejar en 0 si no entra más dinero hoy.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Método (si hay pago adicional)</label>
                            <select name="payment_method" class="form-control">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Motivo / Acuerdo de Cierre Manual *</label>
                        <textarea name="closing_reason" rows="3" class="form-control" placeholder="Ej. Descuento por liquidación anticipada / Quita autorizada / Ajuste de intereses..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Confirmar Cierre Manual del Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    const instSelect = document.getElementById('modalInstallmentSelect');
    const amountInput = document.getElementById('modalPaymentAmount');
    const methodSelect = document.getElementById('modalPaymentMethod');
    const mixedFields = document.getElementById('modalMixedPaymentFields');

    $('#modalInstallmentSelect').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.dataset.amount) {
            amountInput.value = parseFloat(opt.dataset.amount).toFixed(2);
        }
    });

    if (methodSelect) {
        methodSelect.addEventListener('change', function () {
            if (this.value === 'mixto') {
                mixedFields.classList.remove('d-none');
            } else {
                mixedFields.classList.add('d-none');
            }
        });
    }

    $(document).on('click', '.btn-pay-installment', function () {
        const id = this.dataset.id;
        const amount = this.dataset.amount;
        $('#modalInstallmentSelect').val(id).trigger('change');
        if (amountInput) amountInput.value = parseFloat(amount).toFixed(2);
        $('#registerPaymentModal').modal('show');
    });
});
</script>
@endsection

