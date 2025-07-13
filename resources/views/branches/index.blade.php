@extends('dashboard.body.main')

@section('container')
<div class="container">
    <h2>Lista de Sucursales</h2>

    <a href="{{ route('branches.create') }}" class="btn btn-success mb-3">Nueva Sucursal</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($branches as $branch)
                <tr>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->address }}</td>
                    <td>{{ $branch->phone }}</td>
                    <td>
                        <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-primary btn-sm">Editar</a>
                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar sucursal?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $branches->links() }}
</div>
@endsection
