@extends('dashboard.body.main')

@section('container')
<div class="container">
    <h2>Listado de Tandas</h2>
    <a href="{{ route('tandas.create') }}" class="btn btn-primary mb-3">Crear Nueva Tanda</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Total</th>
                <th>Periodo</th>
                <th>Monto por periodo</th>
                <th>Clientes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tandas as $tanda)
                <tr>
                    <td>{{ $tanda->description }}</td>
                    <td>${{ number_format($tanda->total_amount, 2) }}</td>
                    <td>{{ ucfirst($tanda->payment_period) }}</td>
                    <td>${{ number_format($tanda->payment_amount, 2) }}</td>
                    <td>{{ $tanda->clients->count() }}</td>
                    <td>
                        <a href="{{ route('tandas.show', $tanda) }}" class="btn btn-sm btn-info">Ver</a>
                    </td>
                    <td>
                        <a href="{{ route('tandas.edit', $tanda) }}" class="btn btn-sm btn-warning">Editar participantes</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
