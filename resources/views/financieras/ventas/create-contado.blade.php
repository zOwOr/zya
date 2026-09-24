@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    .receipt-preview-card {
        background: #ffffff;
        border: 2px solid #2c3e50;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        font-family: Arial, Helvetica, sans-serif;
    }
    .receipt-header-box {
        border-bottom: 2px solid #2c3e50;
        padding-bottom: 10px;
    }
    .receipt-title {
        font-size: 1.4rem;
        font-weight: 900;
        letter-spacing: -0.5px;
        color: #1a202c;
    }
    .receipt-tagline {
        font-size: 0.75rem;
        font-weight: bold;
        color: #4a5568;
        letter-spacing: 0.5px;
    }
    .receipt-date-box {
        border: 1.5px solid #2c3e50;
        text-align: center;
        border-radius: 4px;
        background-color: #f8fafc;
    }
    .receipt-date-header {
        font-size: 0.7rem;
        font-weight: 900;
        background: #e2e8f0;
        padding: 2px 0;
        border-bottom: 1px solid #2c3e50;
    }
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
    }
    .receipt-table th {
        border: 1.5px solid #2c3e50;
        background-color: #edf2f7;
        font-size: 0.75rem;
        font-weight: 900;
        text-transform: uppercase;
        padding: 5px 6px;
        text-align: center;
    }
    .receipt-table td {
        border: 1.5px solid #2c3e50;
        padding: 6px;
        font-size: 0.8rem;
    }
    .receipt-total-box {
        border: 2px solid #2c3e50;
        background-color: #f7fafc;
        padding: 8px 12px;
        border-radius: 4px;
    }
    .receipt-signature-line {
        border-top: 1.5px solid #2c3e50;
        width: 80%;
        margin: 55px auto 5px auto;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header Navegación -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-success px-3 py-2 mr-2 font-size-14">
                            <i class="fa-solid fa-money-bill-wave mr-1"></i>VENTA AL CONTADO
                        </span>
                        <h4 class="mb-0 font-weight-bold">Nueva Venta Directa (Nota de Venta)</h4>
                    </div>
                    <p class="text-muted mb-0 font-size-13 mt-1">
                        Registro ágil de venta en una sola exhibición con impacto directo en el inventario disponible.
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    @can('financieras.ventas.create')
                        <a href="{{ route('financieras.ventas.create') }}" class="btn btn-outline-primary btn-sm mr-2" title="Cambiar a venta financiada">
                            <i class="fa-solid fa-credit-card mr-1"></i>Ir a Venta a Crédito
                        </a>
                    @endcan
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar a Ventas
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i><strong>Por favor corrige los siguientes detalles:</strong>
                    <ul class="mb-0 mt-2 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('financieras.ventas.store-contado') }}" method="POST" id="contadoSaleForm" autocomplete="off">
                @csrf

                <div class="row">
                    <!-- Columna Izquierda: Formulario de Captura -->
                    <div class="col-xl-7 col-lg-6 mb-4">

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
                                            <input type="text" class="form-control bg-light font-weight-bold" value="{{ auth()->user()->branch?->name ?? 'Sucursal asignada' }}" readonly>
                                            <input type="hidden" name="branch_id" id="branchSelect" value="{{ auth()->user()->branch_id }}">
                                        @endcan
                                        @error('branch_id')
                                            <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="font-weight-bold">Vendedor <span class="text-danger">*</span></label>
                                        @if(auth()->user()?->can('financieras.ventas.assign_seller') || auth()->user()?->isSuperAdmin())
                                            <select name="seller_id" id="sellerSelect" class="form-control @error('seller_id') is-invalid @enderror" required>
                                                @foreach ($sellers as $s)
                                                    <option value="{{ $s->id }}" {{ (old('seller_id', auth()->id()) == $s->id) ? 'selected' : '' }}>
                                                        {{ $s->name }} ({{ $s->username ?? 'User' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control bg-light font-weight-bold" value="{{ auth()->user()->name }}" readonly>
                                            <input type="hidden" name="seller_id" id="sellerSelect" value="{{ auth()->id() }}">
                                            <small class="text-muted"><i class="fa-solid fa-lock mr-1"></i>Asignado automáticamente a tu usuario</small>
                                        @endif
                                        @error('seller_id')
                                            <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Detalles del equipo obtenidos por IMEI o editables si es nuevo -->
                                <div class="row pt-2 border-top">
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold font-size-13 text-secondary">Marca</label>
                                        <div class="position-relative">
                                            <input type="text" name="brand_name" id="brandInput" class="form-control" placeholder="Ej. Samsung, Apple..." value="{{ old('brand_name') }}" autocomplete="off">
                                            <input type="hidden" name="brand_id" id="brandIdInput" value="{{ old('brand_id') }}">
                                            <div id="brandSuggestions" class="dropdown-menu w-100 shadow" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold font-size-13 text-secondary">Modelo <span class="text-danger">*</span></label>
                                        <input type="text" name="model" id="modelInput" class="form-control @error('model') is-invalid @enderror" placeholder="Ej. Galaxy A56 5G" value="{{ old('model') }}" required autocomplete="off">
                                        @error('model')
                                            <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold font-size-13 text-secondary">Color</label>
                                        <input type="text" name="color" id="colorInput" class="form-control" placeholder="Ej. Gris, Negro" value="{{ old('color') }}" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold font-size-13 text-secondary">Capacidad / RAM / Almacenamiento</label>
                                        <input type="text" name="storage" id="storageInput" class="form-control" placeholder="Ej. 256 GB y 8 RAM" value="{{ old('storage') }}" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold font-size-13 text-secondary">Póliza / Garantía <span class="text-danger">*</span></label>
                                        <input type="text" name="warranty_text" id="warrantyInput" class="form-control font-weight-bold" placeholder="Ej. 1 Mes de garantía" value="{{ old('warranty_text', '1 Mes de garantía') }}" required autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: DATOS BÁSICOS DEL CLIENTE (BASADOS EN LA NOTA FÍSICA) -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="mb-0 text-white font-weight-bold">
                                    <i class="fa-solid fa-user mr-2"></i>2. Información Básica del Cliente
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted font-size-13 mb-3">
                                    <i class="fa-solid fa-receipt mr-1"></i>Información básica requerida para la nota de venta y entrega del equipo.
                                </p>
                                <div class="row">
                                    <div class="col-md-7 mb-3">
                                        <label class="font-weight-bold">CLIENTE (Nombre Completo) <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" id="customerNameInput" class="form-control font-weight-bold @error('customer_name') is-invalid @enderror"
                                            placeholder="Ej. Margarita Reynol" value="{{ old('customer_name') }}" required autocomplete="off">
                                        @error('customer_name')
                                            <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label class="font-weight-bold">TELÉFONO (Contacto)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-phone"></i></span></div>
                                            <input type="text" name="customer_phone" id="customerPhoneInput" class="form-control"
                                                placeholder="Ej. 8123704411" value="{{ old('customer_phone') }}" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="font-weight-bold">DIRECCIÓN (Domicilio)</label>
                                        <input type="text" name="customer_address" id="customerAddressInput" class="form-control"
                                            placeholder="Calle, número, colonia, municipio..." value="{{ old('customer_address') }}" autocomplete="off">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold">R.F.C.</label>
                                        <input type="text" name="customer_rfc" id="customerRfcInput" class="form-control text-uppercase"
                                            placeholder="RFC del cliente (opcional)" value="{{ old('customer_rfc') }}" autocomplete="off" maxlength="15">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: PRECIO Y CONDICIONES DE PAGO -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="mb-0 text-white font-weight-bold">
                                    <i class="fa-solid fa-hand-holding-dollar mr-2"></i>3. Condiciones Económicas (Pago Único)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-5 mb-3">
                                        <label class="font-weight-bold font-size-15 text-success">
                                            PRECIO UNITARIO / TOTAL AL CONTADO ($) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text font-weight-bold font-size-18 bg-light text-success">$</span></div>
                                            <input type="number" step="0.01" min="0" name="price" id="priceInput"
                                                class="form-control font-weight-bold text-success font-size-22 @error('price') is-invalid @enderror"
                                                value="{{ old('price') }}" placeholder="0.00" required autocomplete="off">
                                        </div>
                                        @error('price')
                                            <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold">Método de Pago</label>
                                        <select name="payment_method" id="paymentMethodSelect" class="form-control custom-select">
                                            <option value="Efectivo" {{ old('payment_method') == 'Efectivo' ? 'selected' : '' }}>💵 Efectivo</option>
                                            <option value="Transferencia" {{ old('payment_method') == 'Transferencia' ? 'selected' : '' }}>🏦 Transferencia SPEI</option>
                                            <option value="Tarjeta de Débito" {{ old('payment_method') == 'Tarjeta de Débito' ? 'selected' : '' }}>💳 Tarjeta</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="font-weight-bold">Fecha de Venta</label>
                                        <input type="datetime-local" name="sale_date" id="saleDateInput" class="form-control"
                                            value="{{ old('sale_date', now()->format('Y-m-d\TH:i')) }}" autocomplete="off">
                                    </div>

                                    <div class="col-md-12 mb-2">
                                        <label class="font-weight-bold">Observaciones / Notas Adicionales</label>
                                        <textarea name="initial_note" class="form-control" rows="2" placeholder="Observaciones adicionales, estado físico del equipo, accesorios incluidos..." autocomplete="off">{{ old('initial_note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-light border px-4 mr-2">
                                    <i class="fa-solid fa-xmark mr-1"></i>Cancelar
                                </a>
                                @can('financieras.ventas.create')
                                    <a href="{{ route('financieras.ventas.create') }}" class="btn btn-outline-primary">
                                        <i class="fa-solid fa-credit-card mr-1"></i>Ir a Venta a Crédito
                                    </a>
                                @endcan
                            </div>
                            <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold shadow-sm" id="btnSubmitSale">
                                <i class="fa-solid fa-check mr-2"></i>Registrar Venta de Contado
                            </button>
                        </div>
                    </div>

                    <!-- Columna Derecha: Vista Previa en Vivo de la Nota Física -->
                    <div class="col-xl-5 col-lg-6 mb-4">
                        <div class="sticky-top" style="top: 20px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="font-weight-bold text-dark font-size-13 text-uppercase">
                                    <i class="fa-solid fa-eye text-primary mr-1"></i>Vista Previa de la Nota de Venta
                                </span>
                                <span class="badge badge-light border text-muted">Diseño de nota física</span>
                            </div>

                            <div class="receipt-preview-card p-4">
                                <!-- Membrete ZYA -->
                                <div class="receipt-header-box mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="receipt-title">
                                                <i class="fa-solid fa-mobile-screen mr-1 text-primary"></i>ZYA
                                            </div>
                                            <div class="receipt-tagline">
                                                ZYA CELULARES. ACCESORIOS. REPARACIONES
                                            </div>
                                            <div class="font-size-11 text-muted mt-1" id="previewBranchInfo">
                                                <strong>Sucursal:</strong> <span id="previewBranchName">{{ auth()->user()->branch?->name ?? 'ZYA Central' }}</span>
                                            </div>
                                        </div>
                                        <div class="receipt-date-box p-2" style="min-width: 110px;">
                                            <div class="receipt-date-header">FECHA</div>
                                            <div class="d-flex justify-content-around text-center pt-1 font-size-11">
                                                <div>
                                                    <span class="d-block text-muted font-size-9">DÍA</span>
                                                    <strong id="previewDateDay">{{ now()->format('d') }}</strong>
                                                </div>
                                                <div class="border-left px-1">
                                                    <span class="d-block text-muted font-size-9">MES</span>
                                                    <strong id="previewDateMonth">{{ now()->format('m') }}</strong>
                                                </div>
                                                <div class="border-left pl-1">
                                                    <span class="d-block text-muted font-size-9">AÑO</span>
                                                    <strong id="previewDateYear">{{ now()->format('y') }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos del Cliente en la Nota -->
                                <div class="mb-3 font-size-12">
                                    <div class="row no-gutters mb-1">
                                        <div class="col-8">
                                            <strong>CLIENTE:</strong>
                                            <span id="previewCustomerName" class="text-primary font-weight-bold ml-1">Margarita Reynol</span>
                                        </div>
                                        <div class="col-4 text-right">
                                            <strong>TEL:</strong>
                                            <span id="previewCustomerPhone" class="ml-1">8123704411</span>
                                        </div>
                                    </div>
                                    <div class="mb-1">
                                        <strong>DIRECCIÓN:</strong>
                                        <span id="previewCustomerAddress" class="ml-1 text-muted">---------------------------------</span>
                                    </div>
                                    <div>
                                        <strong>R.F.C.:</strong>
                                        <span id="previewCustomerRfc" class="ml-1 text-muted">-----------------</span>
                                    </div>
                                </div>

                                <!-- Tabla de Productos -->
                                <table class="receipt-table mb-3">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%;">CANT.</th>
                                            <th style="width: 55%;">DESCRIPCIÓN</th>
                                            <th style="width: 17%;">P. UNIT.</th>
                                            <th style="width: 18%;">IMPORTE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center font-weight-bold align-top">1</td>
                                            <td class="align-top">
                                                <div id="previewDescription" class="font-weight-bold text-dark">
                                                    Galaxy A56 5G Gris con 256 GB y 8 RAM de contado
                                                </div>
                                                <div class="text-muted font-size-11" id="previewImei">
                                                    Imei: 352953411674504
                                                </div>
                                                <div class="text-primary font-size-11 font-weight-bold" id="previewWarranty">
                                                    (1 Mes de garantia)
                                                </div>
                                            </td>
                                            <td class="text-right font-weight-bold align-top" id="previewUnitPrice">
                                                $0.00
                                            </td>
                                            <td class="text-right font-weight-bold align-top" id="previewSubtotal">
                                                $0.00
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Totales & Letra -->
                                <div class="row align-items-center mb-3">
                                    <div class="col-7">
                                        <div class="font-size-10 font-weight-bold text-uppercase text-muted">Importe total con letra:</div>
                                        <div class="font-size-11 font-weight-bold text-dark font-italic" id="previewPriceInWords">
                                            CERO PESOS 00/100 M.N.
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="receipt-total-box text-right">
                                            <span class="font-size-11 font-weight-bold d-block text-secondary">TOTAL</span>
                                            <span class="font-size-20 font-weight-bold text-success" id="previewTotalPrice">$0.00</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Espacio de Firma -->
                                <div class="text-center pt-2">
                                    <div class="receipt-signature-line"></div>
                                    <span class="font-size-11 font-weight-bold text-uppercase text-secondary">FIRMA DE CONFORMIDAD DEL CLIENTE</span>
                                    <div class="font-size-11 text-muted" id="previewSignName">Margarita Reynol</div>
                                </div>
                            </div>

                            <div class="text-center mt-2 text-muted font-size-12">
                                <i class="fa-solid fa-print mr-1"></i>Al registrarse se podrá imprimir en este mismo formato físico.
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('specificpagescripts')
<script>
$(document).ready(function() {
    // Desactivar historial del navegador en todos los campos
    $('#contadoSaleForm').attr('autocomplete', 'off').find('input, textarea').attr('autocomplete', 'off');

    // 1. Verificación en Vivo de IMEI
    // Live IMEI Verification
    $('#btnVerifyImei, #imeiInput').on('blur change click', function(e) {
        if (e.type === 'click' && this.id !== 'btnVerifyImei') return;
        let imei = $('#imeiInput').val().trim();
        if (!imei) {
            $('#imeiFeedback').html('');
            $('#deviceIdInput').val('');
            $('#modelInput').val('').prop('readonly', false).removeClass('bg-light');
            $('#colorInput').val('').prop('readonly', false).removeClass('bg-light');
            $('#storageInput').val('').prop('readonly', false).removeClass('bg-light');
            $('#brandInput').val('').prop('readonly', false).removeClass('bg-light');
            $('#brandIdInput').val('');
            updateLivePreview();
            return;
        }

        $('#imeiFeedback').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Verificando IMEI...</span>');

        $.get("{{ route('financieras.devices.lookup-imei') }}", { imei: imei }, function(res) {
            if (res.found) {
                let d = res.device;
                $('#deviceIdInput').val(d.id);
                // Bloquear estrictamente la edición manual de la información del producto
                $('#modelInput').val(d.model).prop('readonly', true).addClass('bg-light');
                $('#colorInput').val(d.color || '').prop('readonly', true).addClass('bg-light');
                $('#storageInput').val(d.storage || '').prop('readonly', true).addClass('bg-light');
                if (d.brand) {
                    $('#brandInput').val(d.brand.name).prop('readonly', true).addClass('bg-light');
                    $('#brandIdInput').val(d.brand.id);
                } else {
                    $('#brandInput').val('').prop('readonly', true).addClass('bg-light');
                    $('#brandIdInput').val('');
                }
                $('#brandSuggestions').hide();
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
                    $('#imeiFeedback').html('<div class="alert alert-success py-1 mb-0"><i class="fa-solid fa-circle-check mr-1"></i>Equipo disponible en inventario (' + (d.branch ? d.branch.name : '') + '). Información del producto cargada y bloqueada para proteger inventario.</div>');
                }
            } else {
                $('#deviceIdInput').val('');
                $('#modelInput').prop('readonly', false).removeClass('bg-light');
                $('#colorInput').prop('readonly', false).removeClass('bg-light');
                $('#storageInput').prop('readonly', false).removeClass('bg-light');
                $('#brandInput').prop('readonly', false).removeClass('bg-light');
                $('#brandIdInput').val('');
                $('#imeiFeedback').html('<div class="alert alert-info py-1 mb-0"><i class="fa-solid fa-plus-circle mr-1"></i>IMEI nuevo. Se registrará automáticamente en el inventario al guardar la venta.</div>');
            }
            updateLivePreview();
        }).fail(function() {
            $('#imeiFeedback').html('');
        });
    });

    // 2. Autocompletado de Marcas (bloqueado si el producto ya fue cargado por IMEI)
    let brandTimeout = null;
    $('#brandInput').on('input focus', function() {
        if ($(this).prop('readonly')) {
            $('#brandSuggestions').hide();
            return;
        }
        clearTimeout(brandTimeout);
        let q = $(this).val();
        brandTimeout = setTimeout(function() {
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
        updateLivePreview();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#brandInput, #brandSuggestions').length) {
            $('#brandSuggestions').hide();
        }
    });

    // 3. Conversión de Números a Letras en Español para la Vista Previa
    function convertNumberToWords(amount) {
        let num = parseFloat(amount) || 0;
        let intVal = Math.floor(num);
        let cents = Math.round((num - intVal) * 100);
        let centsText = (cents < 10 ? '0' : '') + cents + '/100 M.N.';

        if (intVal === 0) return 'CERO PESOS ' + centsText;

        function getUnits(n) {
            const units = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
                'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
                'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
            return units[n] || '';
        }

        function getTens(n) {
            const tens = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
            return tens[n] || '';
        }

        function getHundreds(n) {
            const hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
            return hundreds[n] || '';
        }

        function convertGroup(n) {
            if (n === 0) return '';
            if (n < 30) return getUnits(n);
            if (n < 100) {
                let u = n % 10;
                let d = Math.floor(n / 10);
                return getTens(d) + (u > 0 ? ' Y ' + getUnits(u) : '');
            }
            if (n === 100) return 'CIEN';
            if (n < 1000) {
                let h = Math.floor(n / 100);
                let r = n % 100;
                return getHundreds(h) + (r > 0 ? ' ' + convertGroup(r) : '');
            }
            if (n < 1000000) {
                let th = Math.floor(n / 1000);
                let r = n % 1000;
                let thText = th === 1 ? 'MIL' : convertGroup(th) + ' MIL';
                return thText + (r > 0 ? ' ' + convertGroup(r) : '');
            }
            return n.toString();
        }

        return convertGroup(intVal) + ' PESOS ' + centsText;
    }

    // 4. Actualización en tiempo real de la Nota de Venta (Preview)
    function updateLivePreview() {
        let client = $('#customerNameInput').val().trim() || 'Margarita Reynol';
        let phone = $('#customerPhoneInput').val().trim() || '----------';
        let address = $('#customerAddressInput').val().trim() || '---------------------------------';
        let rfc = $('#customerRfcInput').val().trim().toUpperCase() || '-----------------';

        let brand = $('#brandInput').val().trim();
        let model = $('#modelInput').val().trim() || 'Dispositivo';
        let color = $('#colorInput').val().trim();
        let storage = $('#storageInput').val().trim();
        let imei = $('#imeiInput').val().trim() || '---------------';
        let warranty = $('#warrantyInput').val().trim() || '1 Mes de garantía';
        let price = parseFloat($('#priceInput').val()) || 0;

        // Fecha
        let saleDateVal = $('#saleDateInput').val();
        if (saleDateVal) {
            let d = new Date(saleDateVal);
            $('#previewDateDay').text(('0' + d.getDate()).slice(-2));
            $('#previewDateMonth').text(('0' + (d.getMonth() + 1)).slice(-2));
            $('#previewDateYear').text(d.getFullYear().toString().slice(-2));
        }

        // Sucursal
        let branchText = $('#branchSelect option:selected').text();
        if (branchText && branchText.trim()) {
            $('#previewBranchName').text(branchText.trim());
        }

        // Cliente
        $('#previewCustomerName').text(client);
        $('#previewCustomerPhone').text(phone);
        $('#previewCustomerAddress').text(address);
        $('#previewCustomerRfc').text(rfc);
        $('#previewSignName').text(client);

        // Descripción armada exactamente como en la nota física:
        // [Marca] [Modelo] [Color] con [Storage] de contado
        let descParts = [];
        if (brand) descParts.push(brand);
        if (model) descParts.push(model);
        if (color) descParts.push(color);
        if (storage) descParts.push('con ' + storage);
        descParts.push('de contado');

        $('#previewDescription').text(descParts.join(' '));
        $('#previewImei').text('Imei: ' + imei);
        $('#previewWarranty').text('(' + warranty + ')');

        // Precios
        let formattedPrice = '$' + price.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        $('#previewUnitPrice').text(formattedPrice);
        $('#previewSubtotal').text(formattedPrice);
        $('#previewTotalPrice').text(formattedPrice);
        $('#previewPriceInWords').text(convertNumberToWords(price));
    }

    // Escuchadores para actualizar en tiempo real
    $('#customerNameInput, #customerPhoneInput, #customerAddressInput, #customerRfcInput').on('input', updateLivePreview);
    $('#brandInput, #modelInput, #colorInput, #storageInput, #warrantyInput').on('input', updateLivePreview);
    $('#priceInput, #saleDateInput, #branchSelect').on('input change', updateLivePreview);

    // Ejecutar inicialización
    updateLivePreview();

    // 5. Prevenir envío si el IMEI tiene reporte o está vendido
    $('#contadoSaleForm').on('submit', function(e) {
        if ($('#imeiFeedback .alert-danger').length > 0) {
            e.preventDefault();
            alert('No es posible registrar la venta: el dispositivo ya fue vendido o presenta alertas de reporte de robo.');
            $('#imeiInput').focus();
            return false;
        }

        let p = parseFloat($('#priceInput').val()) || 0;
        if (p <= 0) {
            if (!confirm('El precio total de la venta es $0.00. ¿Deseas continuar?')) {
                e.preventDefault();
                $('#priceInput').focus();
                return false;
            }
        }
    });
});
</script>
@endsection
