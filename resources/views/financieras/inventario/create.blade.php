@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-mobile-screen-button text-primary mr-2"></i>Registrar Dispositivo en Inventario
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Agregue un equipo nuevo por su número de IMEI.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar al Inventario
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
                    <form action="{{ route('financieras.inventario.store') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Número de IMEI <span class="text-danger">*</span></label>
                            <input type="text" name="imei" class="form-control font-weight-bold font-size-16 @error('imei') is-invalid @enderror" placeholder="Ingrese 15 dígitos de IMEI" value="{{ old('imei') }}" required autocomplete="off">
                            @error('imei')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">El IMEI debe ser único en el sistema.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Marca</label>
                                <div class="position-relative">
                                    <input type="text" name="brand_name" id="invBrandInput" class="form-control @error('brand_name') is-invalid @enderror" placeholder="Ej. Samsung, Apple, Xiaomi..." value="{{ old('brand_name') }}" autocomplete="off">
                                    <input type="hidden" name="brand_id" id="invBrandIdInput" value="{{ old('brand_id') }}">
                                    <div id="invBrandSuggestions" class="dropdown-menu w-100 shadow" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                @error('brand_name')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                                <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" placeholder="Ej. Galaxy A54, Redmi Note 13" value="{{ old('model') }}" required>
                                @error('model')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Color</label>
                                <input type="text" name="color" class="form-control @error('color') is-invalid @enderror" placeholder="Ej. Negro espacial, Azul, Blanco" value="{{ old('color') }}">
                                @error('color')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Sucursal de Entrada <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Seleccione sucursal...</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ (old('branch_id', auth()->user()->branch_id) == $b->id) ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Notas u Observaciones del Equipo</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Estado físico, proveedor de origen, etc.">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="fa-solid fa-check mr-2"></i>Guardar en Inventario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('specificpagescripts')
<script>
$(document).ready(function() {
    let timeout = null;
    $('#invBrandInput').on('input focus', function() {
        clearTimeout(timeout);
        let q = $(this).val();
        timeout = setTimeout(function() {
            $.get("{{ route('financieras.brands.autocomplete') }}", { q: q }, function(brands) {
                let dropdown = $('#invBrandSuggestions');
                dropdown.empty();
                if (brands.length > 0) {
                    brands.forEach(function(b) {
                        dropdown.append('<a class="dropdown-item brand-item" href="#" data-id="' + b.id + '" data-name="' + b.name + '">' + b.name + '</a>');
                    });
                    dropdown.show();
                } else {
                    dropdown.hide();
                }
            });
        }, 200);
    });

    $(document).on('click', '.brand-item', function(e) {
        e.preventDefault();
        $('#invBrandInput').val($(this).data('name'));
        $('#invBrandIdInput').val($(this).data('id'));
        $('#invBrandSuggestions').hide();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#invBrandInput, #invBrandSuggestions').length) {
            $('#invBrandSuggestions').hide();
        }
    });
});
</script>
@endsection
