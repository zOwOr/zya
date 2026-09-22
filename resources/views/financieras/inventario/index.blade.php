<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 col-12">
                <h5 class="font-weight-bold text-primary mb-1">
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>Inventario de Dispositivos (Control por IMEI)
                </h5>
                <p class="text-muted font-size-13 mb-0">Control de equipos en almacén y sucursales, traspasos y trazabilidad.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                @can('financieras.inventario.delete')
                    <button type="button" class="btn btn-outline-danger btn-sm mr-1" id="btnBulkDelete" style="display: none;" data-toggle="modal" data-target="#bulkDeleteModal">
                        <i class="fa-solid fa-trash mr-1"></i>Eliminar seleccionados (<span id="bulkDeleteCount">0</span>)
                    </button>
                @endcan
                <a href="{{ route('financieras.inventario.export.excel', array_merge(['status' => request('status', 'disponible')], request()->only(['search', 'branch_id', 'brand_id', 'model', 'supplier_id', 'status']))) }}" class="btn btn-outline-success btn-sm mr-1">
                    <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                </a>
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
        <form id="filter-inventory-form" method="GET" action="{{ route('financieras.index') }}" class="p-3 mb-4 rounded border bg-light shadow-xs">
            <input type="hidden" name="tab" value="inventario">
            
            <!-- Fila 1: Búsqueda Principal, Sucursal, Estado y Device ID / TAG -->
            @php
                $showTagFilter = in_array(request('status'), ['vendido', 'todos']) || request()->filled('tag');
            @endphp
            <div class="row align-items-end mb-3">
                <div class="{{ $showTagFilter ? 'col-lg-4' : 'col-lg-5' }} col-md-12 mb-2 mb-lg-0" id="col-search-main">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-magnifying-glass mr-1 text-primary"></i>Buscar Dispositivo
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por IMEI, color, capacidad o notas..." value="{{ request('search') }}">
                        @if(request('search'))
                            <div class="input-group-append">
                                <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'inventario'])) }}" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="{{ $showTagFilter ? 'col-lg-3' : 'col-lg-4' }} col-md-6 col-sm-6 mb-2 mb-lg-0" id="col-branch-main">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-store mr-1 text-info"></i>Sucursal / Ubicación
                    </label>
                    <select name="branch_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="{{ $showTagFilter ? 'col-lg-2' : 'col-lg-3' }} col-md-6 col-sm-6 mb-2 mb-lg-0" id="col-status-main">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-tags mr-1 text-success"></i>Estado
                    </label>
                    <select name="status" id="statusFilterSelect" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="disponible" {{ request('status', 'disponible') == 'disponible' ? 'selected' : '' }}>Disponible (Por defecto)</option>
                        <option value="vendido" {{ request('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                        <option value="todos" {{ request('status') == 'todos' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0" id="container-filter-tag" style="{{ $showTagFilter ? '' : 'display: none;' }}">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-tag mr-1 text-primary"></i>Device ID / TAG
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="tag" id="input-filter-tag" class="form-control" placeholder="Buscar por TAG o Contrato..." value="{{ request('tag') }}">
                        @if(request('tag'))
                            <div class="input-group-append">
                                <a href="{{ route('financieras.index', array_merge(request()->except('tag'), ['tab' => 'inventario'])) }}" class="btn btn-outline-secondary" title="Limpiar filtro TAG">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Fila 2: Marca, Modelo, Proveedor y Acciones -->
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-copyright mr-1 text-secondary"></i>Marca
                    </label>
                    <select name="brand_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todas las marcas</option>
                        @foreach ($brands as $br)
                            <option value="{{ $br->id }}" {{ request('brand_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-mobile-screen mr-1 text-warning"></i>Modelo
                    </label>
                    <input type="text" name="model" list="modelsDatalist" class="form-control form-control-sm" placeholder="Todos o escribir modelo..." value="{{ request('model') }}" autocomplete="off">
                    <datalist id="modelsDatalist">
                        @if(isset($models))
                            @foreach ($models as $m)
                                <option value="{{ $m }}"></option>
                            @endforeach
                        @endif
                    </datalist>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 mb-2 mb-lg-0">
                    <label class="font-size-11 font-weight-bold text-uppercase text-muted mb-1">
                        <i class="fa-solid fa-truck-field mr-1 text-danger"></i>Proveedor
                    </label>
                    <select name="supplier_id" class="form-control form-control-sm custom-select custom-select-sm">
                        <option value="">Todos los proveedores</option>
                        @if(isset($suppliers))
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold flex-grow-1 mr-2 shadow-xs">
                        <i class="fa-solid fa-filter mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-outline-secondary btn-sm px-3" title="Restablecer todos los filtros">
                        <i class="fa-solid fa-rotate-left mr-1"></i>Limpiar
                    </a>
                </div>
            </div>

            <!-- Resumen de Filtros Activos (Pills) -->
            @php
                $activeFiltersCount = collect(['search', 'branch_id', 'brand_id', 'model', 'supplier_id', 'tag'])
                    ->filter(fn($key) => request()->filled($key))
                    ->count();
                if (request('status') && request('status') !== 'disponible') {
                    $activeFiltersCount++;
                }
            @endphp
            @if($activeFiltersCount > 0)
                <div class="d-flex flex-wrap align-items-center mt-3 pt-2 border-top">
                    <span class="font-size-12 text-muted mr-2 font-weight-bold">
                        <i class="fa-solid fa-sliders mr-1 text-primary"></i>Filtros aplicados ({{ $activeFiltersCount }}):
                    </span>
                    @if(request('search'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Búsqueda: <strong>{{ request('search') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('branch_id'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Sucursal: <strong>{{ $branches->firstWhere('id', request('branch_id'))?->name ?? request('branch_id') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('branch_id'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('brand_id'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Marca: <strong>{{ $brands->firstWhere('id', request('brand_id'))?->name ?? request('brand_id') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('brand_id'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('model'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Modelo: <strong>{{ request('model') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('model'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('supplier_id'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Proveedor: <strong>{{ (isset($suppliers) ? $suppliers->firstWhere('id', request('supplier_id'))?->name : null) ?? request('supplier_id') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('supplier_id'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('tag'))
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Device ID / TAG: <strong>{{ request('tag') }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('tag'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    @if(request('status') && request('status') !== 'disponible')
                        <span class="badge badge-white border text-dark mr-1 py-1 px-2 font-size-12 mb-1">
                            Estado: <strong>{{ ucfirst(str_replace('_', ' ', request('status'))) }}</strong>
                            <a href="{{ route('financieras.index', array_merge(request()->except('status'), ['tab' => 'inventario'])) }}" class="text-danger ml-1 font-weight-bold">&times;</a>
                        </span>
                    @endif
                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-link btn-xs text-danger font-weight-bold ml-auto mb-1">
                        <i class="fa-solid fa-trash-can mr-1"></i>Quitar todos los filtros
                    </a>
                </div>
            @endif
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
                        <select name="default_branch_id" class="form-control">
                            <option value="">Seleccione sucursal de respaldo...</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
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
