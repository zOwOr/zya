@extends('dashboard.body.main')

@section('container')
@if (auth()->user()->isSuperAdmin() || auth()->user()->canAny(['dashboard.read', 'dashboard.menu', 'cobros.read', 'financieras.read', 'cash.read']))
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
                                    <p class="mb-1 small text-muted">Movimientos POS ({{ \Carbon\Carbon::parse($movDate)->format('d/m/Y') }})</p>
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
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <a href="{{ route('dashboard.financieras.pdf', request()->query()) }}" class="btn btn-light btn-sm font-weight-bold text-danger mr-2">
                    <i class="las la-file-pdf"></i> Exportar PDF
                </a>
                <a href="{{ route('dashboard.financieras.excel', request()->query()) }}" class="btn btn-light btn-sm font-weight-bold text-success">
                    <i class="las la-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}" class="row align-items-end mb-3">
                <input type="hidden" name="mov_date" value="{{ $movDate }}">
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Fecha Inicio:</label>
                    <input type="date" name="fin_start_date" value="{{ $finStartDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Fecha Fin:</label>
                    <input type="date" name="fin_end_date" value="{{ $finEndDate }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Catálogo Financiera:</label>
                    <select name="financiera_id" class="form-control form-control-sm">
                        <option value="">-- Todas las Financieras --</option>
                        @foreach($financieras as $fin)
                            <option value="{{ $fin->id }}" {{ (string)request('financiera_id') === (string)$fin->id ? 'selected' : '' }}>
                                {{ $fin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('financieras.all_branches') || auth()->user()->can('cobros.all_branches'))
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
                                <td><span class="badge badge-primary">{{ $fp->finSupplier->name ?? ($fp->finSale->financiera ?? 'Financiera') }}</span></td>
                                <td class="font-weight-bold"><code>{{ $fp->device_tag ?? '-' }}</code></td>
                                <td><small>{{ $fp->imei ?? '-' }}</small></td>
                                <td>{{ $fp->customer_name ?? ($fp->finSale->customer_name ?? 'Cliente') }}</td>
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
                    <i class="las la-layer-group mr-1"></i> Gestión de Movimientos Diarios (Procesos de Venta / Cobros Multipago por Día)
                </h5>
                <small class="text-light">Consulta y gestiona procesos de venta con múltiples conceptos y pagos mixtos en la fecha seleccionada</small>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <input type="hidden" name="fin_start_date" value="{{ $finStartDate }}">
                <input type="hidden" name="fin_end_date" value="{{ $finEndDate }}">
                @if($branchId)
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif
                <label class="small text-white mb-0 mr-2">Fecha Operativa:</label>
                <input type="date" name="mov_date" value="{{ $movDate }}" class="form-control form-control-sm mr-2" style="width: 155px;">
                <button type="submit" class="btn btn-warning btn-sm font-weight-bold">
                    <i class="las la-calendar-check"></i> Ver Día
                </button>
            </form>
        </div>
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
                                        <a href="{{ route('central-pos.receipt', $tx->id) }}" target="_blank" class="btn btn-xs btn-outline-dark mr-1" title="Reimprimir Ticket">
                                            <i class="las la-print"></i> Ticket
                                        </a>
                                        @if($tx->status === 'completado' && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.cancel_transaction')))
                                            <form method="POST" action="{{ route('dashboard.transactions.cancel', $tx->id) }}" onsubmit="return confirm('¿Seguro que deseas cancelar este movimiento?')">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Cancelar movimiento">
                                                    <i class="las la-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">
                                    No hay procesos de venta/cobro registrados en la fecha {{ \Carbon\Carbon::parse($movDate)->format('d/m/Y') }}.
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
