@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    /* =========================================================
       DISEÑO ESTANDARIZADO BITÁCORA Y HISTORIAL DE CAJA
       ========================================================= */
    .cash-table-wrapper {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        background: #fff;
    }
    .cash-table {
        margin-bottom: 0;
        font-size: 13px;
        width: 100% !important;
    }
    .cash-table thead th {
        background-color: #f8fafc !important;
        border-bottom: 2px solid #e2e8f0 !important;
        border-top: none !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        padding: 12px 14px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    .cash-table tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        border-bottom: none !important;
    }
    .cash-table tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .badge-income {
        background-color: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
        border-radius: 50rem !important;
        padding: 4px 10px !important;
        font-weight: 600 !important;
        font-size: 11px !important;
        display: inline-block;
    }
    .badge-expense {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
        border-radius: 50rem !important;
        padding: 4px 10px !important;
        font-weight: 600 !important;
        font-size: 11px !important;
        display: inline-block;
    }
    .ref-badge {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 11px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 3px 8px;
        white-space: nowrap;
        display: inline-block;
    }
    /* Neutralizar estilos thead-dark residuales de plantillas o DataTables */
    .table thead.thead-dark th, thead.thead-dark th, .thead-dark th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        border-bottom: 2px solid #e2e8f0 !important;
        border-top: none !important;
    }
    /* Estilos Datatables Botones de Exportar */
    .dt-buttons .btn {
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 5px 12px !important;
        border-radius: 6px !important;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="las la-cash-register text-primary"></i> Historial y Control de Caja
            </h3>
            <small class="text-muted">
                Sucursal Activa: <strong>{{ $branches->firstWhere('id', $branchId)->name ?? (auth()->user()->branch->name ?? 'Principal') }}</strong>
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('central-pos.index') }}" class="btn btn-primary mr-2">
                <i class="las la-layer-group"></i> Punto de Venta            </a>
            <a href="{{ route('cash.dailyCut', ['branch_id' => $branchId, 'date' => $date]) }}" class="btn btn-info mr-2">
                <i class="las la-calculator"></i> Corte Diario (Pre-Corte X / Corte Z)
            </a>
            <a href="{{ route('cash.expenseTypes.index') }}" class="btn btn-outline-dark mr-2">
                <i class="fa-solid fa-tags"></i> Tipos de Egreso
            </a>
            <a href="{{ route('cash.expenses', ['branch_id' => $branchId, 'date' => $date]) }}" class="btn btn-outline-danger">
                <i class="fa-solid fa-file-invoice-dollar"></i> Reporte de Gastos
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPIs del Periodo / Fecha --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-success shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ingresos en Efectivo (Caja)</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($cashIncome ?? $totalIncome, 2) }}</div>
                        <small class="text-muted d-block mt-1">Trf: ${{ number_format($transferIncome ?? 0, 2) }} | Tarj: ${{ number_format($cardIncome ?? 0, 2) }}</small>
                    </div>
                    <i class="las la-arrow-circle-down fa-2x text-success" style="font-size: 2.2rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Egresos en Efectivo</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($totalExpense ?? 0, 2) }}</div>
                        <small class="text-muted d-block mt-1">Gastos y salidas físicas de cajón</small>
                    </div>
                    <i class="las la-arrow-circle-up fa-2x text-danger" style="font-size: 2.2rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-primary shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Flujo Neto en Efectivo</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($cashBalance ?? $balance, 2) }}</div>
                        <small class="text-muted d-block mt-1">Global Contable: ${{ number_format($balance ?? 0, 2) }}</small>
                    </div>
                    <i class="las la-wallet fa-2x text-primary" style="font-size: 2.2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros por Fecha y Sucursal --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('cash.index') }}" class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Modo Fecha:</label>
                    <select name="filter_type" id="cashIdxFilterType" class="form-control form-control-sm">
                        <option value="dia" {{ ($filterType ?? 'dia') === 'dia' ? 'selected' : '' }}>Día en específico</option>
                        <option value="rango" {{ ($filterType ?? 'dia') === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                        @if(!empty($canAllDates))
                            <option value="todos" {{ ($filterType ?? 'dia') === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <div id="cashIdxDiaBox" style="{{ ($filterType ?? 'dia') === 'dia' ? '' : 'display:none;' }}">
                        <label class="small font-weight-bold mb-1">Fecha específica:</label>
                        <input type="date"
                               name="date"
                               id="date"
                               value="{{ $selectedDate ?? $date ?? '' }}"
                               @if(empty($canAllDates) && !empty($minAllowedDate)) min="{{ $minAllowedDate }}" @endif
                               class="form-control form-control-sm"
                               {{ ($filterType ?? 'dia') === 'dia' ? '' : 'disabled' }}>
                    </div>
                    <div id="cashIdxRangoBox" style="{{ ($filterType ?? 'dia') === 'rango' ? '' : 'display:none;' }}">
                        <label class="small font-weight-bold mb-1">Entre Fechas:</label>
                        <div class="input-group input-group-sm">
                            <input type="date"
                                   name="start_date"
                                   value="{{ $startDate ?? $selectedDate ?? '' }}"
                                   @if(empty($canAllDates) && !empty($minAllowedDate)) min="{{ $minAllowedDate }}" @endif
                                   class="form-control"
                                   {{ ($filterType ?? 'dia') === 'rango' ? '' : 'disabled' }}>
                            <div class="input-group-prepend input-group-append"><span class="input-group-text">a</span></div>
                            <input type="date"
                                   name="end_date"
                                   value="{{ $endDate ?? $selectedDate ?? '' }}"
                                   @if(empty($canAllDates) && !empty($minAllowedDate)) min="{{ $minAllowedDate }}" @endif
                                   class="form-control"
                                   {{ ($filterType ?? 'dia') === 'rango' ? '' : 'disabled' }}>
                        </div>
                    </div>
                    <div id="cashIdxTodosBox" style="{{ ($filterType ?? 'dia') === 'todos' ? '' : 'display:none;' }}">
                        <label class="small font-weight-bold mb-1">Período:</label>
                        <div class="form-control form-control-sm bg-light text-muted">Todos los registros</div>
                    </div>
                    @if(empty($canAllDates) && !empty($minAllowedDate))
                        <small class="text-muted d-block mt-1">
                            <i class="las la-lock"></i> Máx. 2 días atrás (desde {{ \Carbon\Carbon::parse($minAllowedDate)->format('d/m/Y') }})
                        </small>
                    @endif
                    @if(!empty($wasRestricted))
                        <small class="text-warning font-weight-bold d-block">Filtro ajustado al límite de 2 días.</small>
                    @endif
                    <script>
                    (function() {
                        var sel = document.getElementById('cashIdxFilterType');
                        if (!sel) return;
                        var boxes = {
                            dia: document.getElementById('cashIdxDiaBox'),
                            rango: document.getElementById('cashIdxRangoBox'),
                            todos: document.getElementById('cashIdxTodosBox')
                        };
                        function sync() {
                            Object.keys(boxes).forEach(function(k) {
                                var el = boxes[k];
                                if (!el) return;
                                var active = sel.value === k;
                                el.style.display = active ? '' : 'none';
                                var inputs = el.querySelectorAll('input, select');
                                for (var i = 0; i < inputs.length; i++) inputs[i].disabled = !active;
                            });
                        }
                        sel.addEventListener('change', sync);
                        sync();
                    })();
                    </script>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                    <div class="col-md-4">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4 mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="las la-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Desglose por Cliente del Día / Periodo --}}
    @if(isset($customerMovements) && $customerMovements->count())
        <div class="card border-0 shadow-sm cash-table-wrapper mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="fa-solid fa-users text-primary mr-2"></i>Desglose de Movimientos por Cliente ({{ $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : 'General' }})
                </h6>
                <span class="badge badge-light border text-secondary font-weight-bold px-2 py-1">
                    {{ $customerMovements->count() }} Clientes con actividad
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover cash-table mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="text-center"># Movs</th>
                                <th class="text-right">Efectivo</th>
                                <th class="text-right">Tarjeta</th>
                                <th class="text-right">Transferencia</th>
                                <th class="text-right text-success">Total Ingresos</th>
                                <th class="text-right text-danger">Total Egresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customerMovements as $cm)
                                <tr>
                                    <td class="font-weight-bold text-dark">
                                        <i class="fa-solid fa-user text-muted mr-1 font-size-11"></i>{{ $cm['customer_name'] }}
                                        @if(!empty($cm['is_external_customer']))
                                            <span class="badge badge-warning text-dark font-size-10 ml-1">Cliente Externo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light border font-weight-bold px-2 py-1">{{ $cm['count'] }}</span>
                                    </td>
                                    <td class="text-right font-weight-bold text-secondary">${{ number_format($cm['cash'], 2) }}</td>
                                    <td class="text-right font-weight-bold text-secondary">${{ number_format($cm['card'], 2) }}</td>
                                    <td class="text-right font-weight-bold text-secondary">${{ number_format($cm['transfer'], 2) }}</td>
                                    <td class="text-right font-weight-bold text-success">${{ number_format($cm['income'], 2) }}</td>
                                    <td class="text-right font-weight-bold text-danger">${{ number_format($cm['expense'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabla Detallada de Movimientos --}}
    <div class="card border-0 shadow-sm cash-table-wrapper mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 font-weight-bold text-dark">
                <i class="fa-solid fa-list-check text-primary mr-2"></i>Bitácora Detallada de Movimientos de Caja
            </h6>
            @if(method_exists($cashFlows, 'total'))
                <span class="badge badge-light border text-secondary font-weight-normal px-2 py-1">
                    {{ $cashFlows->total() }} movimientos registrados
                </span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover cash-table mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('cash.export') ? 'true' : 'false' }}">
                    <thead>
                        <tr>
                            <th style="min-width: 125px;">Fecha / Hora</th>
                            <th style="min-width: 140px;">Cliente</th>
                            <th class="text-center" style="min-width: 95px;">Tipo</th>
                            <th style="min-width: 130px;">Método / Pago</th>
                            <th class="text-right" style="min-width: 110px;">Monto</th>
                            <th style="min-width: 220px;">Descripción</th>
                            <th class="text-center" style="min-width: 120px;">Módulo</th>
                            <th style="min-width: 130px;">Referencia</th>
                            <th style="min-width: 100px;">Cajero</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cashFlows as $flow)
                            @php
                                $desc = $flow->description;
                                if (preg_match('/^Cobro Centralizado \([^)]+\):\s*(.*)$/i', $desc, $matches)) {
                                    $desc = $matches[1];
                                }
                                $method = strtolower($flow->payment_method ?? 'efectivo');
                                $mod = strtolower($flow->module ?? 'general');
                            @endphp
                            <tr>
                                <td style="white-space: nowrap;">
                                    <div class="font-weight-bold text-dark font-size-13">{{ $flow->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted font-size-11"><i class="fa-regular fa-clock mr-1"></i>{{ $flow->created_at->format('H:i') }} hrs</small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark font-size-13">
                                        {{ $flow->customer_display_name }}
                                    </div>
                                    @if($flow->external_customer_id)
                                        @can('external_customer.read')
                                            <a href="{{ route('external-customers.show', $flow->external_customer_id) }}" class="badge badge-warning text-dark font-size-10 mt-1">
                                                <i class="fa-solid fa-user-tag mr-1"></i>Cliente Externo
                                            </a>
                                        @else
                                            <span class="badge badge-warning text-dark font-size-10 mt-1">Cliente Externo</span>
                                        @endcan
                                    @endif
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    @if($flow->type === 'income')
                                        <span class="badge-income">
                                            <i class="fa-solid fa-arrow-down mr-1 font-size-10"></i>Ingreso
                                        </span>
                                    @else
                                        <span class="badge-expense">
                                            <i class="fa-solid fa-arrow-up mr-1 font-size-10"></i>Egreso
                                        </span>
                                    @endif
                                </td>
                                <td style="white-space: nowrap;">
                                    @if($method === 'efectivo')
                                        <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-money-bill-wave text-success mr-1"></i>Efectivo
                                        </span>
                                    @elseif($method === 'tarjeta')
                                        <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-credit-card text-primary mr-1"></i>Tarjeta
                                        </span>
                                    @elseif($method === 'transferencia')
                                        <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-building-columns text-transfer mr-1"></i>Transferencia
                                        </span>
                                    @elseif($method === 'mixto')
                                        <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-layer-group text-warning mr-1"></i>Mixto
                                        </span>
                                        <div class="font-size-11 text-muted mt-1" style="line-height: 1.3;">
                                            @if($flow->cash_amount > 0)<div><span class="text-success font-weight-bold">Ef:</span> ${{ number_format($flow->cash_amount, 2) }}</div>@endif
                                            @if($flow->card_amount > 0)<div><span class="text-primary font-weight-bold">Tj:</span> ${{ number_format($flow->card_amount, 2) }}</div>@endif
                                            @if($flow->transfer_amount > 0)<div><span class="text-transfer font-weight-bold">Tr:</span> ${{ number_format($flow->transfer_amount, 2) }}</div>@endif
                                        </div>
                                    @else
                                        <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                            {{ ucfirst($method) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right" style="white-space: nowrap;">
                                    <span class="font-weight-bold font-size-14 {{ $flow->type === 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $flow->type === 'income' ? '+' : '-' }}${{ number_format($flow->amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($flow->resolved_expense_type_label)
                                        <span class="badge badge-danger mb-1">{{ $flow->resolved_expense_type_label }}</span>
                                    @endif
                                    <div class="text-dark font-size-12" style="line-height: 1.4;">
                                        {{ $desc ?: 'Sin motivo adicional' }}
                                    </div>
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    @if($flow->resolved_expense_type_label)
                                        <span class="badge badge-light border text-danger font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-tags mr-1"></i>{{ $flow->resolved_expense_type_label }}
                                        </span>
                                    @elseif(str_contains($mod, 'cobro') || str_contains($mod, 'venta') || str_contains($mod, 'pos'))
                                        <span class="badge badge-light border text-primary font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-cart-shopping mr-1"></i>POS / Ventas
                                        </span>
                                    @elseif(str_contains($mod, 'financ'))
                                        <span class="badge badge-light border text-info font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-mobile-screen mr-1"></i>Financiera
                                        </span>
                                    @elseif(str_contains($mod, 'prést') || str_contains($mod, 'prest'))
                                        <span class="badge badge-light border text-success font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Préstamos
                                        </span>
                                    @elseif(str_contains($mod, 'tanda'))
                                        <span class="badge badge-light border text-warning font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-piggy-bank mr-1"></i>Tandas
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-secondary font-weight-bold px-2 py-1">
                                            <i class="fa-solid fa-receipt mr-1"></i>{{ $flow->module ?? 'General' }}
                                        </span>
                                    @endif
                                </td>
                                <td style="white-space: nowrap;">
                                    @if($flow->reference)
                                        <span class="ref-badge">{{ $flow->reference }}</span>
                                    @else
                                        <span class="text-muted font-size-11">-</span>
                                    @endif
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="badge badge-light border text-secondary font-weight-normal px-2 py-1">
                                        <i class="fa-solid fa-user-tie mr-1"></i>{{ $flow->user->name ?? 'Sistema' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                    No hay movimientos de caja registrados para este criterio o fecha seleccionada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Mostrando bitácora de movimientos de caja
                </div>
                <div>
                    {{ $cashFlows->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario de Registro Manual --}}
    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.create'))
        @include('cash.partials.form')
    @endif
</div>
@endsection
