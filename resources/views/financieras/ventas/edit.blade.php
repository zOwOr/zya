@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-pen-to-square text-warning mr-2"></i>Editar Venta {{ $sale->sale_code }}
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Actualice los datos o condiciones de la venta.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.ventas.show', $sale->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar al Detalle
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

            <form action="{{ route('financieras.ventas.update', $sale->id) }}" method="POST" id="saleEditForm" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 text-white font-weight-bold">Dispositivo Vinculado</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="text-muted font-size-12">IMEI</label>
                                <div class="font-weight-bold font-size-16">{{ $sale->device?->imei ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted font-size-12">Equipo</label>
                                <div class="font-weight-bold">{{ $sale->device?->brand?->name }} {{ $sale->device?->model }} ({{ $sale->device?->color }})</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="text-muted font-size-12">Sucursal <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ old('branch_id', $sale->branch_id) == $b->id ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0 text-white font-weight-bold">Datos del Cliente</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Nombre Completo</label>
                                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $sale->customer_name) }}" autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Teléfono</label>
                                <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $sale->customer_phone) }}" autocomplete="off">
                            </div>                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">R.F.C.</label>
                                <input type="text" name="customer_rfc" class="form-control text-uppercase" placeholder="RFC del cliente" value="{{ old('customer_rfc', $sale->customer_rfc) }}" autocomplete="off" maxlength="15">
                            </div>
                            @if (!$sale->isContado())
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Email</label>
                                    <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email', $sale->customer_email) }}" autocomplete="off">
                                    @error('customer_email')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">INE / Folio</label>
                                    <input type="text" name="customer_ine" class="form-control" value="{{ old('customer_ine', $sale->customer_ine) }}" autocomplete="off">
                                </div>
                            @endif
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-bold">Dirección</label>
                                <input type="text" name="customer_address" class="form-control" value="{{ old('customer_address', $sale->customer_address) }}" autocomplete="off">
                            </div>
                            @if (!$sale->isContado())
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Chip Ingresado (SIM)</label>
                                    <input type="text" name="customer_chip" class="form-control" placeholder="Operadora / Número SIM" value="{{ old('customer_chip', $sale->customer_chip) }}" autocomplete="off">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Facebook</label>
                                    <input type="text" name="customer_facebook" class="form-control" placeholder="URL o nombre de perfil" value="{{ old('customer_facebook', $sale->customer_facebook) }}" autocomplete="off">
                                </div>
                            @endif
                        </div>

                        @if (!$sale->isContado())
                            <hr>
                            <h6 class="font-weight-bold text-secondary mb-3"><i class="fa-solid fa-users mr-1"></i>Referencias Personales</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #1 — Nombre</label>
                                    <input type="text" name="ref1_name" class="form-control" value="{{ old('ref1_name', $sale->ref1_name) }}" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #1 — Celular</label>
                                    <input type="text" name="ref1_phone" class="form-control" value="{{ old('ref1_phone', $sale->ref1_phone) }}" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #2 — Nombre</label>
                                    <input type="text" name="ref2_name" class="form-control" value="{{ old('ref2_name', $sale->ref2_name) }}" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #2 — Celular</label>
                                    <input type="text" name="ref2_phone" class="form-control" value="{{ old('ref2_phone', $sale->ref2_phone) }}" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #3 — Nombre</label>
                                    <input type="text" name="ref3_name" class="form-control" value="{{ old('ref3_name', $sale->ref3_name) }}" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Referencia #3 — Celular</label>
                                    <input type="text" name="ref3_phone" class="form-control" value="{{ old('ref3_phone', $sale->ref3_phone) }}" autocomplete="off">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($sale->isContado())
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0 text-white font-weight-bold">Condiciones de Venta al Contado</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Precio Total al Contado ($) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control font-weight-bold text-success font-size-16 @error('price') is-invalid @enderror" value="{{ old('price', $sale->price) }}" required autocomplete="off">
                                    @error('price')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Póliza / Garantía</label>
                                    <input type="text" name="warranty_text" class="form-control" value="{{ old('warranty_text', $sale->warranty_text) }}" placeholder="Ej. 1 Mes de garantía" autocomplete="off">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Método de Pago</label>
                                    <select name="payment_method" class="form-control custom-select">
                                        <option value="Efectivo" {{ old('payment_method', $sale->payment_method) == 'Efectivo' ? 'selected' : '' }}>💵 Efectivo</option>
                                        <option value="Transferencia" {{ old('payment_method', $sale->payment_method) == 'Transferencia' ? 'selected' : '' }}>🏦 Transferencia SPEI</option>
                                        <option value="Tarjeta" {{ old('payment_method', $sale->payment_method) == 'Tarjeta' ? 'selected' : '' }}>💳 Tarjeta</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Vendedor</label>
                                    @if(auth()->user()?->can('financieras.ventas.assign_seller') || auth()->user()?->isSuperAdmin())
                                        <select name="seller_id" class="form-control">
                                            @foreach ($sellers as $s)
                                                <option value="{{ $s->id }}" {{ old('seller_id', $sale->seller_id) == $s->id ? 'selected' : '' }}>
                                                    {{ $s->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control bg-light" value="{{ $sale->seller?->name ?? 'Sin asignar' }}" readonly>
                                        <small class="text-muted"><i class="fa-solid fa-lock mr-1"></i>Solo lectura</small>
                                    @endif
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Fecha de Venta</label>
                                    <input type="datetime-local" name="sale_date" class="form-control" value="{{ old('sale_date', $sale->sale_date ? $sale->sale_date->format('Y-m-d\TH:i') : '') }}" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0 text-white font-weight-bold">Condiciones de Crédito</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Financiera <span class="text-danger">*</span></label>
                                    <select name="financiera_id" class="form-control @error('financiera_id') is-invalid @enderror" required>
                                        <option value="">-- Seleccionar Financiera --</option>
                                        @foreach ($financieras as $f)
                                            <option value="{{ $f->id }}" {{ old('financiera_id', $sale->financiera_id) == $f->id ? 'selected' : '' }}>
                                                {{ $f->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('financiera_id')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Vendedor</label>
                                    @if(auth()->user()?->can('financieras.ventas.assign_seller') || auth()->user()?->isSuperAdmin())
                                        <select name="seller_id" class="form-control">
                                            @foreach ($sellers as $s)
                                                <option value="{{ $s->id }}" {{ old('seller_id', $sale->seller_id) == $s->id ? 'selected' : '' }}>
                                                    {{ $s->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control bg-light" value="{{ $sale->seller?->name ?? 'Sin asignar' }}" readonly>
                                        <small class="text-muted"><i class="fa-solid fa-lock mr-1"></i>Solo lectura (sin permiso para modificar)</small>
                                    @endif
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">TAG / No. Contrato</label>
                                    <input type="text" name="tag_contrato" id="tagContratoInput" class="form-control @error('tag_contrato') is-invalid @enderror" placeholder="Ej. PAY-00123" value="{{ old('tag_contrato', $sale->tag_contrato) }}" autocomplete="off">
                                    @error('tag_contrato')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                    <div id="tagFeedback" class="mt-1"></div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Precio Total ($)</label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $sale->price) }}" autocomplete="off">
                                    @error('price')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Enganche ($)</label>
                                    <input type="number" step="0.01" min="0" name="down_payment" class="form-control @error('down_payment') is-invalid @enderror" value="{{ old('down_payment', $sale->down_payment) }}" autocomplete="off">
                                    @error('down_payment')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Enganche con Descuento ($)</label>
                                    <input type="number" step="0.01" min="0" name="enganche_descuento" class="form-control" value="{{ old('enganche_descuento', $sale->enganche_descuento) }}" placeholder="0.00" autocomplete="off">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Crédito ($)</label>
                                    <input type="number" step="0.01" min="0" name="credit_amount" class="form-control @error('credit_amount') is-invalid @enderror" value="{{ old('credit_amount', $sale->credit_amount) }}" autocomplete="off">
                                    @error('credit_amount')
                                        <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Abono Semanal ($)</label>
                                    <input type="number" step="0.01" min="0" name="abono_semanal" class="form-control" value="{{ old('abono_semanal', $sale->abono_semanal) }}" placeholder="0.00" autocomplete="off">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Plazo (Semanas)</label>
                                    <input type="number" min="1" max="104" name="term_weeks" class="form-control" value="{{ old('term_weeks', $sale->term_weeks) }}" autocomplete="off">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Plazo (Meses)</label>
                                    <input type="number" min="1" max="72" name="term_months" class="form-control" value="{{ old('term_months', $sale->term_months) }}" autocomplete="off">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="font-weight-bold">Fecha de Venta</label>
                                    <input type="datetime-local" name="sale_date" class="form-control" value="{{ old('sale_date', $sale->sale_date ? $sale->sale_date->format('Y-m-d\TH:i') : '') }}" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif           </div>

                <div class="d-flex justify-content-end mb-5">
                    <a href="{{ route('financieras.ventas.show', $sale->id) }}" class="btn btn-light border mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-warning px-4 font-weight-bold">
                        <i class="fa-solid fa-save mr-2"></i>Actualizar Venta
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
    // Desactivar sugerencias / historial de autocompletado en todos los campos
    $('#saleEditForm').attr('autocomplete', 'off').find('input, textarea').attr('autocomplete', 'off');

    // Verificación en vivo de duplicados para TAG / No. Contrato (excluyendo la venta actual)
    $('#tagContratoInput').on('blur change', function() {
        let tag = $(this).val().trim();
        if (!tag) {
            $('#tagFeedback').html('');
            $('#tagContratoInput').removeClass('is-invalid');
            return;
        }

        $.get("{{ route('financieras.ventas.check-tag') }}", { tag: tag, exclude_id: {{ $sale->id }} }, function(res) {
            if (res.exists) {
                $('#tagFeedback').html('<div class="text-danger font-size-12 font-weight-bold mt-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i>El TAG ya está registrado en la venta ' + (res.sale_code || '') + '. No se permiten duplicados.</div>');
                $('#tagContratoInput').addClass('is-invalid');
            } else {
                $('#tagFeedback').html('<div class="text-success font-size-12 mt-1"><i class="fa-solid fa-circle-check mr-1"></i>TAG disponible.</div>');
                $('#tagContratoInput').removeClass('is-invalid');
            }
        });
    });

    // Evitar guardar si hay un TAG duplicado
    $('#saleEditForm').on('submit', function(e) {
        if ($('#tagContratoInput').hasClass('is-invalid')) {
            e.preventDefault();
            alert('El TAG / No. Contrato ingresado ya existe en otra venta. Por favor ingrese un TAG único.');
            $('#tagContratoInput').focus();
            return false;
        }
    });
});
</script>
@endsection
