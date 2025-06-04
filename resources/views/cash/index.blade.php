@extends('dashboard.body.main')

@section('container')
    <div class="container">
        <h2 class="mb-4">Historial de Movimientos de Caja</h2>

        <div class="form-group row align-items-center">
            <div class="col-md-12">
                <a href="{{ route('cash.daily-cut') }}" class="btn btn-lg btn-primary mb-3">Ver Corte Diario</a>
                <a href="{{ route('cash.index') }}" class="btn btn-lg btn-secondary mb-3">Actualizar</a>
            </div>
        </div>



        {{-- Formulario para filtrar por fecha --}}
        <form method="GET" action="{{ route('cash.index') }}" class="mb-3 d-flex align-items-center gap-2">
            <label for="date">Seleccionar fecha:</label>
            <input type="date" name="date" id="date" value="{{ $date ?? '' }}" class="form-control"
                style="max-width: 180px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('cash.index') }}" class="btn btn-secondary">Limpiar filtro</a>
        </form>


        <table class="table table-bordered table-striped">
            <thead> … </thead>
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
        <div class="mt-3">
            {{ $cashFlows->links('pagination::bootstrap-5') }}
        </div>

        @include('cash.partials.form')


        {{ $cashFlows->withQueryString()->links() }}
    </div>
@endsection
