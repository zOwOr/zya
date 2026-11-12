@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    @php
        $printParams = ['branch_id' => $branchId, 'filter_type' => $filterType];
        if ($filterType === 'dia') {
            $printParams['date'] = $selectedDate;
            if (isset($finalDailyCut) && $finalDailyCut) {
                $printParams['cut_id'] = $finalDailyCut->id;
            }
        } elseif ($filterType === 'rango') {
            $printParams['start_date'] = $startDate;
            $printParams['end_date'] = $endDate;
        }
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="las la-calculator text-primary"></i>
                @if($filterType === 'dia')
                    Corte de Caja Diario - {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                @elseif($filterType === 'rango')
                    Corte de Caja - Rango del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                @else
                    Corte de Caja - Historial Completo (Todos los Registros)
                @endif
            </h3>
            <small class="text-muted">
                Sucursal: <strong>{{ $currentBranch->name ?? ($branches->firstWhere('id', $branchId)->name ?? 'Principal') }}</strong>
                &bull; Corte Automático:
                @if($currentBranch && $currentBranch->auto_cut_enabled)
                    <span class="badge badge-success"><i class="fa-solid fa-clock mr-1"></i>Activo a las {{ $currentBranch->auto_cut_time }} hrs</span>
                @else
                    <span class="badge badge-secondary"><i class="fa-solid fa-hand mr-1"></i>Solo Manual</span>
                @endif
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches') || auth()->user()->can('cash.edit'))
                <button type="button" class="btn btn-outline-primary mr-2 shadow-sm font-weight-bold" data-toggle="modal" data-target="#autoCutConfigModal">
                    <i class="las la-cog"></i> Configurar Cierre Automático
                </button>
            @endif
            <a href="{{ route('cash.index', ['branch_id' => $branchId, 'date' => $selectedDate]) }}" class="btn btn-outline-secondary mr-2 shadow-sm">
                <i class="las la-arrow-left"></i> Historial de Caja
            </a>
            <a href="{{ route('cash.printCut', $printParams) }}"
               class="btn btn-dark shadow-sm font-weight-bold" target="_blank">
                <i class="las la-print"></i>
                @if($filterType === 'dia')
                    Imprimir Corte
                @elseif($filterType === 'rango')
                    Imprimir Reporte del Rango
                @else
                    Imprimir Todos los Cortes
                @endif
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
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

    {{-- Filtro por Fecha, Rango o Todos los registros, Sucursal y Búsqueda --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="font-weight-bold text-dark font-size-13">
                <i class="las la-filter text-primary mr-1"></i> Filtros de Consulta y Reportes de Corte
            </span>
            <span class="badge badge-primary font-size-12 px-2 py-1">
                @if($filterType === 'dia')
                    Filtro Activo: Día Específico
                @elseif($filterType === 'rango')
                    Filtro Activo: Rango de Fechas
                @else
                    Filtro Activo: Todos los Registros
                @endif
            </span>
        </div>
        <div class="card-body py-3">
            <form method="GET" action="{{ route('cash.dailyCut') }}" id="cutFilterForm" class="row align-items-end">
                <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1"><i class="las la-calendar mr-1"></i>Tipo de Filtro:</label>
                    <select name="filter_type" id="filterTypeSelect" class="form-control font-weight-bold text-primary">
                        <option value="dia" {{ $filterType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                        <option value="rango" {{ $filterType === 'rango' ? 'selected' : '' }}>Rango de fechas</option>
                        <option value="todos" {{ $filterType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-4 mb-2 mb-md-0" id="singleDateContainer" style="{{ $filterType === 'dia' ? '' : 'display: none;' }}">
                    <label class="small font-weight-bold mb-1">Fecha de Corte:</label>
                    <input type="date" name="date" id="singleDateInput" value="{{ $selectedDate }}" class="form-control" {{ $filterType === 'dia' ? '' : 'disabled' }}>
                </div>

                <div class="col-lg-4 col-md-5 mb-2 mb-md-0" id="dateRangeContainer" style="{{ $filterType === 'rango' ? '' : 'display: none;' }}">
                    <label class="small font-weight-bold mb-1">Rango de Fechas (Desde - Hasta):</label>
                    <div class="input-group">
                        <input type="date" name="start_date" id="startDateInput" value="{{ $startDate }}" class="form-control" title="Fecha inicial" {{ $filterType === 'rango' ? '' : 'disabled' }}>
                        <div class="input-group-prepend input-group-append">
                            <span class="input-group-text font-size-12 px-2">a</span>
                        </div>
                        <input type="date" name="end_date" id="endDateInput" value="{{ $endDate }}" class="form-control" title="Fecha final" {{ $filterType === 'rango' ? '' : 'disabled' }}>
                    </div>
                </div>

                <div class="col-lg-3 col-md-4 mb-2 mb-md-0" id="allRecordsContainer" style="{{ $filterType === 'todos' ? '' : 'display: none;' }}">
                    <label class="small font-weight-bold mb-1">Alcance:</label>
                    <div class="alert alert-info py-1 px-2 mb-0 font-size-12 d-flex align-items-center" style="height: 38px;">
                        <i class="las la-globe mr-1 font-size-16"></i> Histórico completo sin límite de fecha
                    </div>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                    <div class="col-lg-2 col-md-3 mb-2 mb-md-0">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control font-weight-bold">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-lg-2 col-md-3 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1">Buscar:</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cliente, ref, módulo...">
                </div>

                <div class="col-lg-2 col-md-3 mt-2 mt-md-0 d-flex">
                    <button class="btn btn-primary flex-grow-1 mr-1 shadow-sm font-weight-bold" type="submit">
                        <i class="las la-search"></i> Filtrar
                    </button>
                    <a href="{{ route('cash.dailyCut', ['branch_id' => $branchId]) }}" class="btn btn-outline-secondary shadow-sm" title="Restablecer filtros a Hoy">
                        <i class="las la-sync"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($filterType === 'dia')
    {{-- Estado del Corte del Día --}}
    @if($finalDailyCut)
        <div class="alert alert-success d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <i class="las la-check-circle la-lg mr-1"></i>
                <strong>Corte Final (Corte Z) Aplicado:</strong>
                Cerrado el {{ optional($finalDailyCut->closed_at ?? $finalDailyCut->created_at)->format('d/m/Y H:i') }}
                en modo <span class="badge badge-dark text-uppercase">{{ $finalDailyCut->cut_mode }}</span>
                por <strong>{{ $finalDailyCut->user->name ?? 'Cierre Programado Automático' }}</strong>.
            </div>
            <a href="{{ route('cash.printCut', ['cut_id' => $finalDailyCut->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-sm btn-success">
                <i class="las la-print"></i> Imprimir Corte Z
            </a>
        </div>
    @else
        <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <i class="las la-clock la-lg mr-1"></i>
                <strong>Caja Abierta / En Curso:</strong> Puedes generar <strong>Pre-Cortes (Corte X)</strong> parciales en cualquier momento o aplicar el <strong>Corte Final (Corte Z)</strong> al terminar el turno.
                @if($currentBranch && $currentBranch->auto_cut_enabled)
                    Si no se cierra manualmente, el sistema aplicará el Corte Z automático programado a las <strong>{{ $currentBranch->auto_cut_time }} hrs</strong>.
                @else
                    El corte automático está <strong>desactivado</strong> para esta sucursal (modo 100% manual).
                @endif
            </div>
        </div>
    @endif

    {{-- 1) RESUMEN DE CAJA EN EL ORDEN EXACTO --}}
    <div class="row mb-3">
        <div class="col-md-3 mb-3">
            <div class="card border-info h-100 shadow-sm">
                <div class="card-header bg-info text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-wallet mr-1"></i> Saldo Inicial / Fondo</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold mb-0 text-info">${{ number_format($dailyCut->opening_balance, 2) }}</h3>
                    <small class="text-muted">Efectivo dejado la noche anterior</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-success h-100 shadow-sm">
                <div class="card-header bg-success text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-arrow-circle-down mr-1"></i> Total Ingresos Efectivo</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold text-success mb-0">${{ number_format($handCash, 2) }}</h3>
                    <small class="text-muted">Todo lo que entró en efectivo en el día</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-danger h-100 shadow-sm">
                <div class="card-header bg-danger text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-arrow-circle-up mr-1"></i> Total Egresos Efectivo</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold text-danger mb-0">${{ number_format($dailyCut->total_expense, 2) }}</h3>
                    <small class="text-muted">Gastos y salidas en efectivo de caja</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-dark h-100 shadow-sm">
                <div class="card-header bg-dark text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-calculator mr-1"></i> Efectivo Esperado en Caja</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold mb-0 text-dark">${{ number_format($expectedCash, 2) }}</h3>
                    <small class="text-dark d-block font-weight-bold mt-1">
                        Fórmula: ${{ number_format($dailyCut->opening_balance, 2) }} + ${{ number_format($handCash, 2) }} − ${{ number_format($dailyCut->total_expense, 2) }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- TOTALES INFORMATIVOS Y DE AUDITORÍA (FUERA DEL CÁLCULO DE EFECTIVO ESPERADO) --}}
    <div class="card shadow-sm mb-4 border-secondary bg-light">
        <div class="card-body py-2 px-3">
            <div class="row align-items-center text-center text-md-left">
                <div class="col-md-3 my-1">
                    <span class="text-muted small d-block">Transferencias:</span>
                    <strong class="text-primary font-size-15">${{ number_format($cheque, 2) }}</strong>
                </div>
                <div class="col-md-2 my-1">
                    <span class="text-muted small d-block">Pagos con Tarjeta:</span>
                    <strong class="text-info font-size-15">${{ number_format($due, 2) }}</strong>
                </div>
                <div class="col-md-2 my-1">
                    <span class="text-muted small d-block">Notas Pendientes (Due):</span>
                    <strong class="text-warning font-size-15">${{ number_format($pendingDueTotal, 2) }}</strong>
                </div>
                <div class="col-md-2 my-1">
                    <span class="text-muted small d-block">Total Ingresos Globales:</span>
                    <strong class="text-success font-size-15">${{ number_format($dailyCut->total_income, 2) }}</strong>
                </div>
                <div class="col-md-3 my-1 text-md-right">
                    <span class="text-muted small d-block">Saldo Final Contable Global:</span>
                    <strong class="text-dark font-size-16">${{ number_format($dailyCut->opening_balance + $dailyCut->total_income - $dailyCut->total_expense, 2) }}</strong>
                </div>
            </div>
            <div class="text-muted font-size-11 border-top pt-1 mt-1 text-center">
                <i class="fa-solid fa-circle-info mr-1 text-info"></i>
                Transferencias, pagos con tarjeta y notas pendientes de pago se concilian contablemente pero <strong>quedan fuera</strong> del efectivo físico esperado en cajón.
            </div>
        </div>
    </div>

    {{-- Acciones de Pre-Corte (Corte X) y Corte Final (Corte Z) --}}
    <div class="row mb-4">
        {{-- Panel Pre-Corte (Corte X) --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-file-invoice-dollar"></i> Pre-Corte Parcial (Corte X)</h6>
                    <span class="badge badge-light text-primary">No cierra la caja</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Genera una fotografía arqueo de la caja hasta este minuto para revisión o cambio de cajero, manteniendo la operación abierta.
                    </p>
                    <form method="POST" action="{{ route('cash.preCut') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="small font-weight-bold">Efectivo contado en cajón (opcional):</label>
                                <input type="number" step="0.01" name="declared_cash" class="form-control form-control-sm" placeholder="Ej. {{ number_format($dailyCut->opening_balance + $handCash - $dailyCut->total_expense, 2, '.', '') }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="small font-weight-bold">Observaciones del arqueo:</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Cambio de turno / Arqueo">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="las la-receipt"></i> Generar Pre-Corte (Corte X)
                        </button>
                    </form>

                    @if($preCuts->count())
                        <hr>
                        <h6 class="small font-weight-bold text-muted mb-2">Pre-Cortes (Corte X) realizados en esta fecha:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Usuario</th>
                                        <th>Ingresos</th>
                                        <th>Efectivo Declarado</th>
                                        <th>Dif.</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preCuts as $pc)
                                        <tr>
                                            <td>{{ $pc->created_at->format('H:i') }}</td>
                                            <td>{{ $pc->user->name ?? 'N/D' }}</td>
                                            <td>${{ number_format($pc->total_income, 2) }}</td>
                                            <td>{{ $pc->declared_cash !== null ? '$' . number_format($pc->declared_cash, 2) : '-' }}</td>
                                            <td class="{{ ($pc->difference_amount ?? 0) < 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                                {{ $pc->declared_cash !== null ? '$' . number_format($pc->difference_amount, 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('cash.printCut', ['cut_id' => $pc->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-xs btn-outline-dark py-0 px-1">
                                                    <i class="las la-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel Corte Final (Corte Z) / Conteo Físico por Denominación --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100 border-dark">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-lock"></i> 2) Conteo Físico por Denominación (Corte Z)</h6>
                    <span class="badge badge-warning text-dark font-weight-bold">Cierre Oficial Diario</span>
                </div>
                <div class="card-body">
                    @if(!$finalDailyCut)
                        <p class="small text-muted mb-2">
                            Captura el número de billetes por cada denominación y el importe en monedas/morralla para calcular el efectivo físico declarado y conciliar el cuadre de caja.
                        </p>
                        <form method="POST" action="{{ route('cash.applyCut') }}" id="cutForm">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">

                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0 text-center font-size-12">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Denominación</th>
                                            <th style="width: 110px;">Piezas / Cantidad</th>
                                            <th class="text-right" style="width: 120px;">Subtotal ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach([1000, 500, 200, 100, 50, 20] as $denom)
                                            <tr>
                                                <td class="align-middle font-weight-bold text-left pl-3">
                                                    <i class="fa-solid fa-money-bill-wave text-success mr-1"></i> Billete ${{ number_format($denom) }}
                                                </td>
                                                <td>
                                                    <input type="number" name="denominations[{{ $denom }}]"
                                                           min="0" step="1"
                                                           class="form-control form-control-sm text-center denom-qty"
                                                           data-val="{{ $denom }}"
                                                           placeholder="0">
                                                </td>
                                                <td class="align-middle text-right font-weight-bold text-dark pr-3">
                                                    <span id="sub_{{ $denom }}">$0.00</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td class="align-middle font-weight-bold text-left pl-3">
                                                <i class="fa-solid fa-coins text-warning mr-1"></i> Monedas / Morralla ($)
                                            </td>
                                            <td>
                                                <input type="number" name="denominations[coins]"
                                                       min="0" step="0.01"
                                                       class="form-control form-control-sm text-center denom-coins"
                                                       id="denom_coins"
                                                       placeholder="0.00">
                                            </td>
                                            <td class="align-middle text-right font-weight-bold text-dark pr-3">
                                                <span id="sub_coins">$0.00</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="card bg-light border p-3 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-sm-6 mb-2 mb-sm-0">
                                        <label class="small font-weight-bold mb-1 d-block">Total Físico Declarado ($):</label>
                                        <input type="number" name="declared_cash" id="cut_declared_cash"
                                               step="0.01" min="0"
                                               class="form-control form-control-sm font-weight-bold font-size-15 text-primary"
                                               placeholder="0.00" value="{{ old('declared_cash') }}" required>
                                        <small class="text-muted font-size-11">Suma del conteo de billetes y monedas.</small>
                                    </div>
                                    <div class="col-sm-6 text-sm-right">
                                        <div class="small text-muted">Efectivo Esperado: <strong>${{ number_format($expectedCash, 2) }}</strong></div>
                                        <div class="mt-1">
                                            <span id="liveDifferenceBadge" class="badge badge-secondary font-size-13 py-1 px-2">
                                                Pendiente de conteo
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="small font-weight-bold">Monto a retirar ($):</label>
                                    <input type="number" name="withdraw_amount" id="withdraw_amount" step="0.01" min="0" class="form-control form-control-sm" value="0">
                                    <small class="text-muted font-size-11">Efectivo retirado para depósito o dueño.</small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="small font-weight-bold">Notas de Cierre:</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Corte Z de cierre">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark btn-block btn-sm mt-3 font-weight-bold" id="cutButton">
                                <i class="las la-check-double"></i> Aplicar Corte Final (Corte Z)
                            </button>
                        </form>
                    @else
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="font-weight-bold text-dark font-size-14">
                                    <i class="las la-check-circle text-success mr-1"></i> Corte Z #{{ $finalDailyCut->id }} Registrado
                                </span>
                                <span class="badge badge-dark text-uppercase">{{ $finalDailyCut->cut_mode }}</span>
                            </div>

                            @if(!empty($finalDailyCut->denomination_breakdown))
                                @php $dBreak = $finalDailyCut->denomination_breakdown; @endphp
                                <h6 class="small font-weight-bold text-dark mb-1">Desglose Físico por Denominación:</h6>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-bordered mb-0 font-size-11 text-center">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Denom.</th>
                                                <th>Pzs</th>
                                                <th class="text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(['b1000' => 1000, 'b500' => 500, 'b200' => 200, 'b100' => 100, 'b50' => 50, 'b20' => 20] as $key => $val)
                                                @if(!empty($dBreak[$key]))
                                                    <tr>
                                                        <td class="text-left font-weight-bold">${{ number_format($val) }}</td>
                                                        <td>{{ $dBreak[$key] }}</td>
                                                        <td class="text-right font-weight-bold">${{ number_format($dBreak[$key] * $val, 2) }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            @if(!empty($dBreak['coins']))
                                                <tr>
                                                    <td class="text-left font-weight-bold" colspan="2">Monedas / Morralla</td>
                                                    <td class="text-right font-weight-bold">${{ number_format($dBreak['coins'], 2) }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Efectivo Físico Declarado:</span>
                                    <strong class="text-dark font-size-13">${{ number_format($finalDailyCut->declared_cash, 2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Efectivo Esperado en Caja:</span>
                                    <strong>${{ number_format($expectedCash, 2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                                    <span class="small font-weight-bold">Resultado de Cuadre:</span>
                                    @if(round((float)$finalDailyCut->difference_amount, 2) == 0.0)
                                        <span class="badge badge-success font-size-13 py-1 px-2">
                                            <i class="fa-solid fa-check-circle mr-1"></i>Caja Cuadrada ($0.00)
                                        </span>
                                    @elseif((float)$finalDailyCut->difference_amount < 0)
                                        <span class="badge badge-danger font-size-13 py-1 px-2">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>Faltante: -${{ number_format(abs($finalDailyCut->difference_amount), 2) }}
                                        </span>
                                    @else
                                        <span class="badge badge-warning font-size-13 py-1 px-2">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>Sobrante: +${{ number_format($finalDailyCut->difference_amount, 2) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 text-center">
                                <a href="{{ route('cash.printCut', ['cut_id' => $finalDailyCut->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-sm btn-outline-dark font-weight-bold">
                                    <i class="las la-print"></i> Reimprimir Corte Z Oficial
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
        {{-- Modo Histórico (Rango o Todos los registros) --}}
        <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center mb-4">
            <div>
                <i class="las la-history la-lg mr-1"></i>
                @if($filterType === 'rango')
                    <strong>Modo Consulta por Rango:</strong> Mostrando movimientos y cortes del <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>.
                @else
                    <strong>Modo Consulta Histórico:</strong> Mostrando todos los movimientos y cortes registrados sin límite de fechas.
                @endif
                <span class="d-block small text-muted mt-1">
                    Puedes consultar y reimprimir cualquier corte individual o generar el reporte consolidado. Para aplicar un Corte Z diario o arqueo de hoy, selecciona <strong>"Día en específico"</strong> en los filtros superiores.
                </span>
            </div>
            <a href="{{ route('cash.printCut', $printParams) }}" target="_blank" class="btn btn-sm btn-dark font-weight-bold shadow-sm text-nowrap ml-3">
                <i class="las la-print mr-1"></i> Imprimir Reporte Consolidado
            </a>
        </div>

        {{-- Resumen Consolidado de KPIs del Periodo --}}
        <div class="row mb-3">
            <div class="col-md-3 mb-3">
                <div class="card border-primary h-100 shadow-sm">
                    <div class="card-header bg-primary text-white text-center py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="las la-coins mr-1"></i> Total Ingresos Globales</h6>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="font-weight-bold mb-0 text-primary">${{ number_format($totalIncome, 2) }}</h3>
                        <small class="text-muted font-weight-bold">Efectivo cobrado: ${{ number_format($handCash, 2) }}</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-danger h-100 shadow-sm">
                    <div class="card-header bg-danger text-white text-center py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="las la-arrow-circle-up mr-1"></i> Total Egresos Efectivo</h6>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="font-weight-bold text-danger mb-0">${{ number_format($totalExpense, 2) }}</h3>
                        <small class="text-muted">Salidas de efectivo en caja</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-success h-100 shadow-sm">
                    <div class="card-header bg-success text-white text-center py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="las la-balance-scale mr-1"></i> Balance Operativo</h6>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="font-weight-bold text-success mb-0">${{ number_format($totalIncome - $totalExpense, 2) }}</h3>
                        <small class="text-muted">Ingresos menos egresos</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-dark h-100 shadow-sm">
                    <div class="card-header bg-dark text-white text-center py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="las la-receipt mr-1"></i> Cortes Registrados</h6>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="font-weight-bold mb-0 text-dark">{{ $cutsHistory->count() }}</h3>
                        <small class="text-dark d-block mt-1 font-weight-bold">
                            {{ $cutsHistory->where('cut_type', 'corte_z')->count() }} Cortes Z | {{ $cutsHistory->where('cut_type', 'pre_corte')->count() }} Pre-Cortes X
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Totales informativos del periodo consultado --}}
        <div class="card shadow-sm mb-4 border-secondary bg-light">
            <div class="card-body py-2 px-3">
                <div class="row align-items-center text-center text-md-left">
                    <div class="col-md-3 my-1">
                        <span class="text-muted small d-block">Transferencias:</span>
                        <strong class="text-primary font-size-15">${{ number_format($cheque, 2) }}</strong>
                    </div>
                    <div class="col-md-3 my-1">
                        <span class="text-muted small d-block">Pagos con Tarjeta:</span>
                        <strong class="text-info font-size-15">${{ number_format($due, 2) }}</strong>
                    </div>
                    <div class="col-md-3 my-1">
                        <span class="text-muted small d-block">Efectivo Físico Declarado (Cortes Z):</span>
                        <strong class="text-dark font-size-15">${{ number_format($cutsHistory->where('cut_type', 'corte_z')->sum('declared_cash'), 2) }}</strong>
                    </div>
                    <div class="col-md-3 my-1 text-md-right">
                        <span class="text-muted small d-block">Diferencia Acumulada en Cortes:</span>
                        @php $totDiff = (float)$cutsHistory->where('cut_type', 'corte_z')->sum('difference_amount'); @endphp
                        <strong class="font-size-15 {{ $totDiff < 0 ? 'text-danger' : ($totDiff > 0 ? 'text-warning' : 'text-success') }}">
                            ${{ number_format($totDiff, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- TABLA DE CORTES DE CAJA REGISTRADOS (CORTES Z Y ARQUEOS X) --}}
    {{-- ======================================================== --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap py-2">
            <div>
                <h6 class="mb-0 font-weight-bold text-white">
                    <i class="las la-receipt mr-1"></i> Cortes Registrados
                    @if($filterType === 'dia')
                        del Día ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                    @elseif($filterType === 'rango')
                        del Periodo ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                    @else
                        (Todos los Registros Históricos)
                    @endif
                </h6>
                <small class="text-white-50">
                    Total: <strong>{{ $cutsHistory->count() }}</strong> cortes encontrados
                    ({{ $cutsHistory->where('cut_type', 'corte_z')->count() }} Cortes Z, {{ $cutsHistory->where('cut_type', 'pre_corte')->count() }} Pre-Cortes X)
                </small>
            </div>
            <div>
                <a href="{{ route('cash.printCut', $printParams) }}"
                   target="_blank"
                   class="btn btn-sm btn-light font-weight-bold shadow-sm">
                    <i class="las la-print mr-1"></i> Imprimir Reporte de Cortes
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($cutsHistory->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped mb-0 font-size-13">
                        <thead class="thead-light font-weight-bold text-dark text-center">
                            <tr>
                                <th style="width: 70px;">Folio</th>
                                <th style="width: 130px;">Tipo de Corte</th>
                                <th style="width: 100px;">Fecha Op.</th>
                                <th style="width: 90px;">Hora Cierre</th>
                                <th>Sucursal</th>
                                <th>Cajero / Usuario</th>
                                <th class="text-right">Fondo Inicial</th>
                                <th class="text-right text-success">Ingresos Tot.</th>
                                <th class="text-right text-danger">Egresos Efec.</th>
                                <th class="text-right font-weight-bold">Efectivo Físico</th>
                                <th class="text-center">Cuadre / Dif.</th>
                                <th style="width: 90px;" class="text-center">Modo</th>
                                <th style="width: 120px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cutsHistory as $cut)
                                @php
                                    $diff = (float)($cut->difference_amount ?? 0);
                                    $isZ = $cut->cut_type === 'corte_z';
                                @endphp
                                <tr class="align-middle">
                                    <td class="text-center font-weight-bold text-dark align-middle">
                                        #{{ $cut->id }}
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($isZ)
                                            <span class="badge badge-success px-2 py-1 font-size-11">
                                                <i class="las la-check-double mr-1"></i>Corte Z (Final)
                                            </span>
                                        @else
                                            <span class="badge badge-info px-2 py-1 font-size-11">
                                                <i class="las la-clock mr-1"></i>Pre-Corte X
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center font-weight-bold align-middle">
                                        {{ $cut->date ? \Carbon\Carbon::parse($cut->date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="text-center text-muted align-middle">
                                        {{ optional($cut->closed_at ?? $cut->created_at)->format('H:i') }} hrs
                                    </td>
                                    <td class="align-middle font-size-12">
                                        {{ $cut->branch->name ?? 'Sucursal' }}
                                    </td>
                                    <td class="align-middle font-size-12">
                                        <div class="font-weight-bold text-dark">{{ $cut->user->name ?? 'Automático' }}</div>
                                        @if($cut->notes)
                                            <small class="text-muted d-block font-italic"><i class="las la-comment-alt mr-1"></i>{{ $cut->notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right align-middle text-muted">
                                        ${{ number_format($cut->opening_balance ?? 0, 2) }}
                                    </td>
                                    <td class="text-right align-middle font-weight-bold text-success">
                                        ${{ number_format($cut->total_income ?? 0, 2) }}
                                        <div class="font-size-10 text-muted font-weight-normal">
                                            Efec: ${{ number_format($cut->cash_income ?? 0, 2) }}
                                        </div>
                                    </td>
                                    <td class="text-right align-middle text-danger font-weight-bold">
                                        ${{ number_format($cut->total_expense ?? 0, 2) }}
                                    </td>
                                    <td class="text-right align-middle font-weight-bold text-dark font-size-14">
                                        @if($cut->declared_cash !== null)
                                            ${{ number_format($cut->declared_cash, 2) }}
                                        @else
                                            <span class="text-muted font-size-11">N/D</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($cut->declared_cash !== null)
                                            @if(round($diff, 2) == 0.0)
                                                <span class="badge badge-success font-size-11">Cuadrado ($0.00)</span>
                                            @elseif($diff < 0)
                                                <span class="badge badge-danger font-size-11">-${{ number_format(abs($diff), 2) }}</span>
                                            @else
                                                <span class="badge badge-warning font-size-11">+${{ number_format($diff, 2) }}</span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary font-size-11">Parcial</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light border text-uppercase font-size-10">
                                            {{ $cut->cut_mode ?? 'manual' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <a href="{{ route('cash.printCut', ['cut_id' => $cut->id, 'branch_id' => $branchId]) }}"
                                           target="_blank"
                                           class="btn btn-xs btn-outline-dark font-weight-bold px-2 py-1 shadow-sm"
                                           title="Imprimir ticket individual de este corte">
                                            <i class="las la-print mr-1"></i>Imprimir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="las la-inbox fa-2x mb-2 d-block text-secondary" style="opacity: 0.4;"></i>
                    No se encontraron cortes de caja registrados para este filtro.
                </div>
            @endif
        </div>
    </div>

    {{-- Resumen por Módulo Operativo del Día --}}
    @if(isset($moduleBreakdown) && $moduleBreakdown->count())
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 font-weight-bold"><i class="las la-cubes text-primary"></i> Resumen por Módulo Operativo del Día</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Módulo</th>
                                <th class="text-center"># Movimientos</th>
                                <th class="text-right text-success">Ingresos ($)</th>
                                <th class="text-right text-danger">Egresos ($)</th>
                                <th class="text-right font-weight-bold">Neto ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleBreakdown as $mb)
                                <tr>
                                    <td class="font-weight-bold">{{ $mb['module'] }}</td>
                                    <td class="text-center">{{ $mb['count'] }}</td>
                                    <td class="text-right text-success">${{ number_format($mb['income'], 2) }}</td>
                                    <td class="text-right text-danger">${{ number_format($mb['expense'], 2) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format($mb['income'] - $mb['expense'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- 3) BITÁCORA CRONOLÓGICA CON DETALLE POR OPERACIÓN DEL CLIENTE --}}
    <div class="card shadow-sm mb-4 border-dark">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">
                <i class="las la-stream mr-1"></i> 3) Bitácora Cronológica con Detalle por Operación del Cliente
            </h6>
            <span class="badge badge-light text-dark font-weight-bold">{{ count($chronologicalOperations) }} Operaciones</span>
        </div>
        <div class="card-body p-0">
            @if(count($chronologicalOperations) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">Hora</th>
                                <th style="width: 220px;">Cliente / Operación</th>
                                <th style="width: 90px;" class="text-center">Tipo</th>
                                <th style="width: 110px;" class="text-center">Método</th>
                                <th style="width: 130px;" class="text-right">Total Op. ($)</th>
                                <th>Detalle de Conceptos por Puntos</th>
                                <th style="width: 120px;">Módulo</th>
                                <th style="width: 110px;">Cajero</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chronologicalOperations as $op)
                                <tr>
                                    <td class="text-center align-middle font-weight-bold text-muted">{{ $op['time'] }}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-dark">{{ $op['customer_name'] }}</div>
                                        @if($op['reference'])
                                            <small class="text-muted d-block font-size-11"><code>{{ $op['reference'] }}</code></small>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-pill badge-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                            {{ $op['type'] === 'income' ? 'Ingreso' : 'Egreso' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-secondary text-uppercase">{{ $op['payment_method'] }}</span>
                                    </td>
                                    <td class="text-right align-middle font-weight-bold font-size-15 text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                        ${{ number_format($op['total_amount'], 2) }}
                                    </td>
                                    <td class="align-middle py-2">
                                        <ul class="mb-0 pl-3 list-unstyled">
                                            @foreach($op['concepts'] as $c)
                                                <li class="mb-1">
                                                    <i class="fa-solid fa-circle text-primary mr-1" style="font-size: 7px; vertical-align: middle;"></i>
                                                    <span class="text-dark">{{ $c['title'] }}</span>
                                                    <strong class="text-dark ml-1">— ${{ number_format($c['amount'], 2) }}</strong>
                                                    @if(!empty($c['commission']) && (float)$c['commission'] > 0 && ($c['section'] ?? '') !== 'prestamo_apertura')
                                                        <span class="badge badge-info font-size-10 ml-1">+ Com. ${{ number_format($c['commission'], 2) }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="align-middle text-muted small font-weight-bold">{{ $op['module'] }}</td>
                                    <td class="align-middle text-muted small">{{ $op['user_name'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $cashFlows->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-receipt fa-2x mb-2 d-block text-secondary" style="opacity: 0.4;"></i>
                    No se registraron movimientos en esta fecha.
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal de Configuración de Cierre Automático --}}
@if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches') || auth()->user()->can('cash.edit'))
<div class="modal fade" id="autoCutConfigModal" tabindex="-1" role="dialog" aria-labelledby="autoCutConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="autoCutConfigModalLabel">
                    <i class="las la-cog mr-1"></i> Configuración de Cierre Automático Diario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('cash.autoCutConfig') }}">
                @csrf
                <div class="modal-body">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                        <div class="form-group mb-3">
                            <label class="font-weight-bold font-size-14 text-dark">
                                <i class="las la-store mr-1 text-primary"></i> Seleccionar Sucursal a Configurar:
                            </label>
                            <select name="branch_id" id="modalBranchSelect" class="form-control font-weight-bold">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" 
                                            data-enabled="{{ $b->auto_cut_enabled ? '1' : '0' }}" 
                                            data-time="{{ $b->auto_cut_time ?? '22:00' }}"
                                            {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                        {{ $b->name }} — [{{ $b->auto_cut_enabled ? 'Automático: ' . ($b->auto_cut_time ?? '22:00') : 'Manual' }}]
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Como administrador con acceso total multisucursal, puedes configurar individualmente cada sucursal.</small>
                        </div>
                    @else
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="alert alert-info py-2 px-3 mb-3">
                            <i class="las la-store mr-1"></i> Sucursal Asignada: <strong>{{ $currentBranch->name ?? 'Mi Sucursal' }}</strong>
                        </div>
                    @endif

                    <div class="card border p-3 bg-light mb-3">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="autoCutSwitch" name="auto_cut_enabled" value="1" {{ ($currentBranch && $currentBranch->auto_cut_enabled) ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold text-dark" for="autoCutSwitch">
                                Habilitar Corte Z Automático Diario
                            </label>
                        </div>
                        <small class="text-muted d-block">
                            <i class="fa-solid fa-circle-info mr-1 text-info"></i>
                            <strong>Modo Manual:</strong> Si este switch está desactivado, la caja permanecerá abierta de forma indefinida hasta que un operador autorizado aplique el Corte Z manualmente.
                        </small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold font-size-13 text-dark">Hora de Corte Programado (HH:MM):</label>
                        <input type="time" name="auto_cut_time" id="autoCutTimeInput" class="form-control" value="{{ $currentBranch->auto_cut_time ?? '22:00' }}" required>
                        <small class="text-muted d-block mt-1">A esta hora programada, el planificador del sistema verificará si la caja sigue abierta y aplicará el Corte Z oficial de forma automática.</small>
                    </div>

                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="font-weight-bold text-dark font-size-13 mb-2">
                                <i class="las la-list mr-1"></i> Resumen de Modo de Corte en Todas las Sucursales
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0 text-center font-size-12">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-left">Sucursal</th>
                                            <th>Modo Actual</th>
                                            <th>Hora Programada</th>
                                            <th>Acción Rápida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($branches as $b)
                                            <tr class="{{ (int)$branchId === (int)$b->id ? 'table-primary font-weight-bold' : '' }}">
                                                <td class="text-left">{{ $b->name }}</td>
                                                <td>
                                                    @if($b->auto_cut_enabled)
                                                        <span class="badge badge-success"><i class="las la-clock mr-1"></i>Automático</span>
                                                    @else
                                                        <span class="badge badge-secondary"><i class="las la-hand-paper mr-1"></i>Manual</span>
                                                    @endif
                                                </td>
                                                <td>{{ $b->auto_cut_time ?? '22:00' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="selectBranchInModal('{{ $b->id }}', '{{ $b->auto_cut_enabled ? '1' : '0' }}', '{{ $b->auto_cut_time ?? '22:00' }}')">
                                                        Cargar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                        <i class="las la-save mr-1"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Alternar visualización de inputs según tipo de filtro (Día específico, Rango de fechas, Todos)
document.addEventListener('DOMContentLoaded', function () {
    const filterTypeSelect = document.getElementById('filterTypeSelect');
    const singleDateContainer = document.getElementById('singleDateContainer');
    const singleDateInput = document.getElementById('singleDateInput');
    const dateRangeContainer = document.getElementById('dateRangeContainer');
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    const allRecordsContainer = document.getElementById('allRecordsContainer');

    function updateFilterTypeView() {
        if (!filterTypeSelect) return;
        const val = filterTypeSelect.value;
        if (val === 'dia') {
            if (singleDateContainer) singleDateContainer.style.display = '';
            if (singleDateInput) singleDateInput.disabled = false;
            if (dateRangeContainer) dateRangeContainer.style.display = 'none';
            if (startDateInput) startDateInput.disabled = true;
            if (endDateInput) endDateInput.disabled = true;
            if (allRecordsContainer) allRecordsContainer.style.display = 'none';
        } else if (val === 'rango') {
            if (singleDateContainer) singleDateContainer.style.display = 'none';
            if (singleDateInput) singleDateInput.disabled = true;
            if (dateRangeContainer) dateRangeContainer.style.display = '';
            if (startDateInput) startDateInput.disabled = false;
            if (endDateInput) endDateInput.disabled = false;
            if (allRecordsContainer) allRecordsContainer.style.display = 'none';
        } else if (val === 'todos') {
            if (singleDateContainer) singleDateContainer.style.display = 'none';
            if (singleDateInput) singleDateInput.disabled = true;
            if (dateRangeContainer) dateRangeContainer.style.display = 'none';
            if (startDateInput) startDateInput.disabled = true;
            if (endDateInput) endDateInput.disabled = true;
            if (allRecordsContainer) allRecordsContainer.style.display = '';
        }
    }

    if (filterTypeSelect) {
        filterTypeSelect.addEventListener('change', updateFilterTypeView);
    }
});

// Apertura AUTOMÁTICA del cajón USB al enfocar el conteo de efectivo o al registrar corte
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        // Disparo automático de cajón USB al procesar corte con éxito
        if (window.ZyaPosHardware) {
            ZyaPosHardware.autoOpenUsbDrawer();
        }
    @endif

    // Abrir automáticamente el cajón USB cuando el usuario va a contar el efectivo físico en el arqueo
    document.querySelectorAll('input[name="declared_cash"], .denom-qty, #denom_coins').forEach(function (inp) {
        inp.addEventListener('focus', function () {
            if (window.ZyaPosHardware) {
                ZyaPosHardware.autoOpenUsbDrawer();
            }
        }, { once: true });
    });

    // Conteo interactivo de denominaciones en tiempo real
    const expectedCashValue = parseFloat('{{ $expectedCash }}') || 0;

    function recalculateDenominations() {
        let total = 0;
        let anyFilled = false;

        document.querySelectorAll('.denom-qty').forEach(function(inp) {
            const mult = parseFloat(inp.getAttribute('data-val')) || 0;
            const val = inp.value.trim();
            const qty = val !== '' ? (parseInt(val, 10) || 0) : 0;
            if (val !== '') anyFilled = true;
            const sub = qty * mult;
            total += sub;
            const subEl = document.getElementById('sub_' + mult);
            if (subEl) {
                subEl.textContent = '$' + sub.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        });

        const coinsInp = document.getElementById('denom_coins');
        if (coinsInp) {
            const cVal = coinsInp.value.trim();
            const coins = cVal !== '' ? (parseFloat(cVal) || 0) : 0;
            if (cVal !== '') anyFilled = true;
            total += coins;
            const coinsSubEl = document.getElementById('sub_coins');
            if (coinsSubEl) {
                coinsSubEl.textContent = '$' + coins.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }

        const declaredInp = document.getElementById('cut_declared_cash');
        if (declaredInp && anyFilled) {
            declaredInp.value = total.toFixed(2);
        }

        updateLiveDifference(total, anyFilled);
    }

    function updateLiveDifference(total, hasInput) {
        const liveDeclared = document.getElementById('liveDeclaredCash');
        if (liveDeclared) {
            liveDeclared.textContent = '$' + total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        const diff = Math.round((total - expectedCashValue) * 100) / 100;
        const diffBadge = document.getElementById('liveDifferenceBadge');
        if (!diffBadge) return;

        if (!hasInput && total === 0) {
            diffBadge.className = 'badge badge-secondary font-size-13 py-1 px-2';
            diffBadge.textContent = 'Pendiente de conteo';
            return;
        }

        if (Math.abs(diff) < 0.001) {
            diffBadge.className = 'badge badge-success font-size-13 py-1 px-2';
            diffBadge.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i>Caja Cuadrada ($0.00)';
        } else if (diff < 0) {
            diffBadge.className = 'badge badge-danger font-size-13 py-1 px-2';
            diffBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i>Faltante: -$' + Math.abs(diff).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            diffBadge.className = 'badge badge-warning font-size-13 py-1 px-2';
            diffBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i>Sobrante: +$' + diff.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    document.querySelectorAll('.denom-qty, #denom_coins').forEach(function(inp) {
        inp.addEventListener('input', recalculateDenominations);
    });

    const directDeclaredInp = document.getElementById('cut_declared_cash');
    if (directDeclaredInp) {
        directDeclaredInp.addEventListener('input', function() {
            const val = this.value.trim();
            const total = val !== '' ? (parseFloat(val) || 0) : 0;
            updateLiveDifference(total, val !== '');
        });
    }
});

function selectBranchInModal(branchId, isEnabled, cutTime) {
    const sel = document.getElementById('modalBranchSelect');
    if (sel) {
        sel.value = branchId;
    }
    const sw = document.getElementById('autoCutSwitch');
    const timeInp = document.getElementById('autoCutTimeInput');
    if (sw) sw.checked = (isEnabled === '1');
    if (timeInp) timeInp.value = cutTime;
}

const modalBranchSelect = document.getElementById('modalBranchSelect');
if (modalBranchSelect) {
    modalBranchSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const isEnabled = opt.getAttribute('data-enabled') === '1';
        const cutTime = opt.getAttribute('data-time') || '22:00';
        const sw = document.getElementById('autoCutSwitch');
        const timeInp = document.getElementById('autoCutTimeInput');
        if (sw) sw.checked = isEnabled;
        if (timeInp) timeInp.value = cutTime;
    });
}

const cutFormEl = document.getElementById('cutForm');
if (cutFormEl) {
    cutFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        const declaredInput = document.getElementById('cut_declared_cash');
        const declaredVal = declaredInput ? declaredInput.value.trim() : '';
        if (declaredVal === '' || isNaN(parseFloat(declaredVal)) || parseFloat(declaredVal) < 0) {
            Swal.fire({
                title: 'Efectivo físico requerido',
                text: 'Debes capturar el efectivo físico contado en cajón antes de cerrar el Corte Z.',
                icon: 'error',
                confirmButtonText: 'Entendido',
            });
            if (declaredInput) declaredInput.focus();
            return;
        }
        const formattedDeclared = parseFloat(declaredVal).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const expectedCashVal = parseFloat('{{ $expectedCash }}') || 0;
        const formattedExpected = expectedCashVal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const diffVal = Math.round((parseFloat(declaredVal) - expectedCashVal) * 100) / 100;
        let diffNotice = '';
        if (Math.abs(diffVal) < 0.001) {
            diffNotice = '<span class="text-success font-weight-bold">✓ Caja Cuadrada ($0.00)</span>';
        } else if (diffVal < 0) {
            diffNotice = `<span class="text-danger font-weight-bold">✗ Faltante: -$${Math.abs(diffVal).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
        } else {
            diffNotice = `<span class="text-warning font-weight-bold">⚠ Sobrante: +$${diffVal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
        }

        Swal.fire({
            title: '¿Confirmar Corte Final (Corte Z)?',
            html: `
                <div class="text-left small mb-3 p-2 bg-light border rounded">
                    <div>Efectivo Físico Declarado: <strong>$${formattedDeclared}</strong></div>
                    <div>Efectivo Esperado en Caja: <strong>$${formattedExpected}</strong></div>
                    <div class="mt-1">Resultado de Cuadre: ${diffNotice}</div>
                </div>
                <p class="mb-0 text-muted font-size-12">El efectivo físico declarado ($${formattedDeclared}) se registrará como el <strong>Saldo Inicial</strong> del día siguiente.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar Corte Z',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                if (window.ZyaPosHardware) {
                    ZyaPosHardware.autoOpenUsbDrawer();
                }
                e.target.submit();
            }
        });
    });
}

const preCutForm = document.querySelector('form[action="{{ route('cash.preCut') }}"]');
if (preCutForm) {
    preCutForm.addEventListener('submit', function () {
        if (window.ZyaPosHardware) {
            ZyaPosHardware.autoOpenUsbDrawer();
        }
    });
}
</script>
@endsection
