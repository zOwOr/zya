@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold"><i class="fa-solid fa-piggy-bank text-primary mr-2"></i>Crear Nueva Tanda</h4>
                    <p class="mb-0 text-muted font-size-13">
                        Registra clientes nuevos con información básica directamente relacionados a esta tanda (lógica similar a Financieras).
                    </p>
                </div>
                <a href="{{ route('tandas.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Volver a Tandas
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('tandas.store') }}" method="POST">
                @csrf
                <div class="row">
                    <!-- Configuración de la Tanda -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-primary text-white py-3">
                                <h6 class="font-weight-bold mb-0 text-white">1. Configuración de la Tanda</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="font-weight-bold">Nombre / Descripción de la Tanda *</label>
                                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Ej. Tanda Quincenal $10,000 Grupo A" required>
                                </div>

                                @if (auth()->user()->isSuperAdmin() || auth()->user()->can('tandas.all_branches'))
                                    <div class="form-group">
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

                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Monto Total ($) *</label>
                                        <input type="number" name="total_amount" class="form-control" step="0.01" value="{{ old('total_amount', 10000) }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Aportación ($) *</label>
                                        <input type="number" name="payment_amount" class="form-control" step="0.01" value="{{ old('payment_amount', 1000) }}" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">Periodo de Pago *</label>
                                        <select name="payment_period" class="form-control" required>
                                            <option value="semana" {{ old('payment_period') === 'semana' ? 'selected' : '' }}>Semanal</option>
                                            <option value="quincena" {{ old('payment_period') === 'quincena' ? 'selected' : '' }}>Quincenal</option>
                                            <option value="mes" {{ old('payment_period') === 'mes' ? 'selected' : '' }}>Mensual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="font-weight-bold">No. de Periodos *</label>
                                        <input type="number" name="duration" id="tandaDurationInput" class="form-control" value="{{ old('duration', 10) }}" min="1" required>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="font-weight-bold">Fecha de Inicio</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registro Directo de Clientes / Participantes de la Tanda -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="font-weight-bold mb-0 text-primary">2. Clientes / Participantes de la Tanda (Información Básica)</h6>
                                    <small class="text-muted">Agrega directamente a cada cliente con su número/posición sin necesidad de registrarlo en el módulo de clientes.</small>
                                </div>
                                <button type="button" id="addParticipantBtn" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-user-plus mr-1"></i>Agregar Participante
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0" id="participantsTable">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 90px;"># Turno</th>
                                                <th>Nombre Completo del Cliente *</th>
                                                <th>Teléfono / WhatsApp</th>
                                                <th>Dirección / Referencia Básica</th>
                                                <th style="width: 60px;">Quitar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="participantsBody">
                                            <tr>
                                                <td>
                                                    <input type="number" name="participants[0][position]" class="form-control form-control-sm text-center font-weight-bold" value="1" min="1" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="participants[0][customer_name]" class="form-control form-control-sm" placeholder="Nombre del participante..." required>
                                                </td>
                                                <td>
                                                    <input type="text" name="participants[0][customer_phone]" class="form-control form-control-sm" placeholder="Ej. 5512345678">
                                                </td>
                                                <td>
                                                    <input type="text" name="participants[0][customer_address]" class="form-control form-control-sm" placeholder="Opcional...">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn" disabled>
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                <button type="button" id="generateByDurationBtn" class="btn btn-sm btn-light border">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Generar filas según No. de Periodos
                                </button>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fa-solid fa-check mr-1"></i>Guardar Tanda y Participantes
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
    const tbody = document.getElementById('participantsBody');
    const addBtn = document.getElementById('addParticipantBtn');
    const genBtn = document.getElementById('generateByDurationBtn');
    const durationInput = document.getElementById('tandaDurationInput');
    let rowIdx = 1;

    function createRow(positionNumber) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="number" name="participants[${rowIdx}][position]" class="form-control form-control-sm text-center font-weight-bold" value="${positionNumber}" min="1" required>
            </td>
            <td>
                <input type="text" name="participants[${rowIdx}][customer_name]" class="form-control form-control-sm" placeholder="Nombre del participante..." required>
            </td>
            <td>
                <input type="text" name="participants[${rowIdx}][customer_phone]" class="form-control form-control-sm" placeholder="Ej. 5512345678">
            </td>
            <td>
                <input type="text" name="participants[${rowIdx}][customer_address]" class="form-control form-control-sm" placeholder="Opcional...">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        rowIdx++;
        return tr;
    }

    function updateRemoveButtons() {
        const buttons = tbody.querySelectorAll('.remove-row-btn');
        buttons.forEach(btn => {
            btn.disabled = (buttons.length <= 1);
        });
    }

    addBtn.addEventListener('click', function () {
        const nextPos = tbody.querySelectorAll('tr').length + 1;
        tbody.appendChild(createRow(nextPos));
        updateRemoveButtons();
    });

    genBtn.addEventListener('click', function () {
        const targetCount = Math.min(50, Math.max(1, parseInt(durationInput.value) || 10));
        const currentCount = tbody.querySelectorAll('tr').length;
        for (let i = currentCount + 1; i <= targetCount; i++) {
            tbody.appendChild(createRow(i));
        }
        updateRemoveButtons();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row-btn');
        if (btn && tbody.querySelectorAll('tr').length > 1) {
            btn.closest('tr').remove();
            updateRemoveButtons();
        }
    });
});
</script>
@endsection
