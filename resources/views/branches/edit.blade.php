@extends('dashboard.body.main')

@section('container')
<div class="container">
    <h2>Editar Sucursal</h2>

    <form action="{{ route('branches.update', $branch->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Nombre de Sucursal <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $branch->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="address">Dirección</label>
            <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $branch->address) }}">
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="phone">Teléfono</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $branch->phone) }}">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-3">Actualizar Sucursal</button>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</div>
@endsection
