@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold"><i class="fa-solid fa-file-invoice-dollar text-primary mr-2"></i>Crear Nuevo Préstamo</h4>
                    <p class="mb-0 text-muted font-size-13">
                        El monto prestado no descuenta de caja; únicamente se refleja en caja la <strong>Comisión por Apertura</strong> y los abonos posteriores.
                    </p>
                </div>
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Volver a Préstamos
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Alerta Dinámica de Préstamos Abiertos / Pagos Pendientes del Cliente -->
            <div id="customerLoanAlertBox" class="alert alert-warning border-warning shadow-sm d-none mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fa-solid fa-triangle-exclamation fa-2x text-danger mr-3 mt-1"></i>
                    <div class="w-100">
                        <h5 class="font-weight-bold text-danger mb-1">
                            ¡ATENCIÓN! El cliente seleccionado tiene préstamos abiertos y pagos pendientes
                        </h5>
                        <p id="customerAlertSummary" class="mb-2 text-dark font-size-14"></p>
                        <div id="customerOpenLoansList" class="mb-2"></div>
                        <div id="customerPendingPaymentsTable" class="table-responsive bg-white rounded p-2 border"></div>
                    </div>
                </div>
            </div>

            <form action="{{ route('loans.store') }}" method="POST" enctype="multipart/form-data" id="createLoanForm">
                @csrf
                <div class="row">
                    <!-- Columna Izquierda: Cliente, Tipo de Préstamo e Información Específica -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="font-weight-bold mb-0 text-primary">
                                    1. Selección de Cliente y Tipo de Préstamo (Opcional)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 form-group">
                                        <label class="font-weight-bold">Cliente (Módulo de Clientes - Autocomplete) <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="customerSelect" class="form-control select2-autocomplete" required>
                                            <option value="">-- Escribe para buscar cliente por nombre o teléfono --</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ (string)old('customer_id', $selectedCustomerId) === (string)$customer->id ? 'selected' : '' }}>
                                                    {{ $customer->tit_name }} ({{ $customer->tit_phone ?? 'Sin tel' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.all_branches'))
                                        <div class="col-md-4 form-group">
                                            <label class="font-weight-bold">Sucursal</label>
                                            <select name="branch_id" class="form-control">
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ (string)old('branch_id', auth()->user()->branch_id) === (string)$branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="col-md-12 form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="font-weight-bold mb-0">
                                                Tipo de Préstamo <small class="text-muted">(Opcional — no obligatorio)</small>
                                            </label>
                                            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.catalogs'))
                                                <a href="{{ route('loans.types.index') }}" class="font-size-12 font-weight-bold text-primary" target="_blank">
                                                    <i class="fa-solid fa-sliders mr-1"></i>Gestionar Catálogo de Tipos
                                                </a>
                                            @endif
                                        </div>

                                        <!-- Botones de Acceso Rápido a Tasas Predefinidas Editables -->
                                        <div class="mb-2">
                                            <small class="text-muted font-weight-bold mr-1">Tasas Predefinidas:</small>
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-rate-preset mr-1 mb-1" data-rate="20">20%</button>
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-rate-preset mr-1 mb-1" data-rate="40">40%</button>
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-rate-preset mr-1 mb-1" data-rate="50">50%</button>
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-rate-preset mr-1 mb-1" data-rate="60">60%</button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary btn-rate-clear mb-1" title="Captura personalizada directa">Directo / Libre</button>
                                        </div>

                                        <select name="loan_type_id" id="loanTypeSelect" class="form-control select2-autocomplete">
                                            <option value="">-- Sin tipo predefinido (Captura Directa / Personalizado) --</option>
                                            @foreach ($loanTypes as $lt)
                                                <option value="{{ $lt->id }}"
                                                    data-rate="{{ $lt->default_interest_rate }}"
                                                    data-fee="{{ $lt->default_opening_fee }}"
                                                    data-frequency="{{ $lt->default_frequency }}"
                                                    data-periods="{{ $lt->default_periods }}"
                                                    data-fields='@json($lt->required_fields ?? [])'
                                                    {{ old('loan_type_id') == $lt->id ? 'selected' : '' }}>
                                                    {{ $lt->name }} ({{ number_format($lt->default_interest_rate, 0) }}% | {{ \App\Models\Loan::frequencyLabel($lt->default_frequency) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Sección dinámica según el tipo de préstamo seleccionado -->
                                <div class="bg-light rounded p-3 border mt-2">
                                    <h6 class="font-weight-bold text-dark mb-2">
                                        <i class="fa-solid fa-clipboard-list mr-1 text-primary"></i>Información Requerida según Tipo de Préstamo
                                    </h6>
                                    <div id="dynamicLoanTypeFields" class="row">
                                        <div class="col-12 text-muted font-size-13">
                                            <i class="fa-solid fa-check-circle text-success mr-1"></i>Modo Préstamo Directo: No requiere catálogo predefinido. Captura directamente capital, abonos y frecuencia.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expediente: Video de Evidencia y Pagaré -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="font-weight-bold mb-0 text-primary">
                                    2. Expediente: Video de Evidencia (Opcional) y Pagaré
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">
                                            <i class="fa-solid fa-video text-danger mr-1"></i>Video de Evidencia (Opcional)
                                        </label>
                                        <input type="file" name="video_evidence" class="form-control-file" accept="video/mp4,video/webm,video/quicktime">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa-solid fa-lock mr-1"></i>Restringido: Únicamente el rol <strong>SuperAdmin</strong> podrá visualizar y editar este video.
                                        </small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">
                                            <i class="fa-solid fa-file-signature text-primary mr-1"></i>Archivo de Pagaré Firmado (Opcional)
                                        </label>
                                        <input type="file" name="promissory_note_file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted d-block mt-1">
                                            También podrás imprimir el Pagaré al guardar y subirlo firmado después.
                                        </small>
                                    </div>
                                    <div class="col-md-12 form-group mb-0">
                                        <label class="font-weight-bold">Observaciones / Notas del Préstamo</label>
                                        <textarea name="notes" rows="2" class="form-control" placeholder="Acuerdos o detalles adicionales del préstamo...">{{ old('notes') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Condiciones Financieras, Amortización y Comisión por Apertura -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-primary text-white py-3">
                                <h6 class="font-weight-bold mb-0 text-white">
                                    3. Condiciones Financieras y Amortización
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Capital Prestado ($) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="1" name="principal_amount" id="principalInput" class="form-control form-control-lg font-weight-bold text-dark" value="{{ old('principal_amount', 5000) }}" required>
                                        <small class="text-muted font-size-11">Monto entregado (sin salida de caja)</small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold text-primary">Abono por Pago / Cuota ($) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" name="installment_amount" id="installmentInput" class="form-control form-control-lg font-weight-bold text-primary border-primary" value="{{ old('installment_amount', 854) }}" placeholder="Ej. 854.00" required>
                                        <small class="text-muted font-size-11">Monto de cada abono a pagar</small>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">No. de Pagos <span class="text-danger">*</span></label>
                                        <input type="number" min="1" max="240" name="term_periods" id="periodsInput" class="form-control font-weight-bold" value="{{ old('term_periods', 8) }}" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">Frecuencia <span class="text-danger">*</span></label>
                                        <select name="payment_frequency" id="frequencyInput" class="form-control font-weight-bold" required>
                                            <option value="12_26" {{ old('payment_frequency') === '12_26' ? 'selected' : '' }}>12 y 26 (Días 12 y 26)</option>
                                            <option value="quincenal" {{ old('payment_frequency') === 'quincenal' ? 'selected' : '' }}>Quincenal (15 y 30)</option>
                                            <option value="semanal" {{ old('payment_frequency') === 'semanal' ? 'selected' : '' }}>Semanal (Cada 7 días)</option>
                                            <option value="mensual" {{ old('payment_frequency') === 'mensual' ? 'selected' : '' }}>Mensual (Un día al mes)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">Tasa Interés (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" name="interest_rate" id="rateInput" class="form-control font-weight-bold" value="{{ old('interest_rate', 36.64) }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text font-size-12">%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted font-size-11">Calculada automáticamente</small>
                                    </div>

                                    <!-- Campos Condicionales por Frecuencia -->
                                    <div class="col-md-6 form-group d-none" id="weeklyDayGroup">
                                        <label class="font-weight-bold font-size-12">Día de cobro semanal</label>
                                        <select name="payment_day_of_week" id="weeklyDaySelect" class="form-control form-control-sm">
                                            <option value="1">Lunes</option>
                                            <option value="2">Martes</option>
                                            <option value="3">Miércoles</option>
                                            <option value="4">Jueves</option>
                                            <option value="5">Viernes</option>
                                            <option value="6" selected>Sábado</option>
                                            <option value="7">Domingo</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group d-none" id="monthlyDayGroup">
                                        <label class="font-weight-bold font-size-12">Día de cobro del mes (1 al 31)</label>
                                        <input type="number" min="1" max="31" name="payment_day_of_month" id="monthlyDayInput" class="form-control form-control-sm" value="{{ date('j') }}">
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold font-size-12">Fecha de Inicio / Firma *</label>
                                        <input type="date" name="start_date" id="startDateInput" class="form-control form-control-sm" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold font-size-12 text-primary">Fecha del Primer Pago</label>
                                        <input type="date" name="first_payment_date" id="firstPaymentDateInput" class="form-control form-control-sm" value="{{ old('first_payment_date') }}">
                                        <small class="text-muted font-size-10">Calculada automáticamente; puedes ajustarla.</small>
                                    </div>
                                </div>

                                <!-- Resumen Financiero Calculado -->
                                <div class="alert alert-info py-2 px-3 mb-3 border-info">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-size-13 font-weight-bold">Total a Pagar (Capital + Interés):</span>
                                        <strong id="calcTotalDisplay" class="font-size-16 text-dark">$6,832.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-size-13">Interés generado:</span>
                                        <span id="calcInterestDisplay" class="font-weight-bold text-primary">$1,832.00 (36.64%)</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-size-13">Vencimiento final estimado:</span>
                                        <strong id="calcDueDateDisplay" class="text-dark font-size-13">-</strong>
                                    </div>
                                </div>

                                <!-- Calendario / Amortización Preliminar en Vivo -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center" style="cursor: pointer;" data-toggle="collapse" data-target="#collapseSchedulePreview">
                                        <span class="font-weight-bold font-size-12 text-dark">
                                            <i class="fa-solid fa-calendar-days text-primary mr-1"></i>Calendario de Amortización (<span id="scheduleCountBadge">8 pagos</span>)
                                        </span>
                                        <span class="font-size-11 text-muted"><i class="fa-solid fa-chevron-down mr-1"></i>Ver desglose de fechas</span>
                                    </div>
                                    <div id="collapseSchedulePreview" class="collapse show">
                                        <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                                            <table class="table table-sm table-striped table-hover mb-0 font-size-12">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th class="py-1">No.</th>
                                                        <th class="py-1">Fecha Programada</th>
                                                        <th class="py-1 text-right">Importe</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="schedulePreviewBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <!-- Comisión por Apertura (Conservada exactamente con su impacto contable y desglose) -->
                                <div class="bg-light p-3 rounded border border-success">
                                    <label class="font-weight-bold text-success mb-1">
                                        <i class="fa-solid fa-cash-register mr-1"></i>Comisión por Apertura ($) — Único impacto en Caja hoy
                                    </label>
                                    <input type="number" step="0.01" min="0" name="opening_commission" id="commissionInput" class="form-control form-control-lg font-weight-bold text-success mb-2" value="{{ old('opening_commission', 150) }}">

                                    <div class="form-group mb-2">
                                        <label class="font-size-12 font-weight-bold">Método de Pago de la Comisión</label>
                                        <select name="commission_payment_method" id="commMethodSelect" class="form-control form-control-sm">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="mixto">Pago Mixto (Combinado)</option>
                                        </select>
                                    </div>

                                    <div id="commMixedBox" class="row d-none mt-2">
                                        <div class="col-4">
                                            <label class="font-size-11">Efectivo</label>
                                            <input type="number" step="0.01" min="0" name="commission_cash" class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-4">
                                            <label class="font-size-11">Tarjeta</label>
                                            <input type="number" step="0.01" min="0" name="commission_card" class="form-control form-control-sm" value="0">
                                        </div>
                                        <div class="col-4">
                                            <label class="font-size-11">Transferencia</label>
                                            <input type="number" step="0.01" min="0" name="commission_transfer" class="form-control form-control-sm" value="0">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block btn-lg mt-4">
                                    <i class="fa-solid fa-check-circle mr-1"></i>Crear Préstamo y Generar Pagaré
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerSelect = document.getElementById('customerSelect');
    const alertBox = document.getElementById('customerLoanAlertBox');
    const alertSummary = document.getElementById('customerAlertSummary');
    const openLoansList = document.getElementById('customerOpenLoansList');
    const pendingTable = document.getElementById('customerPendingPaymentsTable');

    const loanTypeSelect = document.getElementById('loanTypeSelect');
    const dynamicFieldsContainer = document.getElementById('dynamicLoanTypeFields');
    const principalInput = document.getElementById('principalInput');
    const installmentInput = document.getElementById('installmentInput');
    const periodsInput = document.getElementById('periodsInput');
    const frequencyInput = document.getElementById('frequencyInput');
    const rateInput = document.getElementById('rateInput');
    const startDateInput = document.getElementById('startDateInput');
    const firstPaymentDateInput = document.getElementById('firstPaymentDateInput');
    const weeklyDayGroup = document.getElementById('weeklyDayGroup');
    const weeklyDaySelect = document.getElementById('weeklyDaySelect');
    const monthlyDayGroup = document.getElementById('monthlyDayGroup');
    const monthlyDayInput = document.getElementById('monthlyDayInput');

    const commissionInput = document.getElementById('commissionInput');
    const commMethodSelect = document.getElementById('commMethodSelect');
    const commMixedBox = document.getElementById('commMixedBox');

    const calcTotalDisplay = document.getElementById('calcTotalDisplay');
    const calcInterestDisplay = document.getElementById('calcInterestDisplay');
    const calcDueDateDisplay = document.getElementById('calcDueDateDisplay');
    const scheduleCountBadge = document.getElementById('scheduleCountBadge');
    const schedulePreviewBody = document.getElementById('schedulePreviewBody');

    let lastEditedMode = 'installment'; // 'installment' o 'rate'

    // Formatear fecha YYYY-MM-DD a DD/MM/YYYY
    function formatDateDMY(dateStr) {
        if (!dateStr || dateStr === '-') return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    // Cálculo dinámico de fechas de amortización idéntico a Loan::calculateInstallmentDates
    function calculateInstallmentDatesJS(startDateStr, frequency, periods, firstPaymentDateStr, dayOfWeek, dayOfMonth) {
        if (!startDateStr || periods < 1) return [];

        const sParts = startDateStr.split('-').map(Number);
        const start = new Date(sParts[0], sParts[1] - 1, sParts[2]);
        let current = null;

        if (firstPaymentDateStr) {
            const fpParts = firstPaymentDateStr.split('-').map(Number);
            current = new Date(fpParts[0], fpParts[1] - 1, fpParts[2]);
        }

        if (!current) {
            if (frequency === '12_26') {
                const day = start.getDate();
                if (day <= 12) {
                    current = new Date(start.getFullYear(), start.getMonth(), 12);
                } else if (day <= 26) {
                    current = new Date(start.getFullYear(), start.getMonth(), 26);
                } else {
                    current = new Date(start.getFullYear(), start.getMonth() + 1, 12);
                }
            } else if (frequency === 'quincenal') {
                const day = start.getDate();
                if (day <= 15) {
                    current = new Date(start.getFullYear(), start.getMonth(), 15);
                } else if (day <= 30) {
                    const lastDay = new Date(start.getFullYear(), start.getMonth() + 1, 0).getDate();
                    current = new Date(start.getFullYear(), start.getMonth(), Math.min(30, lastDay));
                } else {
                    current = new Date(start.getFullYear(), start.getMonth() + 1, 15);
                }
            } else if (frequency === 'semanal') {
                const targetDow = parseInt(dayOfWeek) || ((start.getDay() === 0) ? 7 : start.getDay());
                const startDow = (start.getDay() === 0) ? 7 : start.getDay();
                let diff = targetDow - startDow;
                if (diff <= 0) diff += 7;
                current = new Date(start.getFullYear(), start.getMonth(), start.getDate() + diff);
            } else if (frequency === 'mensual') {
                const targetDom = parseInt(dayOfMonth) || start.getDate();
                const curDom = start.getDate();
                if (curDom < targetDom) {
                    const maxDay = new Date(start.getFullYear(), start.getMonth() + 1, 0).getDate();
                    current = new Date(start.getFullYear(), start.getMonth(), Math.min(targetDom, maxDay));
                } else {
                    const maxDay = new Date(start.getFullYear(), start.getMonth() + 2, 0).getDate();
                    current = new Date(start.getFullYear(), start.getMonth() + 1, Math.min(targetDom, maxDay));
                }
            } else {
                current = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 7);
            }
        }

        const domMensual = parseInt(dayOfMonth) || (firstPaymentDateStr ? current.getDate() : start.getDate());
        const dates = [];

        for (let i = 1; i <= periods; i++) {
            const y = current.getFullYear();
            const m = String(current.getMonth() + 1).padStart(2, '0');
            const d = String(current.getDate()).padStart(2, '0');
            dates.push(`${y}-${m}-${d}`);

            if (i < periods) {
                if (frequency === '12_26') {
                    if (current.getDate() < 26) {
                        current = new Date(current.getFullYear(), current.getMonth(), 26);
                    } else {
                        current = new Date(current.getFullYear(), current.getMonth() + 1, 12);
                    }
                } else if (frequency === 'quincenal') {
                    if (current.getDate() <= 15) {
                        const lastDay = new Date(current.getFullYear(), current.getMonth() + 1, 0).getDate();
                        current = new Date(current.getFullYear(), current.getMonth(), Math.min(30, lastDay));
                    } else {
                        current = new Date(current.getFullYear(), current.getMonth() + 1, 15);
                    }
                } else if (frequency === 'semanal') {
                    current = new Date(current.getFullYear(), current.getMonth(), current.getDate() + 7);
                } else if (frequency === 'mensual') {
                    const nextMonthYear = (current.getMonth() === 11) ? current.getFullYear() + 1 : current.getFullYear();
                    const nextMonth = (current.getMonth() + 1) % 12;
                    const lastDay = new Date(nextMonthYear, nextMonth + 1, 0).getDate();
                    current = new Date(nextMonthYear, nextMonth, Math.min(domMensual, lastDay));
                } else {
                    current = new Date(current.getFullYear(), current.getMonth(), current.getDate() + 7);
                }
            }
        }

        return dates;
    }

    // Sugerir automáticamente la fecha del primer pago
    function autoSuggestFirstPaymentDate() {
        const startDateStr = startDateInput.value;
        const freq = frequencyInput.value;
        const dates = calculateInstallmentDatesJS(startDateStr, freq, 1, null, weeklyDaySelect.value, monthlyDayInput.value);
        if (dates.length > 0) {
            firstPaymentDateInput.value = dates[0];
        }
    }

    // Renderizar desglose de amortización preliminar
    function renderSchedulePreview(periods, quota, total) {
        const startDateStr = startDateInput.value;
        const freq = frequencyInput.value;
        const fpDateStr = firstPaymentDateInput.value;
        const dow = weeklyDaySelect.value;
        const dom = monthlyDayInput.value;

        const dates = calculateInstallmentDatesJS(startDateStr, freq, periods, fpDateStr, dow, dom);
        schedulePreviewBody.innerHTML = '';

        if (dates.length > 0) {
            calcDueDateDisplay.textContent = formatDateDMY(dates[dates.length - 1]);
        } else {
            calcDueDateDisplay.textContent = '-';
        }

        let accumulated = 0;
        for (let i = 1; i <= periods; i++) {
            const dStr = dates[i - 1] || '-';
            const isLast = (i === periods);
            const thisQuota = isLast ? Math.max(0, total - accumulated) : quota;
            accumulated += thisQuota;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-weight-bold">#${i}</td>
                <td><i class="fa-regular fa-calendar font-size-11 text-muted mr-1"></i>${formatDateDMY(dStr)}</td>
                <td class="text-right font-weight-bold text-dark">$${thisQuota.toFixed(2)}</td>
            `;
            schedulePreviewBody.appendChild(tr);
        }
    }

    // Actualizar cálculos cuando el usuario introduce Abono de cada pago
    function updateFromInstallment() {
        lastEditedMode = 'installment';
        const principal = parseFloat(principalInput.value) || 0;
        const quota = parseFloat(installmentInput.value) || 0;
        const periods = Math.max(1, parseInt(periodsInput.value) || 1);

        const total = quota * periods;
        const interest = Math.max(0, total - principal);
        const rate = principal > 0 ? ((interest / principal) * 100) : 0;

        rateInput.value = rate.toFixed(2);
        updateDisplayValues(total, interest, rate, quota, periods);
    }

    // Actualizar cálculos cuando el usuario introduce Tasa de interés (%) o hace clic en un preset
    function updateFromRate() {
        lastEditedMode = 'rate';
        const principal = parseFloat(principalInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const periods = Math.max(1, parseInt(periodsInput.value) || 1);

        const interest = principal * (rate / 100);
        const total = principal + interest;
        const quota = total / periods;

        installmentInput.value = quota.toFixed(2);
        updateDisplayValues(total, interest, rate, quota, periods);
    }

    function updateDisplayValues(total, interest, rate, quota, periods) {
        calcTotalDisplay.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        calcInterestDisplay.textContent = '$' + interest.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + rate.toFixed(2) + '%)';
        scheduleCountBadge.textContent = periods + ' pagos';

        renderSchedulePreview(periods, quota, total);
    }

    function onGeneralFinancialChange() {
        if (lastEditedMode === 'rate') {
            updateFromRate();
        } else {
            updateFromInstallment();
        }
    }

    // Control de visibilidad de frecuencia
    function handleFrequencyChange() {
        const freq = frequencyInput.value;
        if (freq === 'semanal') {
            weeklyDayGroup.classList.remove('d-none');
            monthlyDayGroup.classList.add('d-none');
        } else if (freq === 'mensual') {
            weeklyDayGroup.classList.add('d-none');
            monthlyDayGroup.classList.remove('d-none');
        } else {
            weeklyDayGroup.classList.add('d-none');
            monthlyDayGroup.classList.add('d-none');
        }
        autoSuggestFirstPaymentDate();
        onGeneralFinancialChange();
    }

    // Renderizar campos dinámicos si se selecciona un tipo de catálogo con garantía / datos
    function renderLoanTypeFields(applyDefaults = false) {
        const option = loanTypeSelect.options[loanTypeSelect.selectedIndex];
        if (!option || !option.value) {
            dynamicFieldsContainer.innerHTML = `
                <div class="col-12 text-muted font-size-13">
                    <i class="fa-solid fa-check-circle text-success mr-1"></i>Modo Préstamo Directo: No requiere catálogo predefinido. Captura directamente capital, abonos y frecuencia.
                </div>
            `;
            return;
        }

        if (applyDefaults) {
            if (option.dataset.rate) {
                rateInput.value = option.dataset.rate;
                updateFromRate();
            }
            if (option.dataset.fee !== undefined) {
                commissionInput.value = option.dataset.fee;
            }
            if (option.dataset.frequency) {
                frequencyInput.value = option.dataset.frequency;
                handleFrequencyChange();
            }
            if (option.dataset.periods) {
                periodsInput.value = option.dataset.periods;
                onGeneralFinancialChange();
            }
        }

        let fields = [];
        try {
            fields = JSON.parse(option.dataset.fields || '[]');
        } catch (e) {
            fields = [];
        }

        dynamicFieldsContainer.innerHTML = '';
        if (!fields.length) {
            dynamicFieldsContainer.innerHTML = '<div class="col-12 text-muted font-size-13">Este tipo de préstamo no requiere campos adicionales.</div>';
            return;
        }

        fields.forEach(function (field) {
            const col = document.createElement('div');
            const reqMark = field.required ? '<span class="text-danger">*</span>' : '';
            const reqAttr = field.required ? 'required' : '';
            let inputControl = '';

            if (field.type === 'textarea') {
                col.className = 'col-md-12 form-group mb-2';
                inputControl = `<textarea name="type_specific_data[${field.key}]" rows="2" class="form-control form-control-sm" placeholder="${field.label}" ${reqAttr}></textarea>`;
            } else if (field.type === 'number') {
                col.className = 'col-md-6 form-group mb-2';
                inputControl = `<input type="number" step="0.01" name="type_specific_data[${field.key}]" class="form-control form-control-sm" placeholder="${field.label}" ${reqAttr}>`;
            } else if (field.type === 'date') {
                col.className = 'col-md-6 form-group mb-2';
                inputControl = `<input type="date" name="type_specific_data[${field.key}]" class="form-control form-control-sm" ${reqAttr}>`;
            } else {
                col.className = 'col-md-6 form-group mb-2';
                inputControl = `<input type="text" name="type_specific_data[${field.key}]" class="form-control form-control-sm" placeholder="${field.label}" ${reqAttr}>`;
            }

            col.innerHTML = `
                <label class="font-size-13 font-weight-bold mb-1">${field.label} ${reqMark}</label>
                ${inputControl}
            `;
            dynamicFieldsContainer.appendChild(col);
        });
    }

    // Comprobar alertas de préstamos previos del cliente
    function checkCustomerOpenLoans(customerId) {
        if (!customerId) {
            alertBox.classList.add('d-none');
            return;
        }

        fetch(`{{ route('loans.check-customer-alerts') }}?customer_id=${encodeURIComponent(customerId)}`)
            .then(res => res.json())
            .then(data => {
                if (data.has_open_loans) {
                    alertBox.classList.remove('d-none');
                    alertSummary.innerHTML = `El cliente cuenta con <strong>${data.open_loans_count} préstamo(s) abierto(s)</strong> y <strong>${data.pending_installments.length} pago(s) pendiente(s)</strong> (${data.overdue_count} vencido(s)).`;

                    let loansHtml = '<div class="mb-2"><strong>Préstamos Abiertos:</strong> ';
                    data.open_loans.forEach(l => {
                        loansHtml += `<a href="${l.url}" target="_blank" class="badge badge-danger mr-2 p-1">${l.loan_code} (${l.type_name}) - Saldo: $${l.balance_amount}</a>`;
                    });
                    loansHtml += '</div>';
                    openLoansList.innerHTML = loansHtml;

                    if (data.pending_installments.length > 0) {
                        let rows = data.pending_installments.slice(0, 6).map(inst => `
                            <tr class="${inst.is_overdue ? 'table-danger' : ''}">
                                <td>${inst.loan_code}</td>
                                <td>Pago #${inst.installment_number}</td>
                                <td>${inst.scheduled_date}</td>
                                <td>$${inst.remaining_amount}</td>
                                <td>${inst.is_overdue ? '<span class="badge badge-danger">VENCIDO</span>' : '<span class="badge badge-warning">PENDIENTE</span>'}</td>
                            </tr>
                        `).join('');
                        pendingTable.innerHTML = `
                            <table class="table table-sm mb-0 font-size-12">
                                <thead>
                                    <tr>
                                        <th>Préstamo</th>
                                        <th>Cuota</th>
                                        <th>Día Programado</th>
                                        <th>Pendiente</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        `;
                    } else {
                        pendingTable.innerHTML = '';
                    }
                } else {
                    alertBox.classList.add('d-none');
                }
            })
            .catch(() => {});
    }

    // Inicializar Select2 en Cliente y Tipo
    $('#customerSelect').select2({
        placeholder: '-- Escribe para buscar cliente por nombre o teléfono --',
        allowClear: true,
        width: '100%'
    });

    $('#customerSelect').on('change', function () {
        checkCustomerOpenLoans(this.value);
    });

    $('#loanTypeSelect').select2({
        placeholder: '-- Sin tipo predefinido (Captura Directa) --',
        allowClear: true,
        width: '100%'
    });

    $('#loanTypeSelect').on('change', function () {
        renderLoanTypeFields(true);
    });

    // Botones de presets de tasa (20%, 40%, 50%, 60%)
    document.querySelectorAll('.btn-rate-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-rate-preset').forEach(b => b.classList.replace('btn-primary', 'btn-outline-primary'));
            this.classList.replace('btn-outline-primary', 'btn-primary');
            rateInput.value = this.dataset.rate;
            updateFromRate();
        });
    });

    const clearPresetBtn = document.querySelector('.btn-rate-clear');
    if (clearPresetBtn) {
        clearPresetBtn.addEventListener('click', function () {
            document.querySelectorAll('.btn-rate-preset').forEach(b => b.classList.replace('btn-primary', 'btn-outline-primary'));
            $('#loanTypeSelect').val('').trigger('change');
            updateFromInstallment();
        });
    }

    // Escuchadores de eventos para cálculos
    installmentInput.addEventListener('input', updateFromInstallment);
    rateInput.addEventListener('input', updateFromRate);
    principalInput.addEventListener('input', onGeneralFinancialChange);
    periodsInput.addEventListener('input', onGeneralFinancialChange);

    frequencyInput.addEventListener('change', handleFrequencyChange);
    weeklyDaySelect.addEventListener('change', function () {
        autoSuggestFirstPaymentDate();
        onGeneralFinancialChange();
    });
    monthlyDayInput.addEventListener('input', function () {
        autoSuggestFirstPaymentDate();
        onGeneralFinancialChange();
    });
    startDateInput.addEventListener('change', function () {
        autoSuggestFirstPaymentDate();
        onGeneralFinancialChange();
    });
    firstPaymentDateInput.addEventListener('change', onGeneralFinancialChange);

    // Pago mixto de comisión por apertura
    commMethodSelect.addEventListener('change', function () {
        if (this.value === 'mixto') {
            commMixedBox.classList.remove('d-none');
        } else {
            commMixedBox.classList.add('d-none');
        }
    });

    // Inicialización al cargar la página
    handleFrequencyChange();
    autoSuggestFirstPaymentDate();
    updateFromInstallment();
    renderLoanTypeFields(false);

    if (customerSelect.value) {
        checkCustomerOpenLoans(customerSelect.value);
    }
});
</script>
@endsection
