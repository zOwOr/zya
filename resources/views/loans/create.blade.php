@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold"><i class="fa-solid fa-file-invoice-dollar text-primary mr-2"></i>Crear Nuevo Préstamo</h4>
                    <p class="mb-0 text-muted font-size-13">
                        El monto prestado no afecta egreso de caja; únicamente se refleja en caja la <strong>Comisión por Apertura</strong> y los abonos posteriores.
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
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="font-weight-bold mb-0 text-primary">
                                    1. Selección de Cliente y Tipo de Préstamo
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
                                            <label class="font-weight-bold mb-0">Tipo de Préstamo <span class="text-danger">*</span></label>
                                            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.catalogs'))
                                                <a href="{{ route('loans.types.index') }}" class="font-size-12 font-weight-bold text-primary">
                                                    <i class="fa-solid fa-sliders mr-1"></i>Gestionar Catálogo de Tipos
                                                </a>
                                            @endif
                                        </div>
                                        <select name="loan_type_id" id="loanTypeSelect" class="form-control select2-autocomplete" required>
                                            @foreach ($loanTypes as $lt)
                                                <option value="{{ $lt->id }}"
                                                    data-rate="{{ $lt->default_interest_rate }}"
                                                    data-fee="{{ $lt->default_opening_fee }}"
                                                    data-frequency="{{ $lt->default_frequency }}"
                                                    data-periods="{{ $lt->default_periods }}"
                                                    data-fields='@json($lt->required_fields ?? [])'
                                                    {{ old('loan_type_id') == $lt->id ? 'selected' : '' }}>
                                                    {{ $lt->name }} — {{ $lt->description }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Sección dinámica según el tipo de préstamo seleccionado -->
                                <div class="bg-light rounded p-3 border mt-2">
                                    <h6 class="font-weight-bold text-dark mb-2">
                                        <i class="fa-solid fa-clipboard-list mr-1 text-primary"></i>Información Requerida según el Tipo de Préstamo
                                    </h6>
                                    <div id="dynamicLoanTypeFields" class="row"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Evidencia en Video (Opcional - Solo SuperAdmin puede ver/editar después) y Pagaré -->
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

                    <!-- Columna Derecha: Condiciones Financieras y Comisión por Apertura -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-primary text-white py-3">
                                <h6 class="font-weight-bold mb-0 text-white">
                                    3. Condiciones Financieras y Comisión por Apertura
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Capital Prestado ($) *</label>
                                        <input type="number" step="0.01" min="1" name="principal_amount" id="principalInput" class="form-control font-weight-bold" value="{{ old('principal_amount', 1000) }}" required>
                                        <small class="text-muted">No descuenta de caja</small>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Interés (%) *</label>
                                        <input type="number" step="0.01" min="0" name="interest_rate" id="rateInput" class="form-control" value="{{ old('interest_rate', 20) }}" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">Frecuencia *</label>
                                        <select name="payment_frequency" id="frequencyInput" class="form-control" required>
                                            <option value="semanal">Semanal</option>
                                            <option value="quincenal">Quincenal</option>
                                            <option value="mensual">Mensual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">No. de Pagos *</label>
                                        <input type="number" min="1" max="240" name="term_periods" id="periodsInput" class="form-control" value="{{ old('term_periods', 8) }}" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="font-weight-bold">Fecha Inicio *</label>
                                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                    </div>
                                </div>

                                <div class="alert alert-info py-2 px-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Total a Pagar (Capital + Interés):</span>
                                        <strong id="calcTotalDisplay">$1,200.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span>Pago Programado por Cuota:</span>
                                        <strong id="calcQuotaDisplay">$150.00</strong>
                                    </div>
                                </div>

                                <hr>
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
    const rateInput = document.getElementById('rateInput');
    const frequencyInput = document.getElementById('frequencyInput');
    const periodsInput = document.getElementById('periodsInput');
    const commissionInput = document.getElementById('commissionInput');
    const calcTotalDisplay = document.getElementById('calcTotalDisplay');
    const calcQuotaDisplay = document.getElementById('calcQuotaDisplay');
    const commMethodSelect = document.getElementById('commMethodSelect');
    const commMixedBox = document.getElementById('commMixedBox');

    function updateFinancialPreview() {
        const principal = parseFloat(principalInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const periods = Math.max(1, parseInt(periodsInput.value) || 1);
        const total = principal + (principal * (rate / 100));
        const quota = total / periods;

        calcTotalDisplay.textContent = '$' + total.toFixed(2);
        calcQuotaDisplay.textContent = '$' + quota.toFixed(2);
    }

    function renderLoanTypeFields(applyDefaults = false) {
        const option = loanTypeSelect.options[loanTypeSelect.selectedIndex];
        if (!option) return;

        if (applyDefaults) {
            rateInput.value = option.dataset.rate || 0;
            commissionInput.value = option.dataset.fee || 0;
            frequencyInput.value = option.dataset.frequency || 'semanal';
            periodsInput.value = option.dataset.periods || 4;
            updateFinancialPreview();
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

    // Autocomplete con Select2 para búsqueda rápida de clientes
    $('#customerSelect').select2({
        placeholder: '-- Escribe para buscar cliente por nombre o teléfono --',
        allowClear: true,
        width: '100%'
    });

    $('#customerSelect').on('change', function () {
        checkCustomerOpenLoans(this.value);
    });

    $('#loanTypeSelect').on('change', function () {
        renderLoanTypeFields(true);
    });

    [principalInput, rateInput, periodsInput].forEach(el => {
        el.addEventListener('input', updateFinancialPreview);
    });

    commMethodSelect.addEventListener('change', function () {
        if (this.value === 'mixto') {
            commMixedBox.classList.remove('d-none');
        } else {
            commMixedBox.classList.add('d-none');
        }
    });

    renderLoanTypeFields(true);
    if (customerSelect.value) {
        checkCustomerOpenLoans(customerSelect.value);
    }
});
</script>
@endsection

