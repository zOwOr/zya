@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-pen-to-square text-primary mr-2"></i>Editar Sucursal: {{ $branch->name }}
                    </h4>
                    <p class="text-muted mb-0 font-size-13">
                        Actualiza la dirección y teléfono para que se reflejen correctamente en el sistema y en los comprobantes en PDF.
                    </p>
                </div>
                <div>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver a Sucursales
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <div class="font-weight-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Por favor revisa los errores:</div>
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 font-weight-bold text-dark">
                        <i class="fa-solid fa-circle-info text-primary mr-2"></i>Datos de la Sucursal
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('branches.update', $branch->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            {{-- Nombre --}}
                            <div class="col-md-6 mb-3">
                                <label for="name" class="font-weight-bold font-size-13">
                                    Nombre de la Sucursal <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0">
                                            <i class="fa-solid fa-store text-muted"></i>
                                        </span>
                                    </div>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="form-control border-left-0 @error('name') is-invalid @enderror"
                                           value="{{ old('name', $branch->name) }}"
                                           placeholder="Ej: Montemorelos o Allende"
                                           required>
                                </div>
                                @error('name') <div class="text-danger font-size-12 mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- Teléfono --}}
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="font-weight-bold font-size-13">
                                    Teléfono de Contacto
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0">
                                            <i class="fa-solid fa-phone text-muted"></i>
                                        </span>
                                    </div>
                                    <input type="text"
                                           name="phone"
                                           id="phone"
                                           class="form-control border-left-0 @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $branch->phone) }}"
                                           placeholder="Ej: 826 261 5418">
                                </div>
                                <small class="text-muted">Aparecerá en el PDF como: "Tel. [número]".</small>
                                @error('phone') <div class="text-danger font-size-12 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Dirección --}}
                        <div class="form-group mb-4">
                            <label for="address" class="font-weight-bold font-size-13">
                                Dirección Completa (para Nota de Venta / Recibo)
                            </label>
                            <textarea name="address"
                                      id="address"
                                      rows="4"
                                      class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Ejemplo:
Calle Cuauhtémoc #412,
entre Degollado y 5 de Mayo, Col. Centro">{{ old('address', $branch->address) }}</textarea>
                            <small class="form-text text-muted">
                                <i class="fa-solid fa-lightbulb text-warning mr-1"></i>
                                <strong>Tip:</strong> Puedes usar saltos de línea (Enter) para separar la calle, entre calles y colonia. En el PDF se imprimirá exactamente en esas líneas separadas.
                            </small>
                            @error('address') <div class="text-danger font-size-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Vista previa del encabezado --}}
                        <div class="card bg-light border mb-4">
                            <div class="card-header bg-transparent py-2 border-bottom">
                                <small class="font-weight-bold text-uppercase text-secondary font-size-11">
                                    <i class="fa-solid fa-eye mr-1"></i>Vista previa en el encabezado del PDF:
                                </small>
                            </div>
                            <div class="card-body py-3">
                                <div class="p-3 bg-white border rounded" style="font-family: Arial, sans-serif; font-size: 11px; max-width: 320px;">
                                    <strong id="preview-name" class="font-size-12">{{ old('name', $branch->name) ?: 'Nombre Sucursal' }}</strong><br>
                                    <span id="preview-address" style="white-space: pre-line; color: #333;">{{ old('address', $branch->address) ?: 'Calle y número, entrecalles, colonia' }}</span><br>
                                    <span id="preview-phone" style="color: #333;">Tel. {{ old('phone', $branch->phone) ?: '000 000 0000' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary mr-2">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Script para vista previa reactiva --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const phoneInput = document.getElementById('phone');
        const addressInput = document.getElementById('address');

        const prevName = document.getElementById('preview-name');
        const prevPhone = document.getElementById('preview-phone');
        const prevAddress = document.getElementById('preview-address');

        function updatePreview() {
            if (prevName) prevName.textContent = nameInput.value.trim() || 'Nombre Sucursal';
            if (prevAddress) prevAddress.textContent = addressInput.value.trim() || 'Calle y número, entrecalles, colonia';
            if (prevPhone) {
                const phone = phoneInput.value.trim();
                prevPhone.textContent = phone ? ('Tel. ' + phone) : '';
            }
        }

        if (nameInput) nameInput.addEventListener('input', updatePreview);
        if (phoneInput) phoneInput.addEventListener('input', updatePreview);
        if (addressInput) addressInput.addEventListener('input', updatePreview);
    });
</script>
@endpush
@endsection
