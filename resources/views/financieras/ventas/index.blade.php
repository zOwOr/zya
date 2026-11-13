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
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('financieras.ventas.export_pdf'))
                    <a href="{{ route('financieras.ventas.export.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm mr-2">
                        <i class="fa-solid fa-file-pdf mr-1"></i>Exportar PDF
                    </a>
                @endif
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('financieras.ventas.export_excel'))
                    <a href="{{ route('financieras.ventas.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm mr-2">
                        <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                    </a>
                @endif
                @if (auth()->user()->can('financieras.ventas.create'))
                    <a href="{{ route('financieras.ventas.create-contado') }}" class="btn btn-success btn-sm mr-2 shadow-xs">
                        <i class="fa-solid fa-money-bill-wave mr-1"></i>Venta al Contado
                    </a>
                    <a href="{{ route('financieras.ventas.create') }}" class="btn btn-primary btn-sm shadow-xs">
                        <i class="fa-solid fa-credit-card mr-1"></i>Venta a Crédito
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
        @if(!empty($salesDateFilter['was_restricted']))
            <div class="alert alert-warning py-2 px-3 small mb-2">
                <i class="las la-exclamation-triangle mr-1"></i>
                Tu rol permite consultar o exportar ventas financieras de hasta <strong>2 días de anterioridad</strong>
                (desde el <strong>{{ \Carbon\Carbon::parse($salesDateFilter['min_allowed_date'])->format('d/m/Y') }}</strong>).
            </div>
        @endif
        <form id="filter-sales-form" method="GET" action="{{ route('financieras.index') }}" class="p-3 mb-3 rounded border bg-light shadow-xs">
            <input type="hidden" name="tab" value="ventas">

            <!-- Fila 1: Búsqueda, Tipo Venta, Financiera, Sucursal, Estado -->
            <div class="row align-items-end mb-2">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-magnifying-glass mr-1 text-primary"></i>Buscar (Folio, TAG, Cliente, Tel, IMEI)
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por cliente, folio, TAG, teléfono o IMEI..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-2 col-md-6 mb-2">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-layer-group mr-1 text-warning"></i>Tipo Venta
                    </label>
                    <select name="sale_type" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todos</option>
                        <option value="credito" {{ request('sale_type') == 'credito' ? 'selected' : '' }}>A Crédito</option>
                        <option value="contado" {{ request('sale_type') == 'contado' ? 'selected' : '' }}>De Contado</option>
                    </select>
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
                <div class="col-lg-2 col-md-6 mb-2">
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
                            @php
                                $finSalesDf = $salesDateFilter ?? ['filter_type' => 'rango', 'can_all_dates' => false, 'min_allowed_date' => \Carbon\Carbon::today()->subDays(2)->toDateString(), 'date' => date('Y-m-d'), 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'was_restricted' => false];
                                $finSalesCanAll = $finSalesDf['can_all_dates'] ?? false;
                                $finSalesMinDate = $finSalesDf['min_allowed_date'] ?? \Carbon\Carbon::today()->subDays(2)->toDateString();
                                $finSalesType = $finSalesDf['filter_type'] ?? 'rango';
                            @endphp
                            @if($finSalesCanAll)
                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('current_month')">Mes actual</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('prev_month')">Mes anterior</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-0" onclick="setDateRange('all')">Todo</button>
                            @else
                                <span class="badge badge-light border text-muted"><i class="las la-lock"></i> Máx. 2 días atrás ({{ \Carbon\Carbon::parse($finSalesMinDate)->format('d/m/Y') }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="row align-items-end no-gutters" style="gap: 6px;">
                        <div style="min-width: 145px;">
                            <select name="filter_type" id="filter_sale_type" class="form-control form-control-sm font-weight-bold">
                                <option value="dia" {{ $finSalesType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                                <option value="rango" {{ $finSalesType === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                                @if($finSalesCanAll)
                                    <option value="todos" {{ $finSalesType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                                @endif
                            </select>
                        </div>
                        <div class="flex-grow-1" id="finSaleDiaBox" style="{{ $finSalesType === 'dia' ? '' : 'display:none;' }}">
                            <input type="date" name="date" id="filter_sale_date" class="form-control form-control-sm"
                                value="{{ $finSalesDf['date'] ?? date('Y-m-d') }}"
                                @if(!$finSalesCanAll) min="{{ $finSalesMinDate }}" @endif
                                {{ $finSalesType === 'dia' ? '' : 'disabled' }}>
                        </div>
                        <div class="input-group input-group-sm flex-grow-1" id="finSaleRangoBox" style="{{ $finSalesType === 'rango' ? '' : 'display:none;' }}">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-size-11 bg-white">Desde</span>
                            </div>
                            <input type="date" name="start_date" id="filter_sale_start_date" class="form-control form-control-sm"
                                value="{{ $finSalesDf['start_date'] ?? now()->startOfMonth()->format('Y-m-d') }}"
                                @if(!$finSalesCanAll) min="{{ $finSalesMinDate }}" @endif
                                {{ $finSalesType === 'rango' ? '' : 'disabled' }}>
                            <div class="input-group-prepend input-group-append">
                                <span class="input-group-text font-size-11 bg-white">Hasta</span>
                            </div>
                            <input type="date" name="end_date" id="filter_sale_end_date" class="form-control form-control-sm"
                                value="{{ $finSalesDf['end_date'] ?? now()->endOfMonth()->format('Y-m-d') }}"
                                @if(!$finSalesCanAll) min="{{ $finSalesMinDate }}" @endif
                                {{ $finSalesType === 'rango' ? '' : 'disabled' }}>
                        </div>
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
    function syncFinSaleDateMode() {
        var sel = document.getElementById('filter_sale_type');
        if (!sel) return;
        var val = sel.value;
        var diaBox = document.getElementById('finSaleDiaBox');
        var diaInput = document.getElementById('filter_sale_date');
        var rangoBox = document.getElementById('finSaleRangoBox');
        var startInput = document.getElementById('filter_sale_start_date');
        var endInput = document.getElementById('filter_sale_end_date');

        if (diaBox) diaBox.style.display = (val === 'dia') ? '' : 'none';
        if (diaInput) diaInput.disabled = (val !== 'dia');

        if (rangoBox) rangoBox.style.display = (val === 'rango') ? '' : 'none';
        if (startInput) startInput.disabled = (val !== 'rango');
        if (endInput) endInput.disabled = (val !== 'rango');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('filter_sale_type');
        if (sel) {
            sel.addEventListener('change', syncFinSaleDateMode);
            syncFinSaleDateMode();
        }
    });

    function setDateRange(type) {
        var sel = document.getElementById('filter_sale_type');
        var startInput = document.getElementById('filter_sale_start_date');
        var endInput = document.getElementById('filter_sale_end_date');
        if (!startInput || !endInput) return;

        var now = new Date();

        if (type === 'all') {
            if (sel && sel.querySelector('option[value="todos"]')) {
                sel.value = 'todos';
                syncFinSaleDateMode();
            }
            return;
        }

        if (sel) {
            sel.value = 'rango';
            syncFinSaleDateMode();
        }

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
