@extends('dashboard.body.main')

@section('container')
<style>
    .tanda-grid-wrapper {
        max-height: 480px;
        overflow: auto;
        position: relative;
    }
    .tanda-grid-wrapper thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
    }
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: #ffffff;
        z-index: 5;
        border-right: 2px solid #dee2e6;
    }
    .input-green { background-color: #d4edda !important; font-weight: bold; }
    .input-yellow { background-color: #fff3cd !important; }
    .input-white { background-color: #ffffff !important; }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold"><i class="fa-solid fa-piggy-bank text-primary mr-2"></i>{{ $tanda->description }}</h4>
            <p class="mb-0 text-muted font-size-13">
                Sucursal: <strong>{{ $tanda->branch?->name ?? 'General' }}</strong> |
                Total Tanda: <strong>${{ number_format($tanda->total_amount, 2) }}</strong> |
                Aportación ({{ ucfirst($tanda->payment_period) }}): <strong>${{ number_format($tanda->payment_amount, 2) }}</strong>
            </p>
        </div>
        <div>
            @if (auth()->user()->can('tandas.edit'))
                <a href="{{ route('tandas.edit', $tanda->id) }}" class="btn btn-warning btn-sm mr-1">
                    <i class="fa-solid fa-users-gear mr-1"></i>Editar Participantes
                </a>
            @endif
            <a href="{{ route('tandas.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left mr-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="font-weight-bold mb-0 text-primary">Matriz de Pagos por Participante y Periodo</h6>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <div class="custom-control custom-checkbox mr-3">
                    <input type="checkbox" class="custom-control-input" id="recordCashFlowCheck" checked>
                    <label class="custom-control-label font-size-13 font-weight-bold text-success" for="recordCashFlowCheck">
                        Impactar nuevos abonos en Caja
                    </label>
                </div>
                <select id="tandaPaymentMethod" class="form-control form-control-sm mr-2" style="width: 150px;">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
                <button id="savePayments" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Pagos
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="tanda-grid-wrapper">
                <table class="table table-bordered mb-0" style="min-width: max-content;">
                    <thead>
                        <tr>
                            <th class="sticky-col text-center">Periodo / Vencimiento</th>
                            @foreach ($tanda->participants as $participant)
                                <th class="text-center">
                                    <span class="badge badge-primary">#{{ $participant->position }}</span>
                                    <div class="font-weight-bold text-dark mt-1">{{ $participant->display_name }}</div>
                                    <small class="text-muted">{{ $participant->display_phone }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tanda->periods as $period)
                            <tr>
                                <td class="sticky-col text-center font-weight-bold">
                                    Periodo {{ $period->period_number }}
                                    <small class="d-block text-muted">{{ $period->due_date }}</small>
                                </td>
                                @foreach ($tanda->participants as $participant)
                                    @php
                                        $paymentsArr = is_array($participant->payments)
                                            ? $participant->payments
                                            : (json_decode($participant->payments ?? '{}', true) ?: []);
                                        $paymentValue = $paymentsArr[$period->period_number] ?? '';
                                    @endphp
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm payment-input text-right"
                                            data-participant="{{ $participant->id }}"
                                            data-period="{{ $period->period_number }}"
                                            value="{{ $paymentValue }}"
                                            min="0"
                                            step="0.01"
                                            style="min-width: 95px;">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentAmount = parseFloat({{ (float) $tanda->payment_amount }});

    function updateInputColor(input) {
        const valStr = input.value;
        const val = parseFloat(valStr);
        input.classList.remove('input-green', 'input-yellow', 'input-white');
        if (valStr === '' || isNaN(val) || val === 0) {
            input.classList.add('input-white');
        } else if (Math.abs(val - paymentAmount) < 0.01) {
            input.classList.add('input-green');
        } else {
            input.classList.add('input-yellow');
        }
    }

    document.querySelectorAll('.payment-input').forEach(function (inp) {
        updateInputColor(inp);
        inp.addEventListener('input', function () {
            updateInputColor(this);
        });
    });

    const saveBtn = document.getElementById('savePayments');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const paymentsData = [];
            document.querySelectorAll('.payment-input').forEach(function (inp) {
                paymentsData.push({
                    participant_id: inp.dataset.participant,
                    period: inp.dataset.period,
                    amount: inp.value
                });
            });

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Guardando...';

            fetch("{{ route('tandas.payments.update', $tanda->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    payments: paymentsData,
                    record_cashflow: document.getElementById('recordCashFlowCheck').checked ? 1 : 0,
                    payment_method: document.getElementById('tandaPaymentMethod').value
                })
            })
            .then(r => r.json())
            .then(res => {
                alert(res.message || 'Pagos guardados correctamente.');
            })
            .catch(() => {
                alert('Error al guardar los pagos.');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Pagos';
            });
        });
    }
});
</script>
@endsection
