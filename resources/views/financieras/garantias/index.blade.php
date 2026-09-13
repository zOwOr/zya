<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 col-12">
                <h5 class="font-weight-bold text-primary mb-1">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i>Gestión de Garantías y Etapas
                </h5>
                <p class="text-muted font-size-13 mb-0">Seguimiento de reparaciones, etapas de servicio y resoluciones técnicas.</p>
            </div>
            <div class="col-md-6 col-12 text-md-right mt-3 mt-md-0">
                <a href="{{ route('financieras.garantias.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm mr-2">
                    <i class="fa-solid fa-file-excel mr-1"></i>Exportar Excel
                </a>
                @if (auth()->user()->can('financieras.garantias.create'))
                    <a href="{{ route('financieras.garantias.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus mr-1"></i>Nueva Garantía
                    </a>
                @endif
            </div>
        </div>

        <!-- Filters Form -->
        <form id="filter-warranties-form" method="GET" action="{{ route('financieras.index') }}" class="bg-light p-3 rounded mb-3">
            <input type="hidden" name="tab" value="garantias">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Buscar (Código, IMEI, Falla, Cliente)</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-size-12 font-weight-bold text-muted mb-1">Etapa de Garantía</label>
                    <select name="stage_id" class="form-control form-control-sm">
                        <option value="">Todas las etapas</option>
                        @foreach (($stages ?? $warrantyStages ?? []) as $st)
                            <option value="{{ $st->id }}" {{ request('stage_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
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
                        <option value="abierta" {{ request('status') == 'abierta' ? 'selected' : '' }}>Abierta</option>
                        <option value="en_proceso" {{ request('status') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                        <option value="resuelta" {{ request('status') == 'resuelta' ? 'selected' : '' }}>Resuelta</option>
                        <option value="rechazada" {{ request('status') == 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 d-flex align-items-end mb-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-2 flex-grow-1">
                        <i class="fa-solid fa-filter mr-1"></i>Filtrar
                    </button>
                    <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}" class="btn btn-light btn-sm border">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Table Container -->
        <div id="warranties-table-container">
            @include('financieras.garantias.table', ['warranties' => $warranties ?? App\Models\FinWarranty::with(['device.brand', 'sale', 'branch', 'currentStage'])->latest('opened_at')->paginate(15)])
        </div>
    </div>
</div>
