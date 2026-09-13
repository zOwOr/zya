@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-wrench text-warning mr-2"></i>Registrar Solicitud de Garantía
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Apertura de caso de garantía para un equipo telefónico.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar a Garantías
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
                    <form action="{{ route('financieras.garantias.store') }}" method="POST">
                        @csrf

                        <!-- IMEI Input -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">IMEI del Dispositivo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="imei" id="warImeiInput" class="form-control font-weight-bold font-size-16 @error('imei') is-invalid @enderror"
                                    placeholder="Ingrese 15 dígitos de IMEI" value="{{ old('imei', $prefilledImei) }}" required autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" id="btnLookupImeiWar">
                                        <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar IMEI
                                    </button>
                                </div>
                            </div>
                            @error('imei')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                            <div id="warImeiFeedback" class="mt-2"></div>
                        </div>

                        <!-- Datos del equipo -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Marca</label>
                                <div class="position-relative">
                                    <input type="text" name="brand_name" id="warBrandInput" class="form-control @error('brand_name') is-invalid @enderror" placeholder="Marca del equipo"
                                        value="{{ old('brand_name', $prefilledDevice?->brand?->name) }}" autocomplete="off">
                                    <input type="hidden" name="brand_id" id="warBrandIdInput" value="{{ old('brand_id', $prefilledDevice?->brand_id) }}">
                                    <div id="warBrandSuggestions" class="dropdown-menu w-100 shadow" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                @error('brand_name')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Modelo</label>
                                <input type="text" name="model" id="warModelInput" class="form-control @error('model') is-invalid @enderror" placeholder="Modelo"
                                    value="{{ old('model', $prefilledDevice?->model) }}">
                                @error('model')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Color</label>
                                <input type="text" name="color" id="warColorInput" class="form-control" placeholder="Color"
                                    value="{{ old('color', $prefilledDevice?->color) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Sucursal de Recepción <span class="text-danger">*</span></label>
                                <select name="branch_id" id="warBranchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Seleccione sucursal...</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ (old('branch_id', $prefilledDevice?->branch_id ?? auth()->user()->branch_id) == $b->id) ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Etapa Inicial <span class="text-danger">*</span></label>
                                <select name="current_stage_id" class="form-control @error('current_stage_id') is-invalid @enderror" required>
                                    @foreach ($stages as $st)
                                        <option value="{{ $st->id }}" {{ old('current_stage_id') == $st->id ? 'selected' : '' }}>
                                            {{ $st->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('current_stage_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Descripción Detallada de la Falla <span class="text-danger">*</span></label>
                            <textarea name="issue_description" class="form-control @error('issue_description') is-invalid @enderror" rows="4" placeholder="Describa el problema que presenta el equipo (ej. No carga, pantalla no enciende, problemas de señal...)" required>{{ old('issue_description') }}</textarea>
                            @error('issue_description')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white">
                                <i class="fa-solid fa-wrench mr-2"></i>Registrar Garantía
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
    $('#btnLookupImeiWar, #warImeiInput').on('blur change click', function(e) {
        if (e.type === 'click' && this.id !== 'btnLookupImeiWar') return;
        let imei = $('#warImeiInput').val().trim();
        if (!imei) return;

        $('#warImeiFeedback').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Verificando IMEI...</span>');

        $.get("{{ route('financieras.devices.lookup-imei') }}", { imei: imei }, function(res) {
            if (res.found) {
                let d = res.device;
                $('#warModelInput').val(d.model);
                $('#warColorInput').val(d.color);
                if (d.brand) {
                    $('#warBrandInput').val(d.brand.name);
                    $('#warBrandIdInput').val(d.brand.id);
                }
                if (d.branch_id) {
                    $('#warBranchSelect').val(d.branch_id);
                }

                let saleInfo = '';
                if (d.latest_sale) {
                    saleInfo = ' | Venta vinculada: <strong>' + d.latest_sale.sale_code + '</strong> (' + (d.latest_sale.customer_name || 'Cliente sin nombre') + ')';
                }

                $('#warImeiFeedback').html('<div class="alert alert-success py-1 mb-0"><i class="fa-solid fa-check-circle mr-1"></i>Equipo encontrado: ' + (d.brand ? d.brand.name : '') + ' ' + d.model + saleInfo + '</div>');
            } else {
                $('#warImeiFeedback').html('<div class="alert alert-info py-1 mb-0"><i class="fa-solid fa-info-circle mr-1"></i>IMEI no registrado previamente. Se creará en el sistema al guardar la garantía.</div>');
            }
        });
    });

    if ($('#warImeiInput').val()) {
        $('#btnLookupImeiWar').click();
    }

    // Brand autocomplete
    let timeout = null;
    $('#warBrandInput').on('input focus', function() {
        clearTimeout(timeout);
        let q = $(this).val();
        timeout = setTimeout(function() {
            $.get("{{ route('financieras.brands.autocomplete') }}", { q: q }, function(brands) {
                let dropdown = $('#warBrandSuggestions');
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
        $('#warBrandInput').val($(this).data('name'));
        $('#warBrandIdInput').val($(this).data('id'));
        $('#warBrandSuggestions').hide();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#warBrandInput, #warBrandSuggestions').length) {
            $('#warBrandSuggestions').hide();
        }
    });
});
</script>
@endsection
