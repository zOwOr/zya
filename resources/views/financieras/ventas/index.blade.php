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
                @can('financieras.ventas.export')
                    <a href="{{ route('financieras.ventas.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                        <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                    </a>
                @endcan
                @if (auth()->user()->can('financieras.ventas.create'))
                    <a href="{{ route('financieras.ventas.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Nueva Venta
                    </a>
                @endif
            </div>
        </div>

        @if (session('import_errors') && count(session('import_errors')) > 0)
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="fa-solid fa-triangle-exclamation fa-lg mr-2"></i>
                    <strong>Se encontraron incidencias en el archivo. No se importó ningún registro:</strong>
                </div>
                <ul class="mb-0 pl-3 font-size-13" style="max-height: 200px; overflow-y: auto;">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Filters Form -->
        <form id="filter-sales-form" method="GET" action="{{ route('financieras.index') }}" class="p-3 mb-3 rounded border bg-light shadow-xs">
            <input type="hidden" name="tab" value="ventas">

            <!-- Fila 1: Búsqueda, Financiera, Sucursal, Estado -->
            <div class="row align-items-end mb-2">
                <div class="col-lg-4 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-magnifying-glass mr-1 text-primary"></i>Buscar (Folio, Cliente, Tel, IMEI)
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por cliente, folio, teléfono o IMEI..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-building-columns mr-1 text-info"></i>Financiera
                    </label>
                    <select name="financiera_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todas las financieras</option>
                        @foreach ($financieras as $f)
                            <option value="{{ $f->id }}" {{ request('financiera_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-store mr-1 text-success"></i>Sucursal
                    </label>
                    @can('financieras.inventario.all_branches')
                        <select name="branch_id" class="form-control form-control-sm custom-select custom-select-sm">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control form-control-sm bg-light" value="{{ auth()->user()->branch?->name ?? 'Sucursal asignada' }}" readonly title="Tu sucursal asignada">
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endcan
                </div>
                <div class="col-lg-2 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-tag mr-1 text-secondary"></i>Estado
                    </label>
                    <select name="status" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todos</option>
                        <option value="activa" {{ request('status') == 'activa' ? 'selected' : '' }}>Activa</option>
                        <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
            </div>

            <!-- Fila 2: Vendedor, Intervalo de Fechas, Botones -->
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-4 mb-2 mb-lg-0">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-user-tie mr-1 text-primary"></i>Vendedor
                    </label>
                    <select name="seller_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todos los vendedores</option>
                        @if(isset($sellers))
                            @foreach ($sellers as $s)
                                <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-lg-6 col-md-8 mb-2 mb-lg-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-0">
                            <i class="fa-solid fa-calendar-days mr-1 text-primary"></i>Intervalo de Fechas (Venta)
                        </label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('current_month')">Mes actual</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('prev_month')">Mes anterior</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('all')">Todo</button>
                        </div>
                    </div>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text font-size-11 bg-white">Desde</span>
                        </div>
                        <input type="date" name="start_date" id="filter_sale_start_date" class="form-control form-control-sm"
                            value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text font-size-11 bg-white">Hasta</span>
                        </div>
                        <input type="date" name="end_date" id="filter_sale_end_date" class="form-control form-control-sm"
                            value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-12 d-flex align-items-end mb-2 mb-lg-0">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold flex-grow-1 mr-2 shadow-xs">
                        <i class="fa-solid fa-filter mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}" class="btn btn-outline-secondary btn-sm px-3" title="Restablecer al mes en curso">
                        <i class="fa-solid fa-rotate-left mr-1"></i>Limpiar
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

<!-- Modal Importar Histórico de Ventas -->
<div class="modal fade" id="importSalesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.ventas.import.excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-file-excel mr-2"></i>Importación de Histórico de Ventas
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Descarga de Plantilla Oficial -->
                    <div class="card border border-light shadow-xs mb-3" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold text-success mb-1">
                                    <i class="fa-solid fa-file-excel mr-1"></i>Plantilla Oficial de Ventas
                                </h6>
                                <p class="text-muted font-size-12 mb-0">Descarga el formato de 21 columnas con tipos de dato optimizados y fila de ejemplo.</p>
                            </div>
                            <a href="{{ route('financieras.ventas.template.excel') }}" class="btn btn-success btn-sm font-weight-bold text-nowrap ml-3 shadow-xs">
                                <i class="fa-solid fa-download mr-1"></i>Descargar (.xls)
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-light border font-size-12 mb-3">
                        <div class="font-weight-bold text-dark mb-1"><i class="fa-solid fa-circle-info text-info mr-1"></i>Criterios de validación:</div>
                        <ul class="mb-0 pl-3 text-muted">
                            <li>El equipo se creará o actualizará en la sucursal indicada con estado <strong>Vendido</strong>.</li>
                            <li><strong>Fecha de Venta:</strong> Formato obligatorio DD/MM/AAAA con año real congruente (2000-2099).</li>
                            <li><strong>Financiera y Marca:</strong> Deben coincidir con los registros existentes del catálogo.</li>
                            <li><strong>Vendedor:</strong> Si coincide con un usuario del sistema se vincula automáticamente; de lo contrario se guarda su nombre como texto libre.</li>
                        </ul>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Sucursal de Destino <span class="text-danger">*</span></label>
                        @can('financieras.inventario.all_branches')
                            <select name="branch_id" class="form-control" required>
                                <option value="">Seleccione sucursal de destino...</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->branch?->name ?? 'Sucursal asignada' }}" readonly>
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        @endcan
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Archivo Excel / CSV <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        <small class="form-text text-muted">Formatos aceptados: .xlsx, .xls, .csv (Máx. 20 MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info font-weight-bold">
                        <i class="fa-solid fa-upload mr-1"></i>Subir e Importar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function setDateRange(type) {
        var startInput = document.getElementById('filter_sale_start_date');
        var endInput = document.getElementById('filter_sale_end_date');
        if (!startInput || !endInput) return;

        var now = new Date();

        if (type === 'current_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            startInput.value = formatLocalDate(firstDay);
            endInput.value = formatLocalDate(lastDay);
        } else if (type === 'prev_month') {
            var firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            var lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
            startInput.value = formatLocalDate(firstDay);
            endInput.value = formatLocalDate(lastDay);
        } else if (type === 'all') {
            startInput.value = '';
            endInput.value = '';
        }
    }

    function formatLocalDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }
</script>
