@php
    $p = $bitacora['prefix'] ?? '';
    $df = $bitacora['dateFilter'];
    $filterType = $df['filter_type'];
    $canAllDates = $df['can_all_dates'];
    $minAllowedDate = $df['min_allowed_date'];
    $singleDate = $df['date'];
    $startDate = $df['start_date'] ?? $df['date'];
    $endDate = $df['end_date'] ?? $df['date'];
    $totals = $bitacora['totals'];
    $formAction = $formAction ?? route('financieras.bitacora.index');
    $uid = ($p !== '' ? rtrim($p, '_') : 'finbit') . '_df';
    $exportParams = array_merge(request()->query(), ['prefix' => $p]);
    $canExportPdf = auth()->user()->isSuperAdmin() || auth()->user()->can('financieras.bitacora.export_pdf');
    $canExportExcel = auth()->user()->isSuperAdmin() || auth()->user()->can('financieras.bitacora.export_excel');
@endphp

<div class="card shadow-sm mb-4 border-primary" id="{{ $uid }}_card">
    <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 text-white font-weight-bold">
                <i class="fa-solid fa-book-open mr-1"></i> Bitácora de Abonos y Enganches a Financieras
            </h5>
            <small class="text-light">
                Período: <strong>{{ $bitacora['periodLabel'] }}</strong> &bull;
                Sucursal: <strong>{{ $bitacora['branchName'] }}</strong>
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            @if($canExportPdf)
                <a href="{{ route('financieras.bitacora.export.pdf', $exportParams) }}"
                   class="btn btn-danger btn-sm font-weight-bold mr-2 shadow-sm">
                    <i class="las la-file-pdf"></i> Exportar PDF
                </a>
            @endif
            @if($canExportExcel)
                <a href="{{ route('financieras.bitacora.export.excel', $exportParams) }}"
                   class="btn btn-success btn-sm font-weight-bold shadow-sm">
                    <i class="las la-file-excel"></i> Exportar Excel
                </a>
            @endif
        </div>
    </div>

    <div class="card-body">
        @if(!empty($df['was_restricted']))
            <div class="alert alert-warning py-2 px-3 small mb-3">
                <i class="las la-exclamation-triangle mr-1"></i>
                Tu rol solo permite consultar o exportar movimientos de hasta <strong>2 días de anterioridad</strong>
                (desde el <strong>{{ \Carbon\Carbon::parse($minAllowedDate)->format('d/m/Y') }}</strong>).
                Se ajustó el filtro automáticamente.
            </div>
        @endif

        <form method="GET" action="{{ $formAction }}" class="row align-items-end mb-3" id="{{ $uid }}_form">
            @if(!empty($includeTab))
                <input type="hidden" name="tab" value="bitacora">
            @endif
            @foreach(($preserveParams ?? []) as $paramName => $paramVal)
                @if($paramVal !== null && $paramVal !== '')
                    <input type="hidden" name="{{ $paramName }}" value="{{ $paramVal }}">
                @endif
            @endforeach

            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold mb-1">Modo Fecha:</label>
                <select name="{{ $p }}filter_type" id="{{ $uid }}_type" class="form-control form-control-sm font-weight-bold">
                    <option value="dia" {{ $filterType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                    <option value="rango" {{ $filterType === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                    @if($canAllDates)
                        <option value="todos" {{ $filterType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                    @endif
                </select>
            </div>

            <div class="col-md-2 mb-2" id="{{ $uid }}_dia_box" style="{{ $filterType === 'dia' ? '' : 'display:none;' }}">
                <label class="small font-weight-bold mb-1">Fecha:</label>
                <input type="date"
                       name="{{ $p }}date"
                       value="{{ $singleDate }}"
                       @if(!$canAllDates) min="{{ $minAllowedDate }}" @endif
                       class="form-control form-control-sm"
                       {{ $filterType === 'dia' ? '' : 'disabled' }}>
            </div>

            <div class="col-md-3 mb-2" id="{{ $uid }}_rango_box" style="{{ $filterType === 'rango' ? '' : 'display:none;' }}">
                <label class="small font-weight-bold mb-1">Desde - Hasta:</label>
                <div class="input-group input-group-sm">
                    <input type="date"
                           name="{{ $p }}start_date"
                           value="{{ $startDate }}"
                           @if(!$canAllDates) min="{{ $minAllowedDate }}" @endif
                           class="form-control"
                           {{ $filterType === 'rango' ? '' : 'disabled' }}>
                    <div class="input-group-prepend input-group-append">
                        <span class="input-group-text">a</span>
                    </div>
                    <input type="date"
                           name="{{ $p }}end_date"
                           value="{{ $endDate }}"
                           @if(!$canAllDates) min="{{ $minAllowedDate }}" @endif
                           class="form-control"
                           {{ $filterType === 'rango' ? '' : 'disabled' }}>
                </div>
            </div>

            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold mb-1">Tipo Movimiento:</label>
                <select name="{{ $p }}movement_type" class="form-control form-control-sm">
                    <option value="todos" {{ $bitacora['movementType'] === 'todos' ? 'selected' : '' }}>Abonos + Enganches</option>
                    <option value="abono" {{ $bitacora['movementType'] === 'abono' ? 'selected' : '' }}>Solo Abonos</option>
                    <option value="enganche" {{ $bitacora['movementType'] === 'enganche' ? 'selected' : '' }}>Solo Enganches</option>
                </select>
            </div>

            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold mb-1">Financiera:</label>
                <select name="{{ $p }}financiera_id" class="form-control form-control-sm">
                    <option value="">Todas</option>
                    @foreach($bitacora['financieras'] as $fin)
                        <option value="{{ $fin->id }}" {{ (string) $bitacora['financieraId'] === (string) $fin->id ? 'selected' : '' }}>
                            {{ $fin->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($bitacora['canAllBranches'])
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Sucursal:</label>
                    <select name="{{ $p }}branch_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach($bitacora['branches'] as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $bitacora['branchId'] === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold mb-1">Buscar:</label>
                <input type="text"
                       name="{{ $p }}search"
                       value="{{ $bitacora['search'] }}"
                       class="form-control form-control-sm"
                       placeholder="Folio, cliente, IMEI, TAG...">
            </div>

            <div class="col-md-2 mb-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm mr-1">
                    <i class="las la-filter"></i> Filtrar
                </button>
                <a href="{{ $formAction }}{{ !empty($includeTab) ? '?tab=bitacora' : '' }}" class="btn btn-outline-secondary btn-sm">
                    Limpiar
                </a>
            </div>
        </form>

        @if(!$canAllDates)
            <div class="text-muted small mb-2">
                <i class="las la-lock"></i> Restricción activa: fechas permitidas desde <strong>{{ \Carbon\Carbon::parse($minAllowedDate)->format('d/m/Y') }}</strong> (máx. 2 días atrás).
            </div>
        @endif

        {{-- Resumen de totales --}}
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="p-2 rounded bg-light border">
                    <small class="text-muted d-block">Abonos ({{ $totals['abonos_count'] }})</small>
                    <strong class="text-success h6 mb-0">${{ number_format($totals['abonos_total'], 2) }}</strong>
                    @if($totals['abonos_commission'] > 0)
                        <small class="text-muted d-block">Comisión: ${{ number_format($totals['abonos_commission'], 2) }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="p-2 rounded bg-light border">
                    <small class="text-muted d-block">Enganches ({{ $totals['enganches_count'] }})</small>
                    <strong class="text-info h6 mb-0">${{ number_format($totals['enganches_total'], 2) }}</strong>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="p-2 rounded bg-light border">
                    <small class="text-muted d-block">Total Bitácora ({{ $totals['count'] }} movs)</small>
                    <strong class="text-primary h6 mb-0">${{ number_format($totals['grand_total'], 2) }}</strong>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="p-2 rounded bg-light border">
                    <small class="text-muted d-block">Desglose por Financiera</small>
                    @if($bitacora['groupedByFinanciera']->isEmpty())
                        <span class="text-muted small">Sin movimientos</span>
                    @else
                        <div class="small" style="max-height: 46px; overflow-y: auto;">
                            @foreach($bitacora['groupedByFinanciera'] as $grp)
                                <div class="d-flex justify-content-between">
                                    <span>{{ $grp['name'] }} ({{ $grp['count'] }})</span>
                                    <strong>${{ number_format($grp['total'], 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-2">
                <thead class="thead-light">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Tipo</th>
                        <th>Folio / Recibo</th>
                        <th>Financiera</th>
                        <th>TAG / Contrato</th>
                        <th>IMEI</th>
                        <th>Cliente</th>
                        <th>Sucursal</th>
                        <th>Método</th>
                        <th class="text-right">Importe</th>
                        <th class="text-right">Comisión</th>
                        <th class="text-right">Total</th>
                        <th>Registró</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bitacora['entries'] as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row['date_formatted'] }}</td>
                            <td>
                                <span class="badge {{ $row['movement_type'] === 'abono' ? 'badge-success' : 'badge-info' }}">
                                    {{ $row['movement_label'] }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                @if(!empty($row['fin_sale_id']) && auth()->user()->can('financieras.ventas.read'))
                                    <a href="{{ route('financieras.ventas.show', $row['fin_sale_id']) }}" class="font-weight-bold text-primary">
                                        {{ $row['folio'] }}
                                    </a>
                                @else
                                    <strong>{{ $row['folio'] }}</strong>
                                @endif
                            </td>
                            <td>{{ $row['financiera_name'] }}</td>
                            <td>{{ $row['tag_contrato'] }}</td>
                            <td class="small">{{ $row['imei'] }}</td>
                            <td>
                                {{ $row['customer_name'] }}
                                @if(!empty($row['is_external_customer']))
                                    <span class="badge badge-secondary">Externo</span>
                                @endif
                            </td>
                            <td>{{ $row['branch_name'] }}</td>
                            <td><span class="badge badge-light border">{{ $row['payment_method'] }}</span></td>
                            <td class="text-right">${{ number_format($row['amount'], 2) }}</td>
                            <td class="text-right">${{ number_format($row['commission'], 2) }}</td>
                            <td class="text-right font-weight-bold">${{ number_format($row['total'], 2) }}</td>
                            <td class="small">{{ $row['user_name'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-3">
                                No hay registros de abonos o enganches para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bitacora['entries'] instanceof \Illuminate\Contracts\Pagination\Paginator && $bitacora['entries']->hasPages())
            <div class="d-flex justify-content-end mt-2">
                {{ $bitacora['entries']->links() }}
            </div>
        @endif
    </div>
</div>

<script>
(function() {
    var selectEl = document.getElementById('{{ $uid }}_type');
    if (!selectEl) return;
    var diaBox = document.getElementById('{{ $uid }}_dia_box');
    var rangoBox = document.getElementById('{{ $uid }}_rango_box');

    function toggleInputs(container, active) {
        if (!container) return;
        container.style.display = active ? '' : 'none';
        var fields = container.querySelectorAll('input, select');
        for (var i = 0; i < fields.length; i++) {
            fields[i].disabled = !active;
        }
    }

    function sync() {
        var val = selectEl.value;
        toggleInputs(diaBox, val === 'dia');
        toggleInputs(rangoBox, val === 'rango');
    }

    selectEl.addEventListener('change', sync);
    sync();
})();
</script>

