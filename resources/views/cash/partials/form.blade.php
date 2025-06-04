<div class="card mb-4">
    <div class="card-header">Registrar Movimiento Manual</div>
    <div class="card-body">
        <form method="POST" action="{{ route('cash.store') }}">
            @csrf
            <div class="row mb-3">
                <div class="col-md-3">
                    <select name="type" class="form-control" required>
                        <option value="">Tipo</option>
                        <option value="income">Ingreso</option>
                        <option value="expense">Egreso</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="Monto" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="module" class="form-control" placeholder="Módulo (opcional)">
                </div>
                <div class="col-md-3">
                    <input type="text" name="reference" class="form-control" placeholder="Referencia (opcional)">
                </div>
            </div>
            <div class="mb-3">
                <textarea name="description" class="form-control" placeholder="Descripción" rows="2"></textarea>
            </div>
            <button class="btn btn-success">Registrar Movimiento</button>
        </form>
    </div>
</div>
