@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="las la-gift text-danger"></i> Inventario y Entrega de Gifts por Proveedor
            </h3>
            <small class="text-muted">
                Control de almacenamiento, entradas por proveedor / financiera y entregas de obsequios a clientes por sucursal
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('gifts.create'))
                <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#modalCreateGift">
                    <i class="las la-plus-circle"></i> Nuevo Gift en Almacén
                </button>
                <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#modalEntryGift">
                    <i class="las la-boxes"></i> Recibir Stock de Proveedor
                </button>
            @endif
            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('gifts.deliver') || auth()->user()->can('gifts.create'))
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalDeliverGift">
                    <i class="las la-hand-holding-heart"></i> Entregar Gift a Cliente
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tarjetas KPI --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="small text-muted text-uppercase font-weight-bold">Catálogo de Gifts</div>
                    <h3 class="font-weight-bold mb-0">{{ $stats['total_items'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success">
                <div class="card-body">
                    <div class="small text-muted text-uppercase font-weight-bold">Unidades en Almacén</div>
                    <h3 class="font-weight-bold text-success mb-0">{{ $stats['total_stock'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info">
                <div class="card-body">
                    <div class="small text-muted text-uppercase font-weight-bold">Recibidos este Mes</div>
                    <h3 class="font-weight-bold text-info mb-0">+{{ $stats['entries_month'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning">
                <div class="card-body">
                    <div class="small text-muted text-uppercase font-weight-bold">Entregados a Clientes (Mes)</div>
                    <h3 class="font-weight-bold text-warning mb-0">{{ $stats['deliveries_month'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('gifts.index') }}" class="row align-items-end">
                <div class="col-md-4">
                    <label class="small font-weight-bold mb-1">Buscar por nombre, código o marca:</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Ej. Audífonos, Termo, PayJoy...">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold mb-1">Filtrar por Proveedor / Financiera:</label>
                    <input type="text" name="supplier_filter" value="{{ request('supplier_filter') }}" class="form-control" placeholder="Nombre del proveedor">
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('gifts.all_branches'))
                    <div class="col-md-3">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-block"><i class="las la-search"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de Inventario de Gifts --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="las la-warehouse text-primary"></i> Existencias Actuales en Almacén de Gifts</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Gift / Promocional</th>
                            <th>Marca</th>
                            <th>Proveedor / Financiera</th>
                            <th>Sucursal</th>
                            <th class="text-center">Existencia</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones Rápidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gifts as $g)
                            <tr>
                                <td><code>{{ $g->code }}</code></td>
                                <td>
                                    <strong>{{ $g->name }}</strong>
                                    @if($g->description)
                                        <div class="small text-muted">{{ $g->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $g->brand ?? '-' }}</td>
                                <td><span class="badge badge-info">{{ $g->supplier_display_name }}</span></td>
                                <td>{{ $g->branch->name ?? 'General' }}</td>
                                <td class="text-center font-weight-bold h5 mb-0 {{ $g->stock_quantity <= $g->min_stock ? 'text-danger' : 'text-success' }}">
                                    {{ $g->stock_quantity }}
                                </td>
                                <td class="text-center">
                                    @if($g->stock_quantity > 0)
                                        <span class="badge badge-success">Disponible</span>
                                    @else
                                        <span class="badge badge-danger">Agotado</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-success mr-1"
                                            onclick="preselectGiftEntry({{ $g->id }}, '{{ addslashes($g->supplier_display_name) }}')"
                                            title="Registrar entrada de proveedor">
                                        <i class="las la-plus"></i> Entrada
                                    </button>
                                    @if($g->stock_quantity > 0)
                                        <button type="button" class="btn btn-xs btn-warning"
                                                onclick="preselectGiftDelivery({{ $g->id }})"
                                                title="Entregar a cliente">
                                            <i class="las la-gift"></i> Entregar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay gifts registrados en esta sucursal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $gifts->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    {{-- Bitácora de Entradas por Proveedor y Entregas a Clientes --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0 font-weight-bold"><i class="las la-history text-secondary"></i> Historial de Almacenamiento (Proveedores) y Entregas (Clientes)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Tipo</th>
                            <th>Gift</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-center">Stock Ant. / Nuevo</th>
                            <th>Proveedor</th>
                            <th>Cliente Receptor</th>
                            <th>Referencia / Módulo</th>
                            <th>Registró</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $mv)
                            <tr>
                                <td>{{ optional($mv->movement_date)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($mv->movement_type === 'entrada_proveedor')
                                        <span class="badge badge-success">Entrada Proveedor</span>
                                    @elseif($mv->movement_type === 'entrega_cliente')
                                        <span class="badge badge-warning text-dark">Entrega a Cliente</span>
                                    @else
                                        <span class="badge badge-secondary">Ajuste</span>
                                    @endif
                                </td>
                                <td class="font-weight-bold">{{ $mv->gift->name ?? 'N/D' }}</td>
                                <td class="text-center font-weight-bold">{{ $mv->quantity }}</td>
                                <td class="text-center small text-muted">{{ $mv->previous_stock }} &rarr; <strong>{{ $mv->new_stock }}</strong></td>
                                <td>{{ $mv->supplier_name ?? '-' }}</td>
                                <td>
                                    @if($mv->customer_name)
                                        <strong>{{ $mv->customer_name }}</strong>
                                        @if($mv->customer_phone)
                                            <small class="text-muted d-block">{{ $mv->customer_phone }}</small>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $mv->reference_module ?? '' }}
                                    @if($mv->reference_code)
                                        <code>({{ $mv->reference_code }})</code>
                                    @endif
                                    @if($mv->notes)
                                        <div class="small text-muted">{{ $mv->notes }}</div>
                                    @endif
                                </td>
                                <td>{{ $mv->user->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">Sin movimientos registrados aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $movements->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Modal: Crear Nuevo Gift --}}
<div class="modal fade" id="modalCreateGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('gifts.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branchId }}">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="las la-plus-circle"></i> Registrar Nuevo Gift / Promocional por Proveedor</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small">Nombre del Gift / Obsequio *</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej. Bocina Bluetooth, Audífonos, Mochila..." required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold small">Código / SKU (opcional)</label>
                        <input type="text" name="code" class="form-control" placeholder="Auto-generado si vacío">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold small">Marca</label>
                        <input type="text" name="brand" class="form-control" placeholder="Ej. Xiaomi, PayJoy...">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Proveedor Catálogo (Opcional)</label>
                        <select name="supplier_id" class="form-control select2-autocomplete" data-placeholder="-- Seleccionar Proveedor --">
                            <option value="">-- Seleccionar --</option>
                            @foreach($suppliers as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Financiera Proveedora (Opcional)</label>
                        <select name="fin_supplier_id" class="form-control select2-autocomplete" data-placeholder="-- Seleccionar Financiera --">
                            <option value="">-- Seleccionar --</option>
                            @foreach($finSuppliers as $fs)
                                <option value="{{ $fs->id }}">{{ $fs->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">O Nombre Libre del Proveedor</label>
                        <input type="text" name="supplier_name" class="form-control" placeholder="Ej. Telcel / KrediYa / Samsung">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Stock Inicial Recibido *</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" value="1" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Alerta Stock Mínimo</label>
                        <input type="number" name="min_stock" class="form-control" min="0" value="2">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Costo Referencial ($)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="font-weight-bold small">Descripción / Notas</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Detalles del promocional o campaña del proveedor"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar en Almacén</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Entrada de Stock por Proveedor --}}
<div class="modal fade" id="modalEntryGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('gifts.entry') }}" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="las la-boxes"></i> Registrar Entrada de Gifts por Proveedor</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="font-weight-bold small">Seleccionar Gift (Autocomplete) *</label>
                    <select name="gift_id" id="entryGiftId" class="form-control select2-autocomplete" data-placeholder="-- Escribe para buscar gift --" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($allGiftsForSelect as $gItem)
                            <option value="{{ $gItem->id }}">{{ $gItem->name }} (Actual: {{ $gItem->stock_quantity }} | Prov: {{ $gItem->supplier_display_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small">Cantidad Recibida *</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small">Folio / Remisión</label>
                        <input type="text" name="reference_code" class="form-control" placeholder="Opcional">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="font-weight-bold small">Proveedor que entrega</label>
                    <input type="text" name="supplier_name" id="entrySupplierName" class="form-control" placeholder="Ej. PayJoy, KrediYa, Distribuidor...">
                </div>
                <div class="mb-2">
                    <label class="font-weight-bold small">Observaciones</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Sumar al Stock</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Entrega de Gift a Cliente --}}
<div class="modal fade" id="modalDeliverGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('gifts.deliver') }}" class="modal-content">
            @csrf
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold"><i class="las la-hand-holding-heart"></i> Registrar Entrega de Gift a Cliente</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="font-weight-bold small">Seleccionar Gift a Entregar (Autocomplete) *</label>
                    <select name="gift_id" id="deliverGiftId" class="form-control select2-autocomplete" data-placeholder="-- Escribe para buscar gift a entregar --" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($allGiftsForSelect->where('stock_quantity', '>', 0) as $gItem)
                            <option value="{{ $gItem->id }}">{{ $gItem->name }} (Disp: {{ $gItem->stock_quantity }} | {{ $gItem->supplier_display_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="font-weight-bold small">Cliente Receptor (Autocomplete) *</label>
                        <select name="customer_name" id="deliverCustomerSelect" class="form-control select2-autocomplete" data-tags="true" data-placeholder="-- Escribe para buscar cliente o escribir nombre --" required>
                            <option value="">-- Escribe para buscar cliente o escribir nombre --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->tit_name }}" data-phone="{{ $c->tit_phone }}">
                                    {{ $c->tit_name }} ({{ $c->tit_phone ?? 'Sin teléfono' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small">Cantidad *</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small">Teléfono del Cliente</label>
                        <input type="text" name="customer_phone" id="deliverCustomerPhone" class="form-control" placeholder="10 dígitos">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small">Motivo / Módulo Relacionado</label>
                        <select name="reference_module" class="form-control">
                            <option value="Financieras">Compra en Financieras</option>
                            <option value="Venta de Productos">Venta de Productos</option>
                            <option value="Préstamos">Apertura / Pago Puntual Préstamo</option>
                            <option value="Tandas">Participante de Tanda</option>
                            <option value="Promoción General">Promoción General</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="font-weight-bold small">TAG / IMEI / Folio de Venta Relacionado (opcional)</label>
                    <input type="text" name="reference_code" class="form-control" placeholder="Ej. TAG-1042 o Folio POS">
                </div>
                <div class="mb-2">
                    <label class="font-weight-bold small">Observaciones</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Detalle opcional de la entrega"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning font-weight-bold">Confirmar Entrega</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#modalDeliverGift').on('shown.bs.modal', function () {
        $('#deliverCustomerSelect').select2({
            tags: true,
            dropdownParent: $('#modalDeliverGift'),
            placeholder: '-- Buscar cliente registrado o escribir nombre --',
            width: '100%'
        });
    });

    $('#deliverCustomerSelect').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.dataset && opt.dataset.phone) {
            document.getElementById('deliverCustomerPhone').value = opt.dataset.phone;
        }
    });
});

function preselectGiftEntry(giftId, supplierName) {
    $('#entryGiftId').val(giftId).trigger('change');
    document.getElementById('entrySupplierName').value = supplierName || '';
    $('#modalEntryGift').modal('show');
}
function preselectGiftDelivery(giftId) {
    $('#deliverGiftId').val(giftId).trigger('change');
    $('#modalDeliverGift').modal('show');
}
</script>
@endsection

