@php
    $mostUsedExpenseTypes = $mostUsedExpenseTypes ?? collect();
    $allExpenseTypes = $allExpenseTypes ?? collect();
    $oldType = old('type', 'income');
    $oldExpenseTypeId = (int) old('expense_type_id', 0);
    $canManageExpenseCatalog = auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('cash.edit'));
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span><i class="las la-plus-circle mr-1"></i> Registrar Movimiento Manual de Caja</span>
        <span class="badge badge-info">Soporta Pago Mixto y Catálogo de Egresos</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('cash.store') }}" id="manualCashForm">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branchId ?? (auth()->user()->branch_id ?? 1) }}">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Tipo de Movimiento *</label>
                    <select name="type" id="manualMovementType" class="form-control" required onchange="toggleMovementType(this.value)">
                        <option value="income" {{ $oldType === 'income' ? 'selected' : '' }}>Ingreso (+)</option>
                        <option value="expense" {{ $oldType === 'expense' ? 'selected' : '' }}>Egreso (-)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small" id="manualBeneficiaryLabel">Cliente / Beneficiario</label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="form-control" placeholder="Ej. Juan Pérez / Proveedor">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Método de Pago *</label>
                    <select name="payment_method" id="manualPaymentMethod" class="form-control" required onchange="toggleMixedFields(this.value)">
                        <option value="efectivo" {{ old('payment_method', 'efectivo') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="tarjeta" class="non-cash-opt" {{ old('payment_method') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                        <option value="transferencia" class="non-cash-opt" {{ old('payment_method') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                        <option value="mixto" class="non-cash-opt" {{ old('payment_method') === 'mixto' ? 'selected' : '' }}>Pago Mixto (Efectivo / Tarjeta / Transf.)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small" id="manualAmountLabel">Importe / Monto Total ($) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="manualTotalAmount" value="{{ old('amount') }}" class="form-control font-weight-bold" placeholder="0.00" required>
                </div>
            </div>

            <div class="row mb-3 d-none bg-light p-2 rounded border" id="manualMixedRow">
                <div class="col-md-4">
                    <label class="small font-weight-bold text-success">Monto en Efectivo ($)</label>
                    <input type="number" step="0.01" min="0" name="cash_amount" id="manualCashAmount" class="form-control form-control-sm" value="{{ old('cash_amount', 0) }}" oninput="recalcManualMixed()">
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-primary">Monto en Tarjeta ($)</label>
                    <input type="number" step="0.01" min="0" name="card_amount" id="manualCardAmount" class="form-control form-control-sm" value="{{ old('card_amount', 0) }}" oninput="recalcManualMixed()">
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-info">Monto en Transferencia ($)</label>
                    <input type="number" step="0.01" min="0" name="transfer_amount" id="manualTransferAmount" class="form-control form-control-sm" value="{{ old('transfer_amount', 0) }}" oninput="recalcManualMixed()">
                </div>
            </div>

            {{-- Menú de Tipo de Egreso con "Más usados" arriba por sucursal y lista completa con "Otro" al final --}}
            <div class="row mb-3 d-none" id="manualExpenseTypeRow">
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label font-weight-bold small mb-0 text-danger">
                            <i class="fa-solid fa-tags mr-1"></i>Tipo de Salida / Egreso *
                        </label>
                        @if($canManageExpenseCatalog)
                            <a href="{{ route('cash.expenseTypes.index') }}" class="small font-weight-bold text-primary">
                                <i class="fa-solid fa-sliders mr-1"></i>Administrar catálogo (CRUD)
                            </a>
                        @endif
                    </div>
                    <select name="expense_type_id" id="manualExpenseTypeSelect" class="form-control" onchange="handleExpenseTypeChange()">
                        <option value="">-- Selecciona un tipo de egreso --</option>
                        @if($mostUsedExpenseTypes->isNotEmpty())
                            <optgroup label="Más usados en esta sucursal">
                                @foreach($mostUsedExpenseTypes as $topType)
                                    <option value="{{ $topType->id }}"
                                        data-is-other="{{ $topType->is_other ? '1' : '0' }}"
                                        data-requires-reason="{{ $topType->requires_reason ? '1' : '0' }}">
                                        {{ $topType->full_label }} ({{ (int) $topType->usage_count }} {{ (int) $topType->usage_count === 1 ? 'uso' : 'usos' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Lista completa">
                            @foreach($allExpenseTypes as $et)
                                <option value="{{ $et->id }}"
                                    data-is-other="{{ $et->is_other ? '1' : '0' }}"
                                    data-requires-reason="{{ $et->requires_reason ? '1' : '0' }}"
                                    {{ $oldExpenseTypeId === (int) $et->id ? 'selected' : '' }}>
                                    {{ $et->full_label }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="alert alert-light border py-2 px-3 mb-0 w-100 small text-muted" id="manualExpenseHint">
                        Los tipos <strong>Más usados</strong> se ordenan automáticamente según las salidas registradas en esta sucursal.
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4" id="manualModuleCol">
                    <label class="form-label font-weight-bold small">Módulo / Categoría</label>
                    <input type="text" name="module" value="{{ old('module') }}" class="form-control" placeholder="Ej. Movimiento Manual, Financieras...">
                </div>
                <div class="col-md-4" id="manualReferenceCol">
                    <label class="form-label font-weight-bold small">Referencia / Folio</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Referencia o recibo (opcional)">
                </div>
                <div class="col-md-4" id="manualDescriptionCol">
                    <label class="form-label font-weight-bold small">
                        <span id="manualDescriptionLabel">Descripción / Concepto *</span>
                        <span class="badge badge-danger ml-1 d-none" id="manualReasonRequiredBadge">Motivo obligatorio</span>
                        <span class="badge badge-secondary ml-1 d-none" id="manualReasonOptionalBadge">Opcional</span>
                    </label>
                    <input type="text" name="description" id="manualDescriptionInput" value="{{ old('description') }}" class="form-control" placeholder="Detalle del movimiento" required>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                    <i class="las la-save mr-1"></i> Registrar Movimiento en Caja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMovementType(type) {
    const methodSel = document.getElementById('manualPaymentMethod');
    const nonCashOpts = methodSel.querySelectorAll('.non-cash-opt');
    const expenseRow = document.getElementById('manualExpenseTypeRow');
    const expenseSel = document.getElementById('manualExpenseTypeSelect');
    const moduleCol = document.getElementById('manualModuleCol');
    const descCol = document.getElementById('manualDescriptionCol');
    const descLabel = document.getElementById('manualDescriptionLabel');
    const descInput = document.getElementById('manualDescriptionInput');

    if (type === 'expense') {
        methodSel.value = 'efectivo';
        nonCashOpts.forEach(opt => opt.disabled = true);
        toggleMixedFields('efectivo');

        if (expenseRow) expenseRow.classList.remove('d-none');
        if (expenseSel) expenseSel.required = true;
        if (moduleCol) moduleCol.classList.add('d-none');
        if (descCol) {
            descCol.classList.remove('col-md-4');
            descCol.classList.add('col-md-8');
        }
        if (descLabel) descLabel.textContent = 'Motivo / Detalle del Egreso';
        if (descInput) descInput.placeholder = 'Motivo del egreso (ej. Recibo CFE / Jabón y papel / Gasolina...)';
        handleExpenseTypeChange();
    } else {
        nonCashOpts.forEach(opt => opt.disabled = false);
        if (expenseRow) expenseRow.classList.add('d-none');
        if (expenseSel) expenseSel.required = false;
        if (moduleCol) moduleCol.classList.remove('d-none');
        if (descCol) {
            descCol.classList.remove('col-md-8');
            descCol.classList.add('col-md-4');
        }
        if (descLabel) descLabel.textContent = 'Descripción / Concepto *';
        if (descInput) {
            descInput.required = true;
            descInput.placeholder = 'Detalle del movimiento';
        }
        document.getElementById('manualReasonRequiredBadge')?.classList.add('d-none');
        document.getElementById('manualReasonOptionalBadge')?.classList.add('d-none');
    }
}

function handleExpenseTypeChange() {
    const typeSel = document.getElementById('manualMovementType');
    const expenseSel = document.getElementById('manualExpenseTypeSelect');
    const descInput = document.getElementById('manualDescriptionInput');
    const reqBadge = document.getElementById('manualReasonRequiredBadge');
    const optBadge = document.getElementById('manualReasonOptionalBadge');
    const hintBox = document.getElementById('manualExpenseHint');

    if (!typeSel || typeSel.value !== 'expense' || !expenseSel || !descInput) {
        return;
    }

    const selectedOpt = expenseSel.options[expenseSel.selectedIndex];
    const requiresReason = selectedOpt && (selectedOpt.dataset.requiresReason === '1' || selectedOpt.dataset.isOther === '1');

    descInput.required = Boolean(requiresReason);
    if (requiresReason) {
        reqBadge?.classList.remove('d-none');
        optBadge?.classList.add('d-none');
        descInput.placeholder = 'Escribe obligatoriamente el motivo de este egreso...';
        if (hintBox) {
            hintBox.innerHTML = '<strong class="text-danger">Motivo obligatorio:</strong> describe claramente en qué se utilizó esta salida de efectivo.';
        }
    } else {
        reqBadge?.classList.add('d-none');
        optBadge?.classList.remove('d-none');
        descInput.placeholder = 'Motivo o detalle del egreso (ej. Recibo de luz / Compra de micas / Taxi)...';
        if (hintBox) {
            hintBox.innerHTML = 'Los tipos <strong>Más usados</strong> se ordenan automáticamente según las salidas registradas en esta sucursal.';
        }
    }
}

function toggleMixedFields(method) {
    const row = document.getElementById('manualMixedRow');
    if (method === 'mixto') {
        row.classList.remove('d-none');
    } else {
        row.classList.add('d-none');
    }
}

function recalcManualMixed() {
    const c = parseFloat(document.getElementById('manualCashAmount').value || 0);
    const d = parseFloat(document.getElementById('manualCardAmount').value || 0);
    const t = parseFloat(document.getElementById('manualTransferAmount').value || 0);
    const sum = (c + d + t).toFixed(2);
    document.getElementById('manualTotalAmount').value = sum;
}

document.addEventListener('DOMContentLoaded', function () {
    const typeSel = document.getElementById('manualMovementType');
    const methodSel = document.getElementById('manualPaymentMethod');
    if (typeSel) {
        toggleMovementType(typeSel.value);
    }
    if (methodSel && typeSel && typeSel.value !== 'expense') {
        toggleMixedFields(methodSel.value);
    }
});
</script>
