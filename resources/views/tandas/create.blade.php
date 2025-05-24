@extends('dashboard.body.main')

@section('container')
<div class="container">
    <h2>Crear Nueva Tanda</h2>

    <form action="{{ route('tandas.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Descripción</label>
            <input type="text" name="description" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Monto total</label>
            <input type="number" name="total_amount" class="form-control" step="0.01" required>
        </div>

        <div class="mb-3">
            <label>Monto por periodo</label>
            <input type="number" name="payment_amount" class="form-control" step="0.01" required>
        </div>

        <div class="mb-3">
            <label>Periodo de pago</label>
            <select name="payment_period" class="form-control" required>
                <option value="semana">Semana</option>
                <option value="quincena">Quincena</option>
                <option value="mes">Mes</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Clientes participantes y posición</label>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Seleccionar</th>
                        <th>Nombre del Cliente</th>
                        <th>Posición</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="customers[{{ $customer->id }}][selected]" value="1" class="form-check-input customer-checkbox">
                            </td>
                            <td>{{ $customer->tit_name }}</td>
                            <td>
                                <input type="number" name="customers[{{ $customer->id }}][position]" class="form-control position-input" min="1" >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <small>Marca los clientes que participarán y asigna su posición en la tanda</small>
        </div>

        <div class="mb-3">
            <label>Duración de la tanda (número de periodos)</label>
            <input type="number" name="duration" class="form-control" required min="1">
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
@endsection

<script>
    // Habilita o deshabilita el campo de posición según si el checkbox está marcado
    document.querySelectorAll('.customer-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const input = this.closest('tr').querySelector('.position-input');
            input.disabled = !this.checked;
            if (!this.checked) {
                input.value = ''; // limpia el valor si se desmarca
            }
        });
    });
</script>
