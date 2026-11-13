@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="fa-solid fa-file-invoice-dollar text-danger mr-1"></i> Reporte de Gastos por Período
            </h3>
            <small class="text-muted">
                Período: <strong>{{ $periodLabel }}</strong> ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                &bull; Sucursal: <strong>{{ $branchLabel }}</strong>
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
            <a href="{{ route('cash.dailyCut', ['branch_id' => $branchId]) }}" class="btn btn-outline-secondary btn-sm mr-2 shadow-sm font-weight-bold">
                <i class="las la-calculator mr-1"></i> Ir a Cortes X / Z
            </a>
            <a href="{{ route('cash.expenseTypes.index') }}" class="btn btn-outline-dark btn-sm mr-2 shadow-sm font-weight-bold">
                <i class="fa-solid fa-tags mr-1"></i> Tipos de Egreso
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.export') || auth()->user()->can('cash.read'))
                <a href="{{ route('cash.expenses.pdf', request()->query()) }}" class="btn btn-danger btn-sm mr-2 shadow-sm font-weight-bold">
                    <i class="las la-file-pdf mr-1"></i> Descargar PDF
                </a>
                <a href="{{ route('cash.expenses.excel', request()->query()) }}" class="btn btn-success btn-sm shadow-sm font-weight-bold">
                    <i class="las la-file-excel mr-1"></i> Descargar Excel
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-danger">
        <div class="card-header bg-light py-2 px-3">
            <strong class="font-size-13 text-dark"><i class="las la-filter text-danger mr-1"></i> Filtros de Consulta de Gastos</strong>
        </div>
        <div class="card-body py-3">
            <form method="GET" action="{{ route('cash.expenses') }}" class="row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Período:</label>
                    <select name="period" id="expensePeriodSelect" class="form-control form-control-sm font-weight-bold">
                        <option value="dia" {{ $period === 'dia' ? 'selected' : '' }}>Por Día</option>
                        <option value="semana" {{ $period === 'semana' ? 'selected' : '' }}>Por Semana</option>
                        <option value="mes" {{ $period === 'mes' ? 'selected' : '' }}>Por Mes</option>
                        <option value="rango" {{ $period === 'rango' ? 'selected' : '' }}>Rango personalizado</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2" id="expenseSingleDateBox" style="{{ $period === 'rango' ? 'display:none;' : '' }}">
                    <label class="small font-weight-bold mb-1">Fecha base:</label>
                    <input type="date" name="date" id="expenseSingleDateInp" value="{{ $refDate }}" class="form-control form-control-sm" {{ $period === 'rango' ? 'disabled' : '' }}>
                </div>

                <div class="col-md-4 mb-2" id="expenseRangeBox" style="{{ $period === 'rango' ? '' : 'display:none;' }}">
                    <label class="small font-weight-bold mb-1">Rango de fechas (Desde - Hasta):</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" id="expenseStartInp" value="{{ $startDate }}" class="form-control" {{ $period === 'rango' ? '' : 'disabled' }}>
                        <div class="input-group-prepend input-group-append"><span class="input-group-text">a</span></div>
                        <input type="date" name="end_date" id="expenseEndInp" value="{{ $endDate }}" class="form-control" {{ $period === 'rango' ? '' : 'disabled' }}>
                    </div>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                    <div class="col-md-2 mb-2">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control form-control-sm font-weight-bold">
                            <option value="all" {{ $isAllBranches ? 'selected' : '' }}>Todas las sucursales</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ !$isAllBranches && (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Buscar concepto:</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Descripción, ref...">
                </div>

                <div class="col-md-2 mb-2 d-flex">
                    <button type="submit" class="btn btn-danger btn-sm flex-grow-1 font-weight-bold mr-1">
                        <i class="las la-search"></i> Consultar
                    </button>
                    <a href="{{ route('cash.expenses') }}" class="btn btn-outline-secondary btn-sm" title="Restablecer">
                        <i class="las la-sync"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body text-center">
                    <small class="text-muted text-uppercase font-weight-bold d-block">Total Gastos del Período</small>
                    <h2 class="font-weight-bold text-danger mb-0">${{ number_format($totalExpense, 2) }}</h2>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-dark shadow-sm h-100">
                <div class="card-body text-center">
                    <small class="text-muted text-uppercase font-weight-bold d-block">Cantidad de Gastos Registrados</small>
                    <h2 class="font-weight-bold text-dark mb-0">{{ $expenses->count() }}</h2>
                    <small class="text-muted">Salidas registradas en caja</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-info shadow-sm h-100">
                <div class="card-body py-2">
                    <small class="text-muted text-uppercase font-weight-bold d-block mb-1">Desglose por Sucursal</small>
                    @forelse($byBranch as $row)
                        <div class="d-flex justify-content-between font-size-12 border-bottom py-1">
                            <span><strong>{{ $row['branch_name'] }}</strong> ({{ $row['count'] }})</span>
                            <span class="text-danger font-weight-bold">${{ number_format($row['total'], 2) }}</span>
                        </div>
                    @empty
                        <span class="text-muted font-size-12">Sin gastos registrados en el período.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold text-dark"><i class="las la-list mr-1 text-danger"></i> Detalle de Gastos del Período</h6>
            <span class="badge badge-danger px-3 py-1">Total: ${{ number_format($totalExpense, 2) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 font-size-13">
                    <thead class="thead-light">
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Sucursal</th>
                            <th>Tipo de Egreso / Ref.</th>
                            <th>Motivo / Detalle del Gasto</th>
                            <th>Usuario</th>
                            <th class="text-right">Monto ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                            <tr>
                                <td class="text-nowrap">{{ optional($exp->created_at)->format('d/m/Y H:i') }}</td>
                                <td><span class="badge badge-light border">{{ $exp->branch?->name ?? 'General' }}</span></td>
                                <td>
                                    <span class="font-weight-bold text-danger">{{ $exp->resolved_expense_type_label ?: ($exp->module ?: 'Caja') }}</span>
                                    @if($exp->reference)
                                        <small class="d-block text-muted"><code>{{ $exp->reference }}</code></small>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-dark">{{ $exp->description ?: 'Sin motivo adicional' }}</td>
                                <td>{{ $exp->user?->name ?? 'Sistema' }}</td>
                                <td class="text-right font-weight-bold text-danger">${{ number_format((float) $exp->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No se encontraron gastos para el filtro seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($expenses->count() > 0)
                        <tfoot class="thead-light font-weight-bold">
                            <tr>
                                <th colspan="5" class="text-right">TOTAL DEL PERÍODO CONSULTADO:</th>
                                <th class="text-right text-danger font-size-15">${{ number_format($totalExpense, 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodSelect = document.getElementById('expensePeriodSelect');
    const singleBox = document.getElementById('expenseSingleDateBox');
    const singleInp = document.getElementById('expenseSingleDateInp');
    const rangeBox = document.getElementById('expenseRangeBox');
    const startInp = document.getElementById('expenseStartInp');
    const endInp = document.getElementById('expenseEndInp');

    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            const isRange = this.value === 'rango';
            singleBox.style.display = isRange ? 'none' : '';
            if (singleInp) singleInp.disabled = isRange;
            rangeBox.style.display = isRange ? '' : 'none';
            if (startInp) startInp.disabled = !isRange;
            if (endInp) endInp.disabled = !isRange;
        });
    }
});
</script>
@endsection

