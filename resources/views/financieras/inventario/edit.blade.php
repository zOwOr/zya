@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-pen-to-square text-warning mr-2"></i>Editar Dispositivo {{ $device->imei }}
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Modifique la información o estado del dispositivo.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.inventario.history', $device->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar al Historial
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('financieras.inventario.update', $device->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Número de IMEI <span class="text-danger">*</span></label>
                            <input type="text" name="imei" class="form-control font-weight-bold font-size-16 @error('imei') is-invalid @enderror" value="{{ old('imei', $device->imei) }}" required>
                            @error('imei')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Marca</label>
                                <select name="brand_id" class="form-control @error('brand_id') is-invalid @enderror">
                                    <option value="">Sin Marca</option>
                                    @foreach ($brands as $b)
                                        <option value="{{ $b->id }}" {{ old('brand_id', $device->brand_id) == $b->id ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                                <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $device->model) }}" required>
                                @error('model')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Color</label>
                                <input type="text" name="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color', $device->color) }}">
                                @error('color')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Capacidad / Almacenamiento</label>
                                <input type="text" name="storage" class="form-control @error('storage') is-invalid @enderror" placeholder="Ej. 64GB, 128GB, 256GB" value="{{ old('storage', $device->storage) }}">
                                @error('storage')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Proveedor</label>
                                <select name="supplier_id" class="form-control @error('supplier_id') is-invalid @enderror">
                                    <option value="">Sin Proveedor</option>
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ old('supplier_id', $device->supplier_id) == $sup->id ? 'selected' : '' }}>
                                            {{ $sup->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Sucursal Actual <span class="text-danger">*</span></label>
                                @can('financieras.inventario.all_branches')
                                    <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ old('branch_id', $device->branch_id) == $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ $device->branch?->name ?? 'Sin Sucursal' }}" readonly title="Para mover a otra sucursal, utilice la opción de Traspaso">
                                    <input type="hidden" name="branch_id" value="{{ $device->branch_id }}">
                                @endcan
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Estado en Inventario <span class="text-danger">*</span></label>
                                <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="disponible" {{ old('status', $device->status) == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                    <option value="vendido" {{ old('status', $device->status) == 'vendido' ? 'selected' : '' }}>Vendido</option>
                                    <option value="en_garantia" {{ old('status', $device->status) == 'en_garantia' ? 'selected' : '' }}>En Garantía</option>
                                    <option value="robado" {{ old('status', $device->status) == 'robado' ? 'selected' : '' }}>Robado</option>
                                </select>
                                @error('status')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Notas / Observaciones</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $device->notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.inventario.history', $device->id) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning px-4 font-weight-bold">
                                <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
