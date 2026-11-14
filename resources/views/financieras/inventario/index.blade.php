@include('financieras.partials.filter-styles')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center fin-section-header">
            <div class="col-md-6 col-12">
                <h5 class="fin-section-title mb-1">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>Inventario de Dispositivos (Control por IMEI)
                </h5>
                <p class="fin-section-subtitle">Control de equipos en almacén y sucursales, traspasos y trazabilidad.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                @can('financieras.inventario.delete')
                    <button type="button" class="btn btn-outline-danger btn-sm mr-1" id="btnBulkDelete" style="display: none;" data-toggle="modal" data-target="#bulkDeleteModal">
                        <i class="fa-solid fa-trash mr-1"></i>Eliminar seleccionados (<span id="bulkDeleteCount">0</span>)
                    </button>
                @endcan
                @can('financieras.inventario.export')
                    <a href="{{ route('financieras.inventario.export.excel', array_merge(['status' => request('status', 'disponible')], request()->only(['search', 'branch_id', 'brand_id', 'model', 'supplier_id', 'status']))) }}" class="btn btn-outline-success btn-sm mr-1">
                        <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                    </a>
                @endcan
                @if (auth()->user()->can('financieras.inventario.create'))
                    <button type="button" class="btn btn-outline-info btn-sm mr-1" data-toggle="modal" data-target="#importExcelModal">
                        <i class="fa-solid fa-file-import mr-1"></i>Importar Excel
                    </button>
                    <a href="{{ route('financieras.inventario.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Nuevo Dispositivo
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-inventory-form" method="GET" action="{{ route('financieras.index') }}" class="fin-filter-shell">
            <input type="hidden" name="tab" value="inventario">

            <!-- Fila 1: Búsqueda Principal, Sucursal, Estado y Device ID / TAG -->
            @php
                $showTagFilter = in_array(request('status'), ['vendido', 'todos']) || request()->filled('tag');
            @endphp
            <div class="row fin-grid-row align-items-end">
                <div class="{{ $showTagFilter ? 'col-lg-4' : 'col-lg-5' }} col-md-12 mb-2 mb-lg-0" id="col-search-main">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-magnifying-glass fin-ico-search"></i>
                        <span>Buscar Dispositivo</span>
                    </label>
                    <div class="input-group fin-filter-input-group">
                        <input type="text" name="search" class="form-control fin-filter-control" placeholder="Buscar por IMEI, color, capacidad o notas..." value="{{ request('search') }}">
                        @if(request('search'))
                            <div class="input-group-append">
                                <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'inventario'])) }}" class="fin-input-clear-btn" title="Limpiar búsqueda">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="{{ $showTagFilter ? 'col-lg-3' : 'col-lg-4' }} col-md-6 col-sm-6 mb-2 mb-lg-0" id="col-branch-main">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-store fin-ico-branch"></i>
                        <span>Sucursal / Ubicación</span>
                    </label>
                    @can('financieras.inventario.all_branches')
                        <select name="branch_id" class="form-control custom-select fin-filter-control">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control fin-filter-control" value="{{ auth()->user()->branch?->name ?? 'Sucursal no asignada' }}" readonly title="Tu sucursal asignada">
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endcan
                </div>

                <div class="{{ $showTagFilter ? 'col-lg-2' : 'col-lg-3' }} col-md-6 col-sm-6 mb-2 mb-lg-0" id="col-status-main">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-tag fin-ico-status"></i>
                        <span>Estado</span>
                    </label>
                    <select name="status" id="statusFilterSelect" class="form-control custom-select fin-filter-control">
                        <option value="disponible" {{ request('status', 'disponible') == 'disponible' ? 'selected' : '' }}>Disponible (Por defecto)</option>
                        <option value="vendido" {{ request('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                        <option value="todos" {{ request('status') == 'todos' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 mb-0" id="container-filter-tag" style="{{ $showTagFilter ? '' : 'display: none;' }}">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-hashtag fin-ico-tag"></i>
                        <span>Device ID / TAG</span>
                    </label>
                    <div class="input-group fin-filter-input-group">
                        <input type="text" name="tag" id="input-filter-tag" class="form-control fin-filter-control" placeholder="Buscar por TAG o Contrato..." value="{{ request('tag') }}">
                        @if(request('tag'))
                            <div class="input-group-append">
                                <a href="{{ route('financieras.index', array_merge(request()->except('tag'), ['tab' => 'inventario'])) }}" class="fin-input-clear-btn" title="Limpiar filtro TAG">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Fila 2: Tarjeta blanca de especificaciones (Marca, Modelo, Proveedor) y Acciones -->
            <div class="fin-filter-subcard fin-filter-subcard-compact">
                <div class="fin-subcard-header mb-2">
                    <div class="fin-subcard-title">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Especificaciones y búsqueda avanzada</span>
                    </div>
                </div>

                <div class="row fin-grid-row align-items-end">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                        <label class="fin-filter-label">
                            <i class="fa-solid fa-copyright fin-ico-brand"></i>
                            <span>Marca</span>
                        </label>
                        <select name="brand_id" class="form-control custom-select fin-filter-control">
                            <option value="">Todas las marcas</option>
                            @foreach ($brands as $br)
                                <option value="{{ $br->id }}" {{ request('brand_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                        <label class="fin-filter-label">
                            <i class="fa-solid fa-mobile-screen fin-ico-model"></i>
                            <span>Modelo</span>
                        </label>
                        <input type="text" name="model" list="modelsDatalist" class="form-control fin-filter-control" placeholder="Todos o escribir modelo..." value="{{ request('model') }}" autocomplete="off">
                        <datalist id="modelsDatalist">
                            @if(isset($models))
                                @foreach ($models as $m)
                                    <option value="{{ $m }}"></option>
                                @endforeach
                            @endif
                        </datalist>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                        <label class="fin-filter-label">
                            <i class="fa-solid fa-truck-field fin-ico-supp"></i>
                            <span>Proveedor</span>
                        </label>
                        <select name="supplier_id" class="form-control custom-select fin-filter-control">
                            <option value="">Todos los proveedores</option>
                            @if(isset($suppliers))
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="fin-actions-wrap w-100 justify-content-lg-end">
                            <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="fin-filter-reset" title="Restablecer todos los filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Limpiar</span>
                            </a>
                            <button type="submit" class="fin-filter-submit flex-grow-1 flex-lg-grow-0">
                                <i class="fa-solid fa-filter"></i>
                                <span>Filtrar</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Filtros Activos (Pills) -->
                @php
                    $canAllBranches = auth()->user()->can('financieras.inventario.all_branches');
                    $activeFiltersCount = collect(['search', $canAllBranches ? 'branch_id' : null, 'brand_id', 'model', 'supplier_id', 'tag'])
                        ->filter(fn($key) => !empty($key) && request()->filled($key))
                        ->count();
                    if (request('status') && request('status') !== 'disponible') {
                        $activeFiltersCount++;
                    }
                @endphp
                @if($activeFiltersCount > 0)
                    <div class="fin-active-filters-bar">
                        <span class="font-size-12 text-muted mr-1 font-weight-bold">
                            <i class="fa-solid fa-sliders mr-1 text-primary"></i>Filtros aplicados ({{ $activeFiltersCount }}):
                        </span>
                        @if(request('search'))
                            <span class="fin-active-pill">
                                Búsqueda: <strong>{{ request('search') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if($canAllBranches && request('branch_id'))
                            <span class="fin-active-pill">
                                Sucursal: <strong>{{ $branches->firstWhere('id', request('branch_id'))?->name ?? request('branch_id') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('branch_id'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if(request('brand_id'))
                            <span class="fin-active-pill">
                                Marca: <strong>{{ $brands->firstWhere('id', request('brand_id'))?->name ?? request('brand_id') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('brand_id'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if(request('model'))
                            <span class="fin-active-pill">
                                Modelo: <strong>{{ request('model') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('model'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if(request('supplier_id'))
                            <span class="fin-active-pill">
                                Proveedor: <strong>{{ (isset($suppliers) ? $suppliers->firstWhere('id', request('supplier_id'))?->name : null) ?? request('supplier_id') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('supplier_id'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if(request('tag'))
                            <span class="fin-active-pill">
                                Device ID / TAG: <strong>{{ request('tag') }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('tag'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        @if(request('status') && request('status') !== 'disponible')
                            <span class="fin-active-pill">
                                Estado: <strong>{{ ucfirst(str_replace('_', ' ', request('status'))) }}</strong>
                                <a href="{{ route('financieras.index', array_merge(request()->except('status'), ['tab' => 'inventario'])) }}">&times;</a>
                            </span>
                        @endif
                        <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-link btn-xs text-danger font-weight-bold ml-auto p-0">
                            <i class="fa-solid fa-trash-can mr-1"></i>Quitar todos los filtros
                        </a>
                    </div>
                @endif
            </div>
        </form>

        <!-- Table Container -->
        <div id="inventory-table-container">
            @include('financieras.inventario.table', ['devices' => $devices ?? App\Models\FinDevice::filter(request()->only(['search', 'branch_id', 'status', 'brand_id', 'supplier_id', 'model', 'tag']))->with(['brand', 'branch', 'supplier', 'activeSale', 'latestSale'])->latest()->paginate(20)->appends(array_merge(request()->only(['search', 'branch_id', 'status', 'brand_id', 'supplier_id', 'model', 'tag']), ['tab' => 'inventario']))])
        </div>
    </div>
</div>

<!-- Modal Transferencia de Dispositivo -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="transferForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-right-left mr-2"></i>Traspaso de Dispositivo
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Mover equipo con IMEI <strong id="transferImei"></strong></p>
                    <div class="form-group mb-3">
                        <label class="font-size-12 text-muted">Sucursal Actual</label>
                        <input type="text" id="currentBranchName" class="form-control" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Sucursal de Destino <span class="text-danger">*</span></label>
                        <select name="to_branch_id" id="toBranchSelect" class="form-control" required>
                            <option value="">Seleccione sucursal de destino...</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Motivo o Notas del Traspaso</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ej. Solicitud de stock para venta..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check mr-1"></i>Confirmar Traspaso
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.inventario.import.excel') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-file-excel mr-2"></i>Carga Masiva de Equipos (Excel)
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
                                    <i class="fa-solid fa-file-excel mr-1"></i>Plantilla Oficial de Importación
                                </h6>
                                <p class="text-muted font-size-12 mb-0">Descarga el archivo base en blanco con las columnas exactas requeridas para la carga masiva.</p>
                            </div>
                            <a href="{{ route('financieras.inventario.template.excel') }}" class="btn btn-success btn-sm font-weight-bold text-nowrap ml-3 shadow-xs">
                                <i class="fa-solid fa-download mr-1"></i>Descargar (.xlsx)
                            </a>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Sucursal por defecto <small class="text-muted">(si el archivo no tiene columna Ubicación)</small></label>
                        @can('financieras.inventario.all_branches')
                            <select name="default_branch_id" class="form-control">
                                <option value="">Seleccione sucursal de respaldo...</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->branch?->name ?? 'Sucursal no asignada' }}" readonly>
                            <input type="hidden" name="default_branch_id" value="{{ auth()->user()->branch_id }}">
                        @endcan
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Proveedor por defecto <small class="text-muted">(si el archivo no tiene columna Proveedor)</small></label>
                        <select name="default_supplier_id" class="form-control">
                            <option value="">Seleccione proveedor de respaldo (opcional)...</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Archivo Excel / CSV <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
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

@can('financieras.inventario.delete')
<!-- Modal Eliminación Masiva -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="bulkDeleteForm" method="POST" action="{{ route('financieras.inventario.bulk-delete') }}">
            @csrf
            <div class="modal-content border-danger shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i>Eliminación Masiva de Dispositivos
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Estás seguro de que deseas eliminar los <strong id="bulkModalCount" class="text-danger">0</strong> dispositivos seleccionados?</p>
                    <div class="alert alert-warning font-size-12 mb-3">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i>
                        Esta acción es irreversible y eliminará el registro de inventario. Los equipos con ventas activas vinculadas serán omitidos automáticamente por seguridad.
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-size-12 font-weight-bold text-muted mb-1">IMEIs a eliminar:</label>
                        <div id="bulkImeisList" class="bg-light p-2 border rounded font-family-monospace font-size-12" style="max-height: 120px; overflow-y: auto; word-break: break-all;">
                        </div>
                    </div>
                    <div id="bulkDeleteHiddenInputs"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger font-weight-bold" id="btnConfirmBulkDelete">
                        <i class="fa-solid fa-trash mr-1"></i>Confirmar Eliminación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateBulkDeleteState() {
        var checked = $('.device-checkbox:checked');
        var count = checked.length;
        $('#bulkDeleteCount').text(count);
        if (count > 0) {
            $('#btnBulkDelete').fadeIn(150);
        } else {
            $('#btnBulkDelete').fadeOut(150);
        }

        var totalCheckboxes = $('.device-checkbox').length;
        if (totalCheckboxes > 0 && count === totalCheckboxes) {
            $('#selectAllDevices').prop('checked', true).prop('indeterminate', false);
        } else if (count > 0) {
            $('#selectAllDevices').prop('checked', false).prop('indeterminate', true);
        } else {
            $('#selectAllDevices').prop('checked', false).prop('indeterminate', false);
        }
    }

    // Toggle seleccionar todos
    $(document).on('change', '#selectAllDevices', function() {
        var isChecked = $(this).is(':checked');
        $('.device-checkbox').prop('checked', isChecked);
        updateBulkDeleteState();
    });

    // Checkbox individual
    $(document).on('change', '.device-checkbox', function() {
        updateBulkDeleteState();
    });

    // Cargar datos en modal antes de mostrar
    $('#bulkDeleteModal').on('show.bs.modal', function() {
        var checked = $('.device-checkbox:checked');
        var container = $('#bulkDeleteHiddenInputs');
        var listContainer = $('#bulkImeisList');
        container.empty();
        listContainer.empty();

        $('#bulkModalCount').text(checked.length);

        var imeis = [];
        checked.each(function() {
            var val = $(this).val();
            var imei = $(this).data('imei') || val;
            container.append('<input type="hidden" name="device_ids[]" value="' + val + '">');
            imeis.push(imei);
        });

        listContainer.text(imeis.join(', '));
    });
});
</script>
@endcan

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar dinámicamente el filtro Device ID / TAG al cambiar el estado
    $('#statusFilterSelect').on('change', function() {
        var statusVal = $(this).val();
        if (statusVal === 'vendido' || statusVal === 'todos') {
            $('#col-search-main').removeClass('col-lg-5').addClass('col-lg-4');
            $('#col-branch-main').removeClass('col-lg-4').addClass('col-lg-3');
            $('#col-status-main').removeClass('col-lg-3').addClass('col-lg-2');
            $('#container-filter-tag').fadeIn(200);
            $('#input-filter-tag').focus();
        } else {
            $('#container-filter-tag').hide();
            $('#input-filter-tag').val('');
            $('#col-search-main').removeClass('col-lg-4').addClass('col-lg-5');
            $('#col-branch-main').removeClass('col-lg-3').addClass('col-lg-4');
            $('#col-status-main').removeClass('col-lg-2').addClass('col-lg-3');
        }
    });
});
</script>
