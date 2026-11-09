@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">Editar Participantes — {{ $tanda->description }}</h4>
                    <p class="mb-0 text-muted font-size-13">
                        Edita datos básicos o agrega nuevos clientes directamente a la tanda.
                    </p>
                </div>
                <a href="{{ route('tandas.show', $tanda->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Volver a la Tanda
                </a>
            </div>

            <form action="{{ route('tandas.update', $tanda->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="font-weight-bold mb-0 text-primary">Participantes Actuales</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 90px;"># Turno</th>
                                        <th>Nombre del Cliente</th>
                                        <th>Teléfono</th>
                                        <th>Dirección / Referencia</th>
                                        <th class="text-center" style="width: 100px;">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tanda->participants as $p)
                                        <tr>
                                            <td>
                                                <input type="number" name="participants[{{ $p->id }}][position]" value="{{ $p->position }}" class="form-control form-control-sm text-center font-weight-bold" min="1" required>
                                            </td>
                                            <td>
                                                <input type="text" name="participants[{{ $p->id }}][customer_name]" value="{{ $p->display_name }}" class="form-control form-control-sm" required>
                                            </td>
                                            <td>
                                                <input type="text" name="participants[{{ $p->id }}][customer_phone]" value="{{ $p->customer_phone }}" class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <input type="text" name="participants[{{ $p->id }}][customer_address]" value="{{ $p->customer_address }}" class="form-control form-control-sm">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="remove_participants[]" value="{{ $p->id }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold mb-0 text-success">Agregar Nuevos Participantes</h6>
                        <button type="button" id="addNewParticipantRow" class="btn btn-sm btn-outline-success">
                            <i class="fa-solid fa-plus mr-1"></i>Otra Fila
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0" id="newParticipantsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 90px;"># Turno</th>
                                    <th>Nombre Completo</th>
                                    <th>Teléfono</th>
                                    <th>Dirección / Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="number" name="new_participants[0][position]" class="form-control form-control-sm text-center" min="1" placeholder="Auto"></td>
                                    <td><input type="text" name="new_participants[0][customer_name]" class="form-control form-control-sm" placeholder="Nombre del nuevo participante..."></td>
                                    <td><input type="text" name="new_participants[0][customer_phone]" class="form-control form-control-sm" placeholder="Teléfono..."></td>
                                    <td><input type="text" name="new_participants[0][customer_address]" class="form-control form-control-sm" placeholder="Dirección / nota..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let idx = 1;
    const btn = document.getElementById('addNewParticipantRow');
    const tbody = document.querySelector('#newParticipantsTable tbody');
    if (btn && tbody) {
        btn.addEventListener('click', function () {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="number" name="new_participants[${idx}][position]" class="form-control form-control-sm text-center" min="1" placeholder="Auto"></td>
                <td><input type="text" name="new_participants[${idx}][customer_name]" class="form-control form-control-sm" placeholder="Nombre del nuevo participante..."></td>
                <td><input type="text" name="new_participants[${idx}][customer_phone]" class="form-control form-control-sm" placeholder="Teléfono..."></td>
                <td><input type="text" name="new_participants[${idx}][customer_address]" class="form-control form-control-sm" placeholder="Dirección / nota..."></td>
            `;
            tbody.appendChild(tr);
            idx++;
        });
    }
});
</script>
@endsection
