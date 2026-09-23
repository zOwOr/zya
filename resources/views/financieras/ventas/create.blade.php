@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    .section-divider {
        border-top: 2px dashed #e9ecef;
        margin: 1.5rem 0;
    }
    .imei-status-badge {
        font-size: 0.85rem;
        padding: 0.35rem 0.65rem;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-cart-plus text-primary mr-2"></i>Nueva Venta / Crédito
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Registre la venta y financiamiento del equipo telefónico.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar a Ventas
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

            <form action="{{ route('financieras.ventas.store') }}" method="POST" id="saleForm">
                @csrf

                <!-- SECCIÓN 1: DATOS DEL EQUIPO (IMEI) -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 text-white font-weight-bold">
                            <i class="fa-solid fa-mobile-screen mr-2"></i>1. Dispositivo / IMEI
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Ingresar IMEI <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="imei" id="imeiInput" class="form-control font-weight-bold font-size-16 @error('imei') is-invalid @enderror"
                                        placeholder="Ingrese 15 dígitos de IMEI" value="{{ old('imei') }}" required autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="btnVerifyImei">
                                            <i class="fa-solid fa-magnifying-glass mr-1"></i>Verificar
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="device_id" id="deviceIdInput" value="{{ old('device_id') }}">
                                @error('imei')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                                <div id="imeiFeedback" class="mt-2"></div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Sucursal de Venta <span class="text-danger">*</span></label>
                                @can('financieras.inventario.all_branches')
                                    <select name="branch_id" id="branchSelect" class="form-control @error('branch_id') is-invalid @enderror" required>
                                        <option value="">Seleccione sucursal...</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ (old('branch_id', auth()->user()->branch_id) == $b->id) ? 'selected' : '' }}>
                                                {{ $b->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ auth()->user()->branch?->name ?? 'Sucursal asignada' }}" readonly>
                                    <input type="hidden" name="branch_id" id="branchSelect" value="{{ auth()->user()->branch_id }}">
                                @endcan
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Vendedor <span class="text-danger">*</span></label>
                                @if(auth()->user()?->can('financieras.ventas.assign_seller') || auth()->user()?->isSuperAdmin())
                                    <select name="seller_id" class="form-control @error('seller_id') is-invalid @enderror" required>
                                        @foreach ($sellers as $s)
                                            <option value="{{ $s->id }}" {{ (old('seller_id', auth()->id()) == $s->id) ? 'selected' : '' }}>
                                                {{ $s->name }} ({{ $s->username ?? 'User' }})
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }}" readonly>
                                    <input type="hidden" name="seller_id" value="{{ auth()->id() }}">
                                    <small class="text-muted"><i class="fa-solid fa-lock mr-1"></i>Asignado automáticamente a tu usuario</small>
                                @endif
                                @error('seller_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Campos editables del equipo si no existía en inventario -->
                        <div class="row" id="deviceDetailsRow">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Marca</label>
                                <div class="position-relative">
                                    <input type="text" name="brand_name" id="brandInput" class="form-control @error('brand_name') is-invalid @enderror" placeholder="Ej. Samsung, Apple..." value="{{ old('brand_name') }}" autocomplete="off">
                                    <input type="hidden" name="brand_id" id="brandIdInput" value="{{ old('brand_id') }}">
                                    <div id="brandSuggestions" class="dropdown-menu w-100 shadow" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                @error('brand_name')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                                <input type="text" name="model" id="modelInput" class="form-control @error('model') is-invalid @enderror" placeholder="Ej. Galaxy A54, iPhone 13" value="{{ old('model') }}" required>
                                @error('model')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Color</label>
                                <input type="text" name="color" id="colorInput" class="form-control" placeholder="Ej. Negro, Azul" value="{{ old('color') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: DATOS DEL CLIENTE (CAMPOS LIBRES SIN RESTRICCIONES OBLIGATORIAS) -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0 text-white font-weight-bold">
                            <i class="fa-solid fa-user mr-2"></i>2. Datos del Cliente (Campos Libres)
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted font-size-13 mb-3">
                            <i class="fa-solid fa-info-circle mr-1"></i>Ningún dato del cliente está condicionado. Puede llenar solo los campos que tenga disponibles.
                        </p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Nombre Completo del Cliente</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="Nombre completo" value="{{ old('customer_name') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Teléfono de Contacto</label>
                                <input type="text" name="customer_phone" class="form-control" placeholder="10 dígitos" value="{{ old('customer_phone') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Correo Electrónico</label>
                                <input type="email" name="customer_email" class="form-control" placeholder="cliente@correo.com" value="{{ old('customer_email') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Folio de INE / Identificación</label>
                                <input type="text" name="customer_ine" class="form-control" placeholder="Clave de elector o folio" value="{{ old('customer_ine') }}">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-bold">Dirección / Domicilio</label>
                                <input type="text" name="customer_address" class="form-control" placeholder="Calle, número, colonia, ciudad" value="{{ old('customer_address') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Chip Ingresado (SIM)</label>
                                <input type="text" name="customer_chip" class="form-control" placeholder="Operadora / Número de SIM" value="{{ old('customer_chip') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Facebook</label>
                                <input type="text" name="customer_facebook" class="form-control" placeholder="URL o nombre de perfil" value="{{ old('customer_facebook') }}">
                            </div>
                        </div>

                        <hr class="section-divider">
                        <h6 class="font-weight-bold text-secondary mb-3"><i class="fa-solid fa-users mr-1"></i>Referencias Personales</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #1 — Nombre</label>
                                <input type="text" name="ref1_name" class="form-control" placeholder="Nombre completo" value="{{ old('ref1_name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #1 — Celular</label>
                                <input type="text" name="ref1_phone" class="form-control" placeholder="10 dígitos" value="{{ old('ref1_phone') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #2 — Nombre</label>
                                <input type="text" name="ref2_name" class="form-control" placeholder="Nombre completo" value="{{ old('ref2_name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #2 — Celular</label>
                                <input type="text" name="ref2_phone" class="form-control" placeholder="10 dígitos" value="{{ old('ref2_phone') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #3 — Nombre</label>
                                <input type="text" name="ref3_name" class="form-control" placeholder="Nombre completo" value="{{ old('ref3_name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #3 — Celular</label>
                                <input type="text" name="ref3_phone" class="form-control" placeholder="10 dígitos" value="{{ old('ref3_phone') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: CONDICIONES FINANCIERAS -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0 text-white font-weight-bold">
                            <i class="fa-solid fa-calculator mr-2"></i>3. Financiamiento y Condiciones Económicas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Financiera <span class="text-danger">*</span></label>
                                <select name="financiera_id" class="form-control @error('financiera_id') is-invalid @enderror" required>
                                    <option value="">-- Seleccionar Financiera --</option>
                                    @foreach ($financieras as $f)
                                        <option value="{{ $f->id }}" {{ old('financiera_id') == $f->id ? 'selected' : '' }}>
                                            {{ $f->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('financiera_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">TAG / No. Contrato</label>
                                <input type="text" name="tag_contrato" class="form-control" placeholder="Ej. PAY-00123" value="{{ old('tag_contrato') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Precio Total ($) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0" name="price" id="priceInput" class="form-control font-weight-bold text-success font-size-16 @error('price') is-invalid @enderror" value="{{ old('price') }}" placeholder="0.00" required>
                                </div>
                                @error('price')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Enganche Inicial ($)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0" name="down_payment" id="downPaymentInput" class="form-control font-weight-bold" value="{{ old('down_payment', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Enganche con Descuento ($)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0" name="enganche_descuento" class="form-control" value="{{ old('enganche_descuento', 0) }}" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Monto Financiado ($)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0" name="credit_amount" id="creditAmountInput" class="form-control font-weight-bold text-primary" value="{{ old('credit_amount', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Abono Semanal ($)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" step="0.01" min="0" name="abono_semanal" class="form-control" value="{{ old('abono_semanal', 0) }}" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Plazo en Semanas</label>
                                <input type="number" min="1" max="104" name="term_weeks" class="form-control" placeholder="Ej. 12, 24, 52" value="{{ old('term_weeks') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Plazo en Meses</label>
                                <input type="number" min="1" max="72" name="term_months" class="form-control" placeholder="Ej. 6, 12, 18, 24" value="{{ old('term_months') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Fecha de Venta</label>
                                <input type="datetime-local" name="sale_date" class="form-control" value="{{ old('sale_date', now()->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Nota Libre / Observaciones de Venta</label>
                                <textarea name="initial_note" class="form-control" rows="2" placeholder="Cualquier acuerdo particular, número de contrato externa o comentario...">{{ old('initial_note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-5">
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-light border mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fa-solid fa-check mr-2"></i>Registrar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('specificpagescripts')
<script>
$(document).ready(function() {
    // Live IMEI Verification
    $('#btnVerifyImei, #imeiInput').on('blur change click', function(e) {
        if (e.type === 'click' && this.id !== 'btnVerifyImei') return;
        let imei = $('#imeiInput').val().trim();
        if (!imei) return;

        $('#imeiFeedback').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Verificando IMEI...</span>');

        $.get("{{ route('financieras.devices.lookup-imei') }}", { imei: imei }, function(res) {
            if (res.found) {
                let d = res.device;
                $('#deviceIdInput').val(d.id);
                $('#modelInput').val(d.model).prop('readonly', true);
                $('#colorInput').val(d.color).prop('readonly', true);
                if (d.brand) {
                    $('#brandInput').val(d.brand.name).prop('readonly', true);
                    $('#brandIdInput').val(d.brand.id);
                }
                if (d.branch_id) {
                    $('#branchSelect').val(d.branch_id);
                }

                if (d.status === 'vendido') {
                    $('#imeiFeedback').html('<div class="alert alert-danger py-1 mb-0"><i class="fa-solid fa-ban mr-1"></i>Este equipo ya figura como VENDIDO. Folio venta: ' + (d.latest_sale ? d.latest_sale.sale_code : '') + '</div>');
                } else if (d.status === 'robado') {
                    $('#imeiFeedback').html('<div class="alert alert-danger py-1 mb-0"><i class="fa-solid fa-triangle-exclamation mr-1"></i>¡ALERTA! Este equipo tiene reporte de ROBO activo.</div>');
                } else if (d.status === 'en_garantia') {
                    $('#imeiFeedback').html('<div class="alert alert-warning py-1 mb-0"><i class="fa-solid fa-wrench mr-1"></i>Este equipo se encuentra actualmente en GARANTÍA.</div>');
                } else {
                    $('#imeiFeedback').html('<div class="alert alert-success py-1 mb-0"><i class="fa-solid fa-circle-check mr-1"></i>Equipo disponible en inventario (' + (d.branch ? d.branch.name : '') + ').</div>');
                }
            } else {
                $('#deviceIdInput').val('');
                $('#modelInput').prop('readonly', false);
                $('#colorInput').prop('readonly', false);
                $('#brandInput').prop('readonly', false);
                $('#imeiFeedback').html('<div class="alert alert-info py-1 mb-0"><i class="fa-solid fa-plus-circle mr-1"></i>IMEI nuevo. Se registrará automáticamente en el inventario al guardar la venta.</div>');
            }
        }).fail(function() {
            $('#imeiFeedback').html('');
        });
    });

    // Auto-calculate credit amount
    $('#priceInput, #downPaymentInput').on('input', function() {
        let p = parseFloat($('#priceInput').val()) || 0;
        let d = parseFloat($('#downPaymentInput').val()) || 0;
        let credit = Math.max(0, p - d);
        $('#creditAmountInput').val(credit.toFixed(2));
    });

    // Brand Autocomplete
    let timeout = null;
    $('#brandInput').on('input focus', function() {
        clearTimeout(timeout);
        let q = $(this).val();
        timeout = setTimeout(function() {
            $.get("{{ route('financieras.brands.autocomplete') }}", { q: q }, function(brands) {
                let dropdown = $('#brandSuggestions');
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
        $('#brandInput').val($(this).data('name'));
        $('#brandIdInput').val($(this).data('id'));
        $('#brandSuggestions').hide();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#brandInput, #brandSuggestions').length) {
            $('#brandSuggestions').hide();
        }
    });
});
</script>
@endsection
