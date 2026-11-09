<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span><i class="las la-plus-circle mr-1"></i> Registrar Movimiento Manual de Caja</span>
        <span class="badge badge-info">Soporta Pago Mixto</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('cash.store') }}" id="manualCashForm">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branchId ?? (auth()->user()->branch_id ?? 1) }}">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Tipo de Movimiento *</label>
                    <select name="type" class="form-control" required>
                        <option value="income">Ingreso (+)</option>
                        <option value="expense">Egreso (-)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Cliente / Beneficiario</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="Ej. Juan Pérez / Proveedor">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Método de Pago *</label>
                    <select name="payment_method" id="manualPaymentMethod" class="form-control" required onchange="toggleMixedFields(this.value)">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="mixto">Pago Mixto (Efectivo / Tarjeta / Transf.)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold small">Monto Total ($) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="manualTotalAmount" class="form-control font-weight-bold" placeholder="0.00" required>
                </div>
            </div>

            <div class="row mb-3 d-none bg-light p-2 rounded border" id="manualMixedRow">
                <div class="col-md-4">
                    <label class="small font-weight-bold text-success">Monto en Efectivo ($)</label>
                    <input type="number" step="0.01" min="0" name="cash_amount" id="manualCashAmount" class="form-control form-control-sm" value="0" oninput="recalcManualMixed()">
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-primary">Monto en Tarjeta ($)</label>
                    <input type="number" step="0.01" min="0" name="card_amount" id="manualCardAmount" class="form-control form-control-sm" value="0" oninput="recalcManualMixed()">
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-info">Monto en Transferencia ($)</label>
                    <input type="number" step="0.01" min="0" name="transfer_amount" id="manualTransferAmount" class="form-control form-control-sm" value="0" oninput="recalcManualMixed()">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold small">Módulo / Categoría</label>
                    <input type="text" name="module" class="form-control" placeholder="Ej. Movimiento Manual, Financieras, Gasto...">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold small">Referencia / Folio</label>
                    <input type="text" name="reference" class="form-control" placeholder="Referencia (opcional)">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold small">Descripción / Concepto *</label>
                    <input type="text" name="description" class="form-control" placeholder="Detalle del movimiento" required>
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
</script>
