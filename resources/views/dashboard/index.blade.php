@extends('dashboard.body.main')

@section('container')
@if (auth()->user()->isSuperAdmin() || auth()->user()->canAny(['dashboard.read', 'dashboard.menu']))
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session()->has('success'))
                <div class="alert text-white bg-success" role="alert">
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            @if (session()->has('info'))
                <div class="alert alert-info" role="alert">
                    {{ session('info') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert text-white bg-danger" role="alert">
                    <div class="iq-alert-text">{{ session('error') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card card-transparent card-block card-stretch card-height border-none">
                <div class="card-body p-0 mt-lg-2 mt-0">
                    <h3 class="mb-2">Hola {{ auth()->user()->name }}</h3>
                    <p class="mb-2 mr-4 text-muted">Panel de control centralizado: consulta pagos de financieras por fecha, exporta reportes y gestiona movimientos multipago del día.</p>
                    <a href="{{ route('central-pos.index') }}" class="btn btn-primary btn-sm">
                        <i class="las la-cash-register"></i> Punto de venta
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row">
                <div class="col-lg-4 col-md-4">
                    <div class="card card-block card-stretch card-height">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 card-total-sale">
                                <div class="icon iq-icon-box-2 bg-info-light">
                                    <img src="{{ asset('assets/images/product/1.png') }}" class="img-fluid" alt="image">
                                </div>
                                <div>
                                    <p class="mb-1 small text-muted">Abonos Financieras (Filtro)</p>
                                    <h4>${{ number_format($finTotals['total_paid'] ?? 0, 2) }}</h4>
                                </div>
                            </div>
                            <small class="text-muted">{{ $finTotals['count'] ?? 0 }} abonos | Comisiones: ${{ number_format($finTotals['commission'] ?? 0, 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="card card-block card-stretch card-height">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 card-total-sale">
                                <div class="icon iq-icon-box-2 bg-success-light">
                                    <img src="{{ asset('assets/images/product/3.png') }}" class="img-fluid" alt="image">
                                </div>
                                <div>
                                    <p class="mb-1 small text-muted">Movimientos POS ({{ $movPeriodLabel }})</p>
                                    <h4>${{ number_format($movTotals['total'] ?? 0, 2) }}</h4>
                                </div>
                            </div>
                            <small class="text-muted">{{ $movTotals['count'] ?? 0 }} procesos de venta/cobro completados</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="card card-block card-stretch card-height">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 card-total-sale">
                                <div class="icon iq-icon-box-2 bg-warning-light">
                                    <img src="{{ asset('assets/images/product/2.png') }}" class="img-fluid" alt="image">
                                </div>
                                <div>
                                    <p class="mb-1 small text-muted">Ventas Históricas POS</p>
                                    <h4>${{ number_format($total_paid ?? 0, 2) }}</h4>
                                </div>
                            </div>
                            <small class="text-muted">Pedidos completos: {{ count($complete_orders) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- SECCIÓN 1: CONSULTA DE PAGOS DE FINANCIERAS POR FECHA (EXPORTAR PDF/EXCEL) --}}
    {{-- ========================================================================= --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 text-white font-weight-bold">
                    <i class="las la-mobile mr-1"></i> Consulta de Pagos de Financieras por Fecha (Por Equipo / TAG / IMEI)
                </h5>
                <small class="text-light">Filtra por rango de fechas, catálogo de financiera o sucursal y descarga en PDF o Excel</small>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.financieras.export_pdf'))
                    <form method="GET" action="{{ route('dashboard.financieras.pdf') }}" class="d-inline-flex align-items-center mr-2 bg-white rounded px-2 py-1">
                        <input type="hidden" name="fin_filter_type" value="dia">
                        @if($branchId)
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        @endif
                        <input type="date"
                               name="fin_date"
                               value="{{ $finDate }}"
                               @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                               class="form-control form-control-sm border-0 p-0 mr-1"
                               style="height:22px; width:125px; font-size:12px;"
                               title="Fecha para PDF diario de todas las financieras">
                        <button type="submit" class="btn btn-xs btn-danger font-weight-bold text-nowrap" title="Descargar PDF del día con todas las financieras agrupadas">
                            <i class="las la-file-pdf"></i> PDF Diario Todas
                        </button>
                    </form>
                    <a href="{{ route('dashboard.financieras.pdf', request()->query()) }}" class="btn btn-light btn-sm font-weight-bold text-danger mr-2">
                        <i class="las la-file-pdf"></i> PDF Filtro
                    </a>
                @endif
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.financieras.export_excel'))
                    <a href="{{ route('dashboard.financieras.excel', request()->query()) }}" class="btn btn-light btn-sm font-weight-bold text-success">
                        <i class="las la-file-excel"></i> Exportar Excel
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(!empty($dateRestrictionTriggered))
                <div class="alert alert-warning py-2 px-3 small mb-3">
                    <i class="las la-exclamation-triangle mr-1"></i>
                    Tu rol permite consultar o exportar únicamente fechas de hasta <strong>2 días de anterioridad</strong>
                    (desde el <strong>{{ \Carbon\Carbon::parse($minAllowedDate)->format('d/m/Y') }}</strong>).
                </div>
            @endif
            @php
                $canMultiFinFilter = $canMultiFilterFinancieras ?? \App\Services\FinancieraFilterService::userCanFilterMultiple();
                $selectedFinFilterIds = $selectedFinancieraIds ?? \App\Services\FinancieraFilterService::resolveSelectedIds(
                    \App\Services\FinancieraFilterService::rawInputFromRequest(request()),
                    null,
                    $canMultiFinFilter
                );
            @endphp
            <form method="GET" action="{{ route('dashboard') }}" class="row align-items-end mb-3" id="dashFinFilterForm">
                @foreach(request()->query() as $qk => $qv)
                    @if(!is_array($qv) && !str_starts_with($qk, 'fin_') && !in_array($qk, ['date', 'start_date', 'end_date', 'filter_type', 'fin_page', 'financiera_id', 'financiera_ids'], true))
                        <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
                    @endif
                @endforeach
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Modo Fecha:</label>
                    <select name="fin_filter_type" id="dashFinFilterType" class="form-control form-control-sm font-weight-bold">
                        <option value="dia" {{ $finFilterType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                        <option value="rango" {{ $finFilterType === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                        @if($canDashboardAllDates)
                            <option value="todos" {{ $finFilterType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2 mb-2" id="dashFinDiaBox" style="{{ $finFilterType === 'dia' ? '' : 'display:none;' }}">
                    <label class="small font-weight-bold mb-1">Fecha:</label>
                    <input type="date"
                           name="fin_date"
                           value="{{ $finDate }}"
                           @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                           class="form-control form-control-sm"
                           {{ $finFilterType === 'dia' ? '' : 'disabled' }}>
                </div>
                <div class="col-md-3 mb-2" id="dashFinRangoBox" style="{{ $finFilterType === 'rango' ? '' : 'display:none;' }}">
                    <label class="small font-weight-bold mb-1">Entre Fechas:</label>
                    <div class="input-group input-group-sm">
                        <input type="date"
                               name="fin_start_date"
                               value="{{ $finStartDate }}"
                               @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                               class="form-control"
                               {{ $finFilterType === 'rango' ? '' : 'disabled' }}>
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text">a</span>
                        </div>
                        <input type="date"
                               name="fin_end_date"
                               value="{{ $finEndDate }}"
                               @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                               class="form-control"
                               {{ $finFilterType === 'rango' ? '' : 'disabled' }}>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    @if($canMultiFinFilter)
                        <label class="small font-weight-bold mb-1">
                            Catálogo Financiera: <span class="badge badge-info ml-1">Múltiple</span>
                        </label>
                        <select name="financiera_id[]"
                                id="dashFinancieraSelect"
                                class="form-control form-control-sm select2-autocomplete select2-multi-sm"
                                multiple="multiple"
                                data-placeholder="Todas las Financieras (selección múltiple)">
                            @foreach($financieras as $fin)
                                <option value="{{ $fin->id }}" {{ in_array((int) $fin->id, $selectedFinFilterIds, true) ? 'selected' : '' }}>
                                    {{ $fin->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <label class="small font-weight-bold mb-1">Catálogo Financiera:</label>
                        <select name="financiera_id" class="form-control form-control-sm">
                            <option value="">-- Todas las Financieras --</option>
                            @foreach($financieras as $fin)
                                <option value="{{ $fin->id }}" {{ in_array((int) $fin->id, $selectedFinFilterIds, true) ? 'selected' : '' }}>
                                    {{ $fin->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.all_branches') || auth()->user()->can('financieras.all_branches') || auth()->user()->can('cobros.all_branches'))
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control form-control-sm">
                            <option value="">-- Todas --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">TAG / IMEI / Cliente:</label>
                    <input type="text" name="fin_search" value="{{ request('fin_search') }}" class="form-control form-control-sm" placeholder="Buscar...">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-block" title="Consultar">
                        <i class="las la-search"></i>
                    </button>
                </div>
            </form>

            {{-- Resumen Rápido del Filtro de Financieras --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded border text-center">
                        <span class="small text-muted d-block">Abonos a Financieras</span>
                        <strong class="h6 mb-0 text-primary">${{ number_format($finTotals['amount'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded border text-center">
                        <span class="small text-muted d-block">Comisiones Opcionales</span>
                        <strong class="h6 mb-0 text-info">${{ number_format($finTotals['commission'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded border text-center">
                        <span class="small text-muted d-block">Total Ingresado a Caja</span>
                        <strong class="h6 mb-0 text-success">${{ number_format($finTotals['total_paid'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded border text-center small">
                        Ef: <strong>${{ number_format($finTotals['cash'], 2) }}</strong> |
                        Tj: <strong>${{ number_format($finTotals['card'], 2) }}</strong> |
                        Tr: <strong>${{ number_format($finTotals['transfer'], 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Financiera</th>
                            <th>Device ID / TAG</th>
                            <th>IMEI</th>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                            <th>Método</th>
                            <th class="text-right">Abono ($)</th>
                            <th class="text-right">Comisión ($)</th>
                            <th class="text-right">Total ($)</th>
                            <th class="text-center">Contrato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($financieraPayments as $fp)
                            <tr>
                                <td>{{ optional($fp->payment_date)->format('d/m/Y H:i') }}</td>
                                <td><span class="badge badge-primary">{{ $fp->financiera?->name ?? ($fp->finSupplier?->name ?? ($fp->sale?->financiera?->name ?? 'Financiera')) }}</span></td>
                                <td class="font-weight-bold"><code>{{ $fp->device_tag ?? '-' }}</code></td>
                                <td><small>{{ $fp->imei ?? '-' }}</small></td>
                                <td>
                                    {{ $fp->customer_name ?? ($fp->finSale?->customer_name ?? ($fp->externalCustomer?->tit_name ?? 'Cliente')) }}
                                    @if($fp->external_customer_id)
                                        <span class="badge badge-warning text-dark font-size-10 ml-1">Externo</span>
                                    @endif
                                </td>
                                <td>{{ $fp->branch->name ?? 'General' }}</td>
                                <td>
                                    <span class="badge badge-secondary text-uppercase">{{ $fp->payment_method }}</span>
                                    @if($fp->payment_method === 'mixto')
                                        <small class="d-block text-muted">
                                            Ef:${{ number_format($fp->cash_amount, 2) }} Tj:${{ number_format($fp->card_amount, 2) }} Tr:${{ number_format($fp->transfer_amount, 2) }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-right">${{ number_format($fp->amount, 2) }}</td>
                                <td class="text-right">${{ number_format($fp->commission_amount, 2) }}</td>
                                <td class="text-right font-weight-bold text-success">${{ number_format($fp->total_paid, 2) }}</td>
                                <td class="text-center">
                                    @if($fp->fin_sale_id)
                                        <a href="{{ route('financieras.ventas.show', $fp->fin_sale_id) }}" class="btn btn-xs btn-outline-info py-0 px-2" title="Ver Venta en Financieras">
                                            <i class="las la-eye"></i> #{{ $fp->fin_sale_id }}
                                        </a>
                                    @elseif($fp->is_external_company)
                                        <span class="badge badge-light border text-dark">Equipo Externo</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-3">No hay abonos de financieras registrados en este periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $financieraPayments->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- SECCIÓN 2: GESTIÓN DE MOVIMIENTOS (PROCESOS DE VENTA CON VARIOS PAGOS)    --}}
    {{-- ========================================================================= --}}
    <div class="card shadow-sm mb-4 border-dark">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 text-white font-weight-bold">
                    <i class="las la-layer-group mr-1"></i> Gestión de Movimientos Diarios / Ventas POS
                </h5>
                <small class="text-light">Período: <strong>{{ $movPeriodLabel }}</strong> &bull; Procesos de venta con múltiples conceptos y pagos mixtos</small>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.ventas.export_pdf'))
                    <a href="{{ route('dashboard.ventas.pdf', request()->query()) }}" class="btn btn-danger btn-sm font-weight-bold mr-2 shadow-sm">
                        <i class="las la-file-pdf"></i> Exportar PDF
                    </a>
                @endif
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.ventas.export_excel'))
                    <a href="{{ route('dashboard.ventas.excel', request()->query()) }}" class="btn btn-success btn-sm font-weight-bold shadow-sm">
                        <i class="las la-file-excel"></i> Exportar Excel
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body pb-0">
            <form method="GET" action="{{ route('dashboard') }}" class="row align-items-end mb-2" id="dashMovFilterForm">
                @foreach(request()->query() as $qk => $qv)
                    @if(!str_starts_with($qk, 'mov_') && $qk !== 'mov_page' && !in_array($qk, ['financiera_id', 'financiera_ids'], true))
                        @if(!is_array($qv))
                            <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
                        @endif
                    @endif
                @endforeach
                @if($canMultiFinFilter)
                    @foreach($selectedFinFilterIds as $sfId)
                        <input type="hidden" name="financiera_id[]" value="{{ $sfId }}">
                    @endforeach
                @elseif(!empty($selectedFinFilterIds))
                    <input type="hidden" name="financiera_id" value="{{ $selectedFinFilterIds[0] }}">
                @endif
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Modo Fecha:</label>
                    <select name="mov_filter_type" id="dashMovFilterType" class="form-control form-control-sm font-weight-bold">
                        <option value="dia" {{ $movFilterType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                        <option value="rango" {{ $movFilterType === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                        @if($canDashboardAllDates)
                            <option value="todos" {{ $movFilterType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2 mb-2" id="dashMovDiaBox" style="{{ $movFilterType === 'dia' ? '' : 'display:none;' }}">
                    <label class="small font-weight-bold mb-1">Fecha:</label>
                    <input type="date"
                           name="mov_date"
                           value="{{ $movDate }}"
                           @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                           class="form-control form-control-sm"
                           {{ $movFilterType === 'dia' ? '' : 'disabled' }}>
                </div>
                <div class="col-md-3 mb-2" id="dashMovRangoBox" style="{{ $movFilterType === 'rango' ? '' : 'display:none;' }}">
                    <label class="small font-weight-bold mb-1">Entre Fechas:</label>
                    <div class="input-group input-group-sm">
                        <input type="date"
                               name="mov_start_date"
                               value="{{ $movStartDate }}"
                               @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                               class="form-control"
                               {{ $movFilterType === 'rango' ? '' : 'disabled' }}>
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text">a</span>
                        </div>
                        <input type="date"
                               name="mov_end_date"
                               value="{{ $movEndDate }}"
                               @if(!$canDashboardAllDates) min="{{ $minAllowedDate }}" @endif
                               class="form-control"
                               {{ $movFilterType === 'rango' ? '' : 'disabled' }}>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Estado:</label>
                    <select name="mov_status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="completado" {{ $movStatus === 'completado' ? 'selected' : '' }}>Completado</option>
                        <option value="cancelado" {{ $movStatus === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Buscar:</label>
                    <input type="text" name="mov_search" value="{{ $movSearch }}" class="form-control form-control-sm" placeholder="Folio o cliente...">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="submit" class="btn btn-dark btn-sm font-weight-bold btn-block">
                        <i class="las la-filter"></i>
                    </button>
                </div>
            </form>
        </div>
        <script>
        (function() {
            function bindToggle(selectId, diaId, rangoId) {
                var sel = document.getElementById(selectId);
                if (!sel) return;
                var dia = document.getElementById(diaId);
                var rango = document.getElementById(rangoId);
                function toggleBox(box, active) {
                    if (!box) return;
                    box.style.display = active ? '' : 'none';
                    var inputs = box.querySelectorAll('input, select');
                    for (var i = 0; i < inputs.length; i++) inputs[i].disabled = !active;
                }
                function sync() {
                    toggleBox(dia, sel.value === 'dia');
                    toggleBox(rango, sel.value === 'rango');
                }
                sel.addEventListener('change', sync);
                sync();
            }
            bindToggle('dashFinFilterType', 'dashFinDiaBox', 'dashFinRangoBox');
            bindToggle('dashMovFilterType', 'dashMovDiaBox', 'dashMovRangoBox');
        })();
        </script>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="p-2 border rounded bg-light text-center">
                        <span class="small text-muted d-block">Total Procesos del Día</span>
                        <strong class="h6 mb-0">{{ $movTotals['count'] }} Movimientos</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 border rounded bg-light text-center">
                        <span class="small text-muted d-block">Efectivo Recibido</span>
                        <strong class="h6 mb-0 text-success">${{ number_format($movTotals['cash'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 border rounded bg-light text-center">
                        <span class="small text-muted d-block">Tarjeta + Transferencia</span>
                        <strong class="h6 mb-0 text-primary">${{ number_format($movTotals['card'] + $movTotals['transfer'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 border rounded bg-light text-center">
                        <span class="small text-muted d-block">Total Acumulado del Día</span>
                        <strong class="h6 mb-0 text-dark">${{ number_format($movTotals['total'], 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Folio</th>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Tab / Sucursal</th>
                            <th>Conceptos Integrados (4 en 1)</th>
                            <th>Desglose de Pagos (Efectivo / Tarjeta / Transf.)</th>
                            <th class="text-right">Total ($)</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Gestionar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyMovements as $tx)
                            <tr>
                                <td class="font-weight-bold"><code>{{ $tx->folio }}</code></td>
                                <td>{{ optional($tx->transaction_date)->format('H:i') }}</td>
                                <td class="font-weight-bold">{{ $tx->customer_name ?? ' Público en General' }}</td>
                                <td>
                                    <span class="badge badge-light border">{{ $tx->tab_label ?? 'Tab POS' }}</span>
                                    <small class="d-block text-muted">{{ $tx->branch->name ?? 'Principal' }}</small>
                                </td>
                                <td class="small">
                                    @if(is_array($tx->items_payload))
                                        <ul class="pl-3 mb-0">
                                            @foreach($tx->items_payload as $item)
                                                <li>
                                                    <strong class="text-uppercase">[{{ $item['section'] ?? 'item' }}]</strong>
                                                    {{ $item['title'] ?? ($item['description'] ?? 'Concepto') }}
                                                    — ${{ number_format((float)($item['total'] ?? ($item['amount'] ?? 0)), 2) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">Movimiento unificado</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <span class="badge badge-info text-uppercase">{{ $tx->payment_method }}</span>
                                    <div>
                                        @if($tx->cash_amount > 0) <span class="mr-2">Ef: <strong>${{ number_format($tx->cash_amount, 2) }}</strong></span> @endif
                                        @if($tx->card_amount > 0) <span class="mr-2">Tj: <strong>${{ number_format($tx->card_amount, 2) }}</strong></span> @endif
                                        @if($tx->transfer_amount > 0) <span>Tr: <strong>${{ number_format($tx->transfer_amount, 2) }}</strong></span> @endif
                                    </div>
                                </td>
                                <td class="text-right font-weight-bold h6 mb-0">${{ number_format($tx->total_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($tx->status === 'completado')
                                        <span class="badge badge-success">Completado</span>
                                    @else
                                        <span class="badge badge-danger">Cancelado</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" onclick="window.openTicketPreviewModal('{{ route('central-pos.receipt', $tx->id) }}', 'Ticket {{ $tx->transaction_code }}')" class="btn btn-xs btn-outline-primary mr-1" title="Ver Ticket completo en pantalla / WhatsApp">
                                            <i class="las la-eye"></i> Ver
                                        </button>
                                        <button type="button" onclick="window.printTicketDirectly('{{ route('central-pos.receipt', $tx->id) }}?skip_drawer=1')" class="btn btn-xs btn-outline-dark mr-1" title="Imprimir Ticket">
                                            <i class="las la-print"></i> Ticket
                                        </button>
                                        @php
                                            $canCancelTx = auth()->user()->isSuperAdmin() || auth()->user()->can('dashboard.cancel_transaction') || auth()->user()->can('cobros.cancel_transaction');
                                            $isTodayCorteZClosed = in_array((int) $tx->branch_id, $todayClosedBranchIds ?? [], true);
                                        @endphp
                                        @if($tx->status === 'completado' && $canCancelTx)
                                            @if(!$isTodayCorteZClosed)
                                                <form method="POST" action="{{ route('dashboard.transactions.cancel', $tx->id) }}" onsubmit="return confirm('¿Seguro que deseas cancelar este movimiento?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Cancelar movimiento">
                                                        <i class="las la-ban"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="btn btn-xs btn-outline-secondary disabled" title="No se puede cancelar: el Corte Z del día ya fue aplicado en esta sucursal">
                                                    <i class="las la-lock"></i>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">
                                    No hay procesos de venta/cobro registrados en la fecha {{ $movPeriodLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $dailyMovements->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@else
<div class="row justify-content-center mt-5">
    <div class="col-md-8 text-center">
        <h1>¡Bienvenido {{ auth()->user()->name }}!</h1>
        <p class="lead">Selecciona un módulo en el menú lateral para comenzar a operar.</p>
    </div>
</div>
@endif
@endsection
