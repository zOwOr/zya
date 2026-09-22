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

            <form action="{{ route('financieras.ventas.update', $sale->id) }}" method="POST">
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
                                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $sale->customer_name) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Teléfono</label>
                                <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $sale->customer_phone) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Email</label>
                                <input type="email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email', $sale->customer_email) }}">
                                @error('customer_email')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">INE / Folio</label>
                                <input type="text" name="customer_ine" class="form-control" value="{{ old('customer_ine', $sale->customer_ine) }}">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-bold">Dirección</label>
                                <input type="text" name="customer_address" class="form-control" value="{{ old('customer_address', $sale->customer_address) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Chip Ingresado (SIM)</label>
                                <input type="text" name="customer_chip" class="form-control" placeholder="Operadora / Número SIM" value="{{ old('customer_chip', $sale->customer_chip) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Facebook</label>
                                <input type="text" name="customer_facebook" class="form-control" placeholder="URL o nombre de perfil" value="{{ old('customer_facebook', $sale->customer_facebook) }}">
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-secondary mb-3"><i class="fa-solid fa-users mr-1"></i>Referencias Personales</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #1 — Nombre</label>
                                <input type="text" name="ref1_name" class="form-control" value="{{ old('ref1_name', $sale->ref1_name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #1 — Celular</label>
                                <input type="text" name="ref1_phone" class="form-control" value="{{ old('ref1_phone', $sale->ref1_phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #2 — Nombre</label>
                                <input type="text" name="ref2_name" class="form-control" value="{{ old('ref2_name', $sale->ref2_name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #2 — Celular</label>
                                <input type="text" name="ref2_phone" class="form-control" value="{{ old('ref2_phone', $sale->ref2_phone) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #3 — Nombre</label>
                                <input type="text" name="ref3_name" class="form-control" value="{{ old('ref3_name', $sale->ref3_name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Referencia #3 — Celular</label>
                                <input type="text" name="ref3_phone" class="form-control" value="{{ old('ref3_phone', $sale->ref3_phone) }}">
                            </div>
                        </div>
                    </div>
                </div>

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
                                @if(auth()->user()?->isSuperAdmin())
                                    <select name="seller_id" class="form-control">
                                        @foreach ($sellers as $s)
                                            <option value="{{ $s->id }}" {{ old('seller_id', $sale->seller_id) == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ $sale->seller?->name ?? 'Sin asignar' }}" readonly>
                                    <small class="text-muted"><i class="fa-solid fa-lock mr-1"></i>Solo lectura (solo SuperAdmin puede modificar)</small>
                                @endif
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">TAG / No. Contrato</label>
                                <input type="text" name="tag_contrato" class="form-control" placeholder="Ej. PAY-00123" value="{{ old('tag_contrato', $sale->tag_contrato) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Precio Total ($)</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $sale->price) }}">
                                @error('price')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Enganche ($)</label>
                                <input type="number" step="0.01" min="0" name="down_payment" class="form-control @error('down_payment') is-invalid @enderror" value="{{ old('down_payment', $sale->down_payment) }}">
                                @error('down_payment')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Enganche con Descuento ($)</label>
                                <input type="number" step="0.01" min="0" name="enganche_descuento" class="form-control" value="{{ old('enganche_descuento', $sale->enganche_descuento) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Crédito ($)</label>
                                <input type="number" step="0.01" min="0" name="credit_amount" class="form-control @error('credit_amount') is-invalid @enderror" value="{{ old('credit_amount', $sale->credit_amount) }}">
                                @error('credit_amount')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Abono Semanal ($)</label>
                                <input type="number" step="0.01" min="0" name="abono_semanal" class="form-control" value="{{ old('abono_semanal', $sale->abono_semanal) }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Plazo (Semanas)</label>
                                <input type="number" min="1" max="104" name="term_weeks" class="form-control" value="{{ old('term_weeks', $sale->term_weeks) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Plazo (Meses)</label>
                                <input type="number" min="1" max="72" name="term_months" class="form-control" value="{{ old('term_months', $sale->term_months) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Fecha de Venta</label>
                                <input type="datetime-local" name="sale_date" class="form-control" value="{{ old('sale_date', $sale->sale_date ? $sale->sale_date->format('Y-m-d\TH:i') : '') }}">
                            </div>
                        </div>
                    </div>
                </div>

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
