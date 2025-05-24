@extends('dashboard.body.main')

@section('container')
<div class="container">
    <h2>Editar Tanda #{{ $tanda->id }}</h2>

    <form action="{{ route('tandas.update', $tanda->id) }}" method="POST">
        @csrf
        @method('PUT')

        <h4>Clientes actuales</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Eliminar</th>
                    <th>Nombre</th>
                    <th>Posición</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tanda->clients->sortBy('pivot.position') as $client)
                <tr>
                    <td>
                        <input type="checkbox" name="remove_clients[]" value="{{ $client->id }}">
                    </td>
                    <td>{{ $client->tit_name }}</td>
                    <td>
                        <input type="number" name="positions[{{ $client->id }}]" value="{{ $client->pivot->position }}" min="1" class="form-control" style="width: 80px;">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h4>Agregar nuevos clientes</h4>
        <select name="new_clients[]" multiple class="form-control" style="height: 200px;">
            @foreach ($customers as $customer)
                @if (!$tanda->clients->contains('id', $customer->id))
                    <option value="{{ $customer->id }}">{{ $customer->tit_name }}</option>
                @endif
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
        <a href="{{ route('tandas.show', $tanda->id) }}" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</div>
@endsection
