@extends('dashboard.body.main')

@section('container')
    <div class="container">
        <h2 class="mb-4">
            Corte Diario - {{ \Carbon\Carbon::now()->format('d/m/Y') }}
            <small class="text-muted d-block" style="font-size: 1rem;">
                Sucursal: {{ auth()->user()->branch->name ?? 'Sin sucursal asignada' }}
            </small>
        </h2>

        <a href="{{ route('cash.index') }}" class="btn btn-secondary mb-3">Volver</a>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white text-center">
                        <h3>Total Ingresos</h3>
                    </div>
                    <div class="card-body text-center">
                        <h1>${{ number_format($totalIncome, 2) }}</h1>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white text-center">
                        <h3>Total Egresos</h3>
                    </div>
                    <div class="card-body text-center">
                        <h1>${{ number_format($totalExpense, 2) }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <h5>Movimientos de Hoy</h5>
        <table class="table table-bordered">
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
                        <td>{{ $flow->created_at->format('H:i') }}</td>
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
                        <td colspan="6" class="text-center">No hay movimientos para hoy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $cashFlows->links('pagination::bootstrap-5') }}
        </div>

        @php
            $cutExists = \App\Models\DailyCut::where('date', \Carbon\Carbon::today())->exists();
        @endphp

        <form method="POST" action="{{ route('cash.applyCut') }}">
            @csrf
            <button type="submit" class="btn btn-primary mb-3" @if($cutExists) disabled @endif>
                Aplicar Corte del Día
            </button>
        </form>

        @if ($cutExists)
            <div class="alert alert-info">El corte del día ya fue aplicado.</div>
        @endif
    </div>
@endsection
