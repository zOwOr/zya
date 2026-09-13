<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 col-12">
                <h5 class="font-weight-bold text-danger mb-1">
                    <i class="fa-solid fa-shield-halved mr-2"></i>Reporte y Seguimiento de Robos
                </h5>
                <p class="text-muted font-size-13 mb-0">Control de denuncias por robo o extravío de dispositivos y seguimiento legal.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                <a href="{{ route('financieras.robos.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                    <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                </a>
                @if (auth()->user()->can('financieras.robos.create'))
                    <a href="{{ route('financieras.robos.create') }}" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Reportar Robo
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-thefts-form" method="GET" action="{{ route('financieras.index') }}" class="bg-light p-3 rounded mb-3">
            <input type="hidden" name="tab" value="robos">
            <div class="row">
                <div class="col-md-4 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Buscar (Folio, IMEI, Acta, Cliente)</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Sucursal</label>
                    <select name="branch_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Estado</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="reportado" {{ request('status') == 'reportado' ? 'selected' : '' }}>Reportado</option>
                        <option value="en_investigacion" {{ request('status') == 'en_investigacion' ? 'selected' : '' }}>En Investigación</option>
                        <option value="recuperado" {{ request('status') == 'recuperado' ? 'selected' : '' }}>Recuperado</option>
                        <option value="cerrado" {{ request('status') == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 d-flex align-items-end mb-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-2 flex-grow-1">
                        <i class="fa-solid fa-filter mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'robos']) }}" class="btn btn-light btn-sm border">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Container -->
        <div id="thefts-table-container">
            @include('financieras.robos.table', ['theftReports' => $theftReports ?? App\Models\FinTheftReport::with(['device.brand', 'sale', 'branch'])->latest('incident_date')->paginate(15)])
        </div>
    </div>
</div>
