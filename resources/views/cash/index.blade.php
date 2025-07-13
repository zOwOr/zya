@extends('dashboard.body.main')

@section('container')
    <div class="container">

        {{-- ✅ Mostrar la sucursal actual --}}
        <h2 class="mb-4">
            Historial de Movimientos de Caja
            <small class="text-muted d-block" style="font-size: 1rem;">
                Sucursal: {{ auth()->user()->branch->name ?? 'Sin sucursal asignada' }}
            </small>
        </h2>

        <div class="form-group row align-items-center">
            <div class="col-md-12 mb-3 d-flex gap-2 flex-wrap">
                <a href="{{ route('cash.dailyCut') }}" class="btn btn-lg btn-primary">Ver Corte Diario</a>
                <a href="{{ route('cash.index') }}" class="btn btn-lg btn-secondary">Actualizar</a>
            </div>
        </div>

        {{-- 🔍 Filtro por fecha --}}
        <form method="GET" action="{{ route('cash.index') }}" class="mb-3 d-flex align-items-center gap-2">
            <label for="date" class="form-label m-0">Seleccionar fecha:</label>
            <input type="date" name="date" id="date" value="{{ $date ?? '' }}" class="form-control" style="max-width: 180px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('cash.index') }}" class="btn btn-secondary">Limpiar filtro</a>
        </form>

        {{-- 🧾 Tabla de movimientos --}}
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Descripción</th>
                    <th>Módulo</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cashFlows as $flow)
                    <tr>
                        <td>{{ $flow->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ $flow->type == 'income' ? 'success' : 'danger' }}">
                                {{ $flow->type == 'income' ? 'Ingreso' : 'Egreso' }}
                            </span>
                        </td>
                        <td>${{ number_format($flow->amount, 2) }}</td>
                        <td>{{ $flow->description }}</td>
                        <td>{{ $flow->module }}</td>
                        <td>{{ $flow->reference }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay movimientos para esta fecha.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- 📄 Paginación --}}
        <div class="mt-3">
            {{ $cashFlows->links('pagination::bootstrap-5') }}
        </div>

        {{-- ➕ Formulario adicional (ej. registrar nuevo movimiento) --}}
        @include('cash.partials.form')

    </div>
@endsection
