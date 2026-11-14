@include('financieras.partials.filter-styles')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center fin-section-header">
            <div class="col-md-6 col-12">
                <h5 class="fin-section-title fin-section-title-danger mb-1">
                    <i class="fa-solid fa-shield-halved mr-2"></i>Reporte y Seguimiento de Robos
                </h5>
                <p class="fin-section-subtitle">Control de denuncias por robo o extravío de dispositivos y seguimiento legal.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                @can('financieras.robos.export')
                    <a href="{{ route('financieras.robos.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                        <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                    </a>
                @endcan
                @if (auth()->user()->can('financieras.robos.create'))
                    <a href="{{ route('financieras.robos.create') }}" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Reportar Robo
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-thefts-form" method="GET" action="{{ route('financieras.index') }}" class="fin-filter-shell">
            <input type="hidden" name="tab" value="robos">
            <div class="row fin-grid-row align-items-end">
                <div class="col-lg-5 col-md-6 mb-2 mb-lg-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-magnifying-glass fin-ico-search"></i>
                        <span>Buscar</span>
                    </label>
                    <input type="text" name="search" class="form-control fin-filter-control" placeholder="Folio, IMEI, acta o cliente..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-4 col-md-6 mb-2 mb-lg-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-store fin-ico-branch"></i>
                        <span>Sucursal</span>
                    </label>
                    @can('financieras.inventario.all_branches')
                        <select name="branch_id" class="form-control custom-select fin-filter-control">
                            <option value="">Todas</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control fin-filter-control" value="{{ auth()->user()->branch?->name ?? 'Sucursal asignada' }}" readonly title="Tu sucursal asignada">
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endcan
                </div>
                <div class="col-lg-3 col-md-12 mb-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-tag fin-ico-status"></i>
                        <span>Estado</span>
                    </label>
                    <select name="status" class="form-control custom-select fin-filter-control">
                        <option value="">Todos</option>
                        <option value="reportado" {{ request('status') == 'reportado' ? 'selected' : '' }}>Reportado</option>
                        <option value="en_investigacion" {{ request('status') == 'en_investigacion' ? 'selected' : '' }}>En Investigación</option>
                        <option value="recuperado" {{ request('status') == 'recuperado' ? 'selected' : '' }}>Recuperado</option>
                        <option value="cerrado" {{ request('status') == 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                    </select>
                </div>
            </div>

            <div class="fin-filter-subcard fin-filter-subcard-compact">
                <div class="fin-period-footer">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                        @php
                            $canAllTheftBranches = auth()->user()->can('financieras.inventario.all_branches');
                            $hasTheftFilters = request()->filled('search') || ($canAllTheftBranches && request()->filled('branch_id')) || request()->filled('status');
                        @endphp
                        @if($hasTheftFilters)
                            <span class="font-size-12 text-muted font-weight-bold mr-1">
                                <i class="fa-solid fa-sliders mr-1 text-primary"></i>Filtros activos:
                            </span>
                            @if(request('search'))
                                <span class="fin-active-pill">
                                    Buscar: <strong>{{ request('search') }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'robos'])) }}">&times;</a>
                                </span>
                            @endif
                            @if($canAllTheftBranches && request('branch_id'))
                                <span class="fin-active-pill">
                                    Sucursal: <strong>{{ $branches->firstWhere('id', request('branch_id'))?->name ?? request('branch_id') }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('branch_id'), ['tab' => 'robos'])) }}">&times;</a>
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="fin-active-pill">
                                    Estado: <strong>{{ ucfirst(str_replace('_', ' ', request('status'))) }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('status'), ['tab' => 'robos'])) }}">&times;</a>
                                </span>
                            @endif
                        @else
                            <span class="font-size-12 text-muted">
                                <i class="fa-solid fa-circle-info mr-1 text-danger"></i>Filtra por folio, IMEI, acta ministerial, sucursal o estatus del reporte.
                            </span>
                        @endif
                    </div>

                    <div class="fin-actions-wrap">
                        <a href="{{ route('financieras.index', ['tab' => 'robos']) }}" class="fin-filter-reset" title="Restablecer filtros">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Limpiar</span>
                        </a>
                        <button type="submit" class="fin-filter-submit">
                            <i class="fa-solid fa-filter"></i>
                            <span>Filtrar</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Table Container -->
        <div id="thefts-table-container">
            @include('financieras.robos.table', ['theftReports' => $theftReports ?? App\Models\FinTheftReport::with(['device.brand', 'sale', 'branch'])->latest('incident_date')->paginate(15)->appends(['tab' => 'robos'])])
        </div>
    </div>
</div>
