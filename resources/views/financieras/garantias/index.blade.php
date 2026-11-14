@include('financieras.partials.filter-styles')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center fin-section-header">
            <div class="col-md-6 col-12">
                <h5 class="fin-section-title mb-1">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i>Gestión de Garantías y Etapas
                </h5>
                <p class="fin-section-subtitle">Seguimiento de reparaciones, etapas de servicio y resoluciones técnicas.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                @can('financieras.garantias.export')
                    <a href="{{ route('financieras.garantias.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                        <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                    </a>
                @endcan
                @if (auth()->user()->can('financieras.garantias.create'))
                    <a href="{{ route('financieras.garantias.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Nueva Garantía
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-warranties-form" method="GET" action="{{ route('financieras.index') }}" class="fin-filter-shell">
            <input type="hidden" name="tab" value="garantias">
            <div class="row fin-grid-row align-items-end">
                <div class="col-lg-4 col-md-6 mb-2 mb-lg-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-magnifying-glass fin-ico-search"></i>
                        <span>Buscar</span>
                    </label>
                    <input type="text" name="search" class="form-control fin-filter-control" placeholder="Código, IMEI, falla o cliente..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-layer-group fin-ico-type"></i>
                        <span>Etapa de Garantía</span>
                    </label>
                    <select name="stage_id" class="form-control custom-select fin-filter-control">
                        <option value="">Todas las etapas</option>
                        @foreach (($stages ?? $warrantyStages ?? []) as $st)
                            <option value="{{ $st->id }}" {{ request('stage_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
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
                <div class="col-lg-2 col-md-6 mb-0">
                    <label class="fin-filter-label">
                        <i class="fa-solid fa-tag fin-ico-status"></i>
                        <span>Estado</span>
                    </label>
                    <select name="status" class="form-control custom-select fin-filter-control">
                        <option value="">Todos</option>
                        <option value="abierta" {{ request('status') == 'abierta' ? 'selected' : '' }}>Abierta</option>
                        <option value="en_proceso" {{ request('status') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                        <option value="resuelta" {{ request('status') == 'resuelta' ? 'selected' : '' }}>Resuelta</option>
                        <option value="rechazada" {{ request('status') == 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                    </select>
                </div>
            </div>

            <div class="fin-filter-subcard fin-filter-subcard-compact">
                <div class="fin-period-footer">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                        @php
                            $canAllWarrantyBranches = auth()->user()->can('financieras.inventario.all_branches');
                            $hasWarrantyFilters = request()->filled('search') || request()->filled('stage_id') || ($canAllWarrantyBranches && request()->filled('branch_id')) || request()->filled('status');
                        @endphp
                        @if($hasWarrantyFilters)
                            <span class="font-size-12 text-muted font-weight-bold mr-1">
                                <i class="fa-solid fa-sliders mr-1 text-primary"></i>Filtros activos:
                            </span>
                            @if(request('search'))
                                <span class="fin-active-pill">
                                    Buscar: <strong>{{ request('search') }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('search'), ['tab' => 'garantias'])) }}">&times;</a>
                                </span>
                            @endif
                            @if(request('stage_id'))
                                <span class="fin-active-pill">
                                    Etapa: <strong>{{ collect($stages ?? $warrantyStages ?? [])->firstWhere('id', request('stage_id'))?->name ?? request('stage_id') }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('stage_id'), ['tab' => 'garantias'])) }}">&times;</a>
                                </span>
                            @endif
                            @if($canAllWarrantyBranches && request('branch_id'))
                                <span class="fin-active-pill">
                                    Sucursal: <strong>{{ $branches->firstWhere('id', request('branch_id'))?->name ?? request('branch_id') }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('branch_id'), ['tab' => 'garantias'])) }}">&times;</a>
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="fin-active-pill">
                                    Estado: <strong>{{ ucfirst(str_replace('_', ' ', request('status'))) }}</strong>
                                    <a href="{{ route('financieras.index', array_merge(request()->except('status'), ['tab' => 'garantias'])) }}">&times;</a>
                                </span>
                            @endif
                        @else
                            <span class="font-size-12 text-muted">
                                <i class="fa-solid fa-circle-info mr-1 text-primary"></i>Filtra por código, IMEI, etapa técnica, sucursal o estado de la garantía.
                            </span>
                        @endif
                    </div>

                    <div class="fin-actions-wrap">
                        <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}" class="fin-filter-reset" title="Restablecer filtros">
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
        <div id="warranties-table-container">
            @include('financieras.garantias.table', ['warranties' => $warranties ?? App\Models\FinWarranty::with(['device.brand', 'sale', 'branch', 'currentStage'])->latest('opened_at')->paginate(15)->appends(['tab' => 'garantias'])])
        </div>
    </div>
</div>
