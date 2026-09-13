@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-shield-halved text-danger mr-2"></i>Registrar Reporte de Robo
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Reporte de equipo sustraído, extraviado o denunciado ante autoridades.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.index', ['tab' => 'robos']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar a Robos
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
                    <form action="{{ route('financieras.robos.store') }}" method="POST">
                        @csrf

                        <!-- IMEI Input -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">IMEI del Dispositivo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="imei" id="roboImeiInput" class="form-control font-weight-bold font-size-16 @error('imei') is-invalid @enderror"
                                    placeholder="Ingrese 15 dígitos de IMEI" value="{{ old('imei', $prefilledImei) }}" required autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger" id="btnLookupImeiRobo">
                                        <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar IMEI
                                    </button>
                                </div>
                            </div>
                            @error('imei')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                            <div id="roboImeiFeedback" class="mt-2"></div>
                        </div>

                        <!-- Datos del equipo -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Marca</label>
                                <div class="position-relative">
                                    <input type="text" name="brand_name" id="roboBrandInput" class="form-control @error('brand_name') is-invalid @enderror" placeholder="Marca del equipo"
                                        value="{{ old('brand_name', $prefilledDevice?->brand?->name) }}" autocomplete="off">
                                    <input type="hidden" name="brand_id" id="roboBrandIdInput" value="{{ old('brand_id', $prefilledDevice?->brand_id) }}">
                                    <div id="roboBrandSuggestions" class="dropdown-menu w-100 shadow" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                @error('brand_name')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Modelo</label>
                                <input type="text" name="model" id="roboModelInput" class="form-control @error('model') is-invalid @enderror" placeholder="Modelo"
                                    value="{{ old('model', $prefilledDevice?->model) }}">
                                @error('model')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Color</label>
                                <input type="text" name="color" id="roboColorInput" class="form-control" placeholder="Color"
                                    value="{{ old('color', $prefilledDevice?->color) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Sucursal <span class="text-danger">*</span></label>
                                <select name="branch_id" id="roboBranchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
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
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Fecha del Incidente <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="incident_date" class="form-control @error('incident_date') is-invalid @enderror" value="{{ old('incident_date', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('incident_date')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">No. de Acta / Carpeta de Investigación</label>
                                <input type="text" name="police_report_number" class="form-control" placeholder="Ej. CI-FGE-2026-0984" value="{{ old('police_report_number') }}">
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Narración o Descripción de los Hechos <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Detalles de cómo, cuándo y dónde ocurrió el incidente, persona que reporta, datos relevantes..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.index', ['tab' => 'robos']) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-danger px-4 font-weight-bold">
                                <i class="fa-solid fa-shield-halved mr-2"></i>Registrar Reporte de Robo
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
    $('#btnLookupImeiRobo, #roboImeiInput').on('blur change click', function(e) {
        if (e.type === 'click' && this.id !== 'btnLookupImeiRobo') return;
        let imei = $('#roboImeiInput').val().trim();
        if (!imei) return;

        $('#roboImeiFeedback').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Verificando IMEI...</span>');

        $.get("{{ route('financieras.devices.lookup-imei') }}", { imei: imei }, function(res) {
            if (res.found) {
                let d = res.device;
                $('#roboModelInput').val(d.model);
                $('#roboColorInput').val(d.color);
                if (d.brand) {
                    $('#roboBrandInput').val(d.brand.name);
                    $('#roboBrandIdInput').val(d.brand.id);
                }
                if (d.branch_id) {
                    $('#roboBranchSelect').val(d.branch_id);
                }

                let saleInfo = '';
                if (d.latest_sale) {
                    saleInfo = ' | Venta: <strong>' + d.latest_sale.sale_code + '</strong> (' + (d.latest_sale.customer_name || 'Cliente sin nombre') + ')';
                }

                $('#roboImeiFeedback').html('<div class="alert alert-success py-1 mb-0"><i class="fa-solid fa-check-circle mr-1"></i>Dispositivo localizado: ' + (d.brand ? d.brand.name : '') + ' ' + d.model + saleInfo + '</div>');
            } else {
                $('#roboImeiFeedback').html('<div class="alert alert-info py-1 mb-0"><i class="fa-solid fa-info-circle mr-1"></i>IMEI nuevo. Se registrará en inventario con estado ROBADO.</div>');
            }
        });
    });

    if ($('#roboImeiInput').val()) {
        $('#btnLookupImeiRobo').click();
    }

    // Brand autocomplete
    let timeout = null;
    $('#roboBrandInput').on('input focus', function() {
        clearTimeout(timeout);
        let q = $(this).val();
        timeout = setTimeout(function() {
            $.get("{{ route('financieras.brands.autocomplete') }}", { q: q }, function(brands) {
                let dropdown = $('#roboBrandSuggestions');
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
        $('#roboBrandInput').val($(this).data('name'));
        $('#roboBrandIdInput').val($(this).data('id'));
        $('#roboBrandSuggestions').hide();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#roboBrandInput, #roboBrandSuggestions').length) {
            $('#roboBrandSuggestions').hide();
        }
    });
});
</script>
@endsection
