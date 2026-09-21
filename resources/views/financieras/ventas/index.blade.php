<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 col-12">
                <h5 class="font-weight-bold text-primary mb-1">
                    <i class="fa-solid fa-file-invoice-dollar mr-2"></i>Ventas y Créditos de Financieras
                </h5>
                <p class="text-muted font-size-13 mb-0">Gestión de operaciones, financiamientos y seguimiento de clientes.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                <a href="{{ route('financieras.ventas.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                    <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                </a>
                @if (auth()->user()->can('financieras.ventas.create'))
                    <a href="{{ route('financieras.ventas.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Nueva Venta
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-sales-form" method="GET" action="{{ route('financieras.index') }}" class="bg-light p-3 rounded mb-3">
            <input type="hidden" name="tab" value="ventas">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Buscar (Folio, Cliente, Tel, IMEI)</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Financiera</label>
                    <select name="financiera_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($financieras as $f)
                            <option value="{{ $f->id }}" {{ request('financiera_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Sucursal</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Estado</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="activa" {{ request('status') == 'activa' ? 'selected' : '' }}>Activa</option>
                        <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-12 d-flex align-items-end mb-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-2 flex-grow-1">
                        <i class="fa-solid fa-filter mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-light btn-sm border">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Partial Container -->
        <div id="sales-table-container">
            @include('financieras.ventas.table', ['sales' => $sales ?? App\Models\FinSale::with(['device.brand', 'financiera', 'branch', 'seller'])->latest('sale_date')->paginate(15)->appends(['tab' => 'ventas'])])
        </div>
    </div>
</div>

<!-- Modal Cancelar Venta -->
<div class="modal fade" id="cancelSaleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="cancelSaleForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i>Cancelar Venta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Está seguro de que desea cancelar la venta <strong id="cancelSaleCode"></strong>?</p>
                    <div class="alert alert-warning py-2 font-size-13 mb-3">
                        <i class="fa-solid fa-circle-info mr-1"></i> El dispositivo con IMEI <strong id="cancelSaleImei"></strong> volverá automáticamente a estar <strong>disponible</strong> en el inventario.
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Motivo de Cancelación <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Ej. Cliente desistió del crédito / error de captura" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban mr-1"></i>Confirmar Cancelación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
