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
                <a href="{{ route('financieras.inventario.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-1">
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
        <form id="filter-inventory-form" method="GET" action="{{ route('financieras.index') }}" class="bg-light p-3 rounded mb-3">
            <input type="hidden" name="tab" value="inventario">
            <div class="row align-items-end">
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Buscar (IMEI / Color)</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Sucursal</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Marca</label>
                    <select name="brand_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($brands as $br)
                            <option value="{{ $br->id }}" {{ request('brand_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Modelo</label>
                    <input type="text" name="model" list="modelsDatalist" class="form-control form-control-sm" placeholder="Todos o escribir..." value="{{ request('model') }}" autocomplete="off">
                    <datalist id="modelsDatalist">
                        @if(isset($models))
                            @foreach ($models as $m)
                                <option value="{{ $m }}"></option>
                            @endforeach
                        @endif
                    </datalist>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Proveedor</label>
                    <select name="supplier_id" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @if(isset($suppliers))
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-xl-1 col-lg-3 col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Estado</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="disponible" {{ request('status') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                        <option value="vendido" {{ request('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                        <option value="en_garantia" {{ request('status') == 'en_garantia' ? 'selected' : '' }}>En Garantía</option>
                        <option value="robado" {{ request('status') == 'robado' ? 'selected' : '' }}>Robado</option>
                    </select>
                </div>
                <div class="col-xl-1 col-lg-3 col-md-4 col-sm-6 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm mr-1 flex-grow-1" title="Filtrar">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}" class="btn btn-light btn-sm border" title="Restablecer filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Container -->
        <div id="inventory-table-container">
            @include('financieras.inventario.table', ['devices' => $devices ?? App\Models\FinDevice::with(['brand', 'branch', 'supplier', 'latestSale'])->latest()->paginate(20)])
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
                    <div class="alert alert-light border font-size-12 mb-3">
                        <p class="font-weight-bold mb-1 text-primary">Encabezados soportados en el archivo:</p>
                        <p class="mb-1 font-family-monospace text-dark">
                            <code>FECHA DE LLEGADA | PROVEEDOR | UBICACIÓN | MARCA | MODELO | IMEI | COLOR | CAPACIDAD</code>
                        </p>
                        <ul class="pl-3 mb-0 text-muted">
                            <li><strong>MARCA</strong>: debe existir previamente en el Catálogo de Marcas.</li>
                            <li><strong>UBICACIÓN</strong>: debe existir en el Catálogo de Sucursales.</li>
                            <li><strong>PROVEEDOR</strong>: debe existir en el Catálogo de Proveedores.</li>
                            <li><strong>FECHA DE LLEGADA</strong>: se registrará como fecha de entrada del equipo.</li>
                        </ul>
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
