@extends('dashboard.body.main')

@section('container')
<div class="container my-4" style="max-width: 980px;">
    <script>
        window.onload = () => window.print();
    </script>

    @if($isMultiCutReport ?? false)
        {{-- ========================================================= --}}
        {{-- REPORTE CONSOLIDADO DE CORTES (RANGO O TODOS LOS REGISTROS) --}}
        {{-- ========================================================= --}}
        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white text-center py-3">
                <h3 class="mb-1 font-weight-bold tracking-wide">
                    @if($filterType === 'rango')
                        REPORTE CONSOLIDADO DE CORTES DE CAJA
                    @else
                        REPORTE HISTÓRICO GENERAL DE CORTES DE CAJA
                    @endif
                </h3>
                <div class="font-size-13 text-light">
                    @if($filterType === 'rango')
                        <span><strong>Período:</strong> Del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
                    @else
                        <span><strong>Alcance:</strong> Todos los registros históricos</span>
                    @endif
                    <span class="mx-2">|</span>
                    <span><strong>Sucursal:</strong> {{ $branch->name ?? 'Principal' }}</span>
                    <span class="mx-2">|</span>
                    <span><strong>Total Cortes:</strong> {{ $cutsCount }}</span>
                    <span class="mx-2">|</span>
                    <span><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="card-body p-4">
                {{-- 1) RESUMEN FINANCIERO DEL PERIODO --}}
                <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                    <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">1</span>
                    <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Resumen Financiero del Período</h5>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="p-3 border rounded bg-light text-center h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold">Total Ingresos</small>
                            <h4 class="font-weight-bold text-success mb-1">${{ number_format($totalIncome, 2) }}</h4>
                            <div class="font-size-11 text-muted">
                                Efec: ${{ number_format($cashIncome, 2) }}<br>
                                Tarj: ${{ number_format($cardIncome, 2) }} | Trans: ${{ number_format($transferIncome, 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-2">
                        <div class="p-3 border rounded bg-light text-center h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold">Total Egresos Efec.</small>
                            <h4 class="font-weight-bold text-danger mb-1">${{ number_format($totalExpense, 2) }}</h4>
                            <small class="text-muted font-size-11">Salidas en efectivo</small>
                        </div>
                    </div>

                    <div class="col-md-3 mb-2">
                        <div class="p-3 border rounded bg-light text-center h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold">Efectivo Físico Declarado</small>
                            <h4 class="font-weight-bold text-dark mb-1">${{ number_format($totalDeclaredCash, 2) }}</h4>
                            <small class="text-muted font-size-11">En Cortes Z cerrados</small>
                        </div>
                    </div>

                    <div class="col-md-3 mb-2">
                        <div class="p-3 border rounded bg-light text-center h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold">Diferencia Acumulada</small>
                            @if(round($totalDifference, 2) == 0.0)
                                <h4 class="font-weight-bold text-success mb-1">$0.00</h4>
                                <span class="badge badge-success font-size-11">Cuadre Exacto</span>
                            @elseif($totalDifference < 0)
                                <h4 class="font-weight-bold text-danger mb-1">-${{ number_format(abs($totalDifference), 2) }}</h4>
                                <span class="badge badge-danger font-size-11">Faltante Neto</span>
                            @else
                                <h4 class="font-weight-bold text-warning mb-1">+${{ number_format($totalDifference, 2) }}</h4>
                                <span class="badge badge-warning font-size-11">Sobrante Neto</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 2) RELACIÓN DETALLADA DE CORTES REGISTRADOS --}}
                <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                    <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">2</span>
                    <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Relación Detallada de Cortes (Z y X)</h5>
                </div>

                @if($cutsHistory->count() > 0)
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered table-striped font-size-12 mb-0">
                            <thead class="thead-dark text-center font-size-11">
                                <tr>
                                    <th style="width: 50px;">Folio</th>
                                    <th style="width: 100px;">Tipo</th>
                                    <th style="width: 80px;">Fecha</th>
                                    <th style="width: 70px;">Hora</th>
                                    <th>Cajero</th>
                                    <th class="text-right">Fondo Ini.</th>
                                    <th class="text-right">Total Ing.</th>
                                    <th class="text-right">Ing. Efec.</th>
                                    <th class="text-right">Egr. Efec.</th>
                                    <th class="text-right">Efec. Declarado</th>
                                    <th class="text-center">Diferencia</th>
                                    <th style="width: 70px;" class="text-center">Modo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cutsHistory as $cut)
                                    @php
                                        $diff = (float)($cut->difference_amount ?? 0);
                                        $isZ = $cut->cut_type === 'corte_z';
                                    @endphp
                                    <tr>
                                        <td class="text-center font-weight-bold">#{{ $cut->id }}</td>
                                        <td class="text-center">
                                            @if($isZ)
                                                <span class="badge badge-success font-size-10">Corte Z</span>
                                            @else
                                                <span class="badge badge-info font-size-10">Pre-Corte X</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $cut->date ? \Carbon\Carbon::parse($cut->date)->format('d/m/Y') : '-' }}</td>
                                        <td class="text-center text-muted">{{ optional($cut->closed_at ?? $cut->created_at)->format('H:i') }}</td>
                                        <td>{{ $cut->user->name ?? 'Automático' }}</td>
                                        <td class="text-right">${{ number_format($cut->opening_balance ?? 0, 2) }}</td>
                                        <td class="text-right font-weight-bold">${{ number_format($cut->total_income ?? 0, 2) }}</td>
                                        <td class="text-right text-success">${{ number_format($cut->cash_income ?? 0, 2) }}</td>
                                        <td class="text-right text-danger">${{ number_format($cut->total_expense ?? 0, 2) }}</td>
                                        <td class="text-right font-weight-bold">
                                            {{ $cut->declared_cash !== null ? '$' . number_format($cut->declared_cash, 2) : '-' }}
                                        </td>
                                        <td class="text-center">
                                            @if($cut->declared_cash !== null)
                                                @if(round($diff, 2) == 0.0)
                                                    <span class="text-success font-weight-bold">$0.00</span>
                                                @elseif($diff < 0)
                                                    <span class="text-danger font-weight-bold">-${{ number_format(abs($diff), 2) }}</span>
                                                @else
                                                    <span class="text-warning font-weight-bold">+${{ number_format($diff, 2) }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-uppercase font-size-10">{{ $cut->cut_mode ?? 'manual' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="thead-light font-weight-bold">
                                <tr>
                                    <th colspan="6" class="text-right">TOTALES ACUMULADOS:</th>
                                    <th class="text-right">${{ number_format($cutsHistory->sum('total_income'), 2) }}</th>
                                    <th class="text-right text-success">${{ number_format($cutsHistory->sum('cash_income'), 2) }}</th>
                                    <th class="text-right text-danger">${{ number_format($cutsHistory->sum('total_expense'), 2) }}</th>
                                    <th class="text-right">${{ number_format($totalDeclaredCash, 2) }}</th>
                                    <th class="text-center {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-warning' : 'text-success') }}">
                                        ${{ number_format($totalDifference, 2) }}
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="alert alert-light border text-center py-4 text-muted mb-4">
                        No se registraron cortes en el período seleccionado.
                    </div>
                @endif

                {{-- 3) OPERACIONES Y MOVIMIENTOS REGISTRADOS EN EL PERIODO --}}
                @if(count($chronologicalOperations) > 0)
                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                        <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">3</span>
                        <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Bitácora de Operaciones del Período ({{ count($chronologicalOperations) }})</h5>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered table-hover mb-0 font-size-11">
                            <thead class="thead-light text-uppercase">
                                <tr>
                                    <th style="width: 75px;" class="text-center">Hora</th>
                                    <th style="width: 180px;">Cliente / Operación</th>
                                    <th style="width: 70px;" class="text-center">Tipo</th>
                                    <th style="width: 90px;" class="text-center">Método</th>
                                    <th style="width: 100px;" class="text-right">Monto ($)</th>
                                    <th>Detalle de Conceptos</th>
                                    <th style="width: 100px;">Módulo</th>
                                    <th style="width: 90px;">Cajero</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chronologicalOperations as $op)
                                    <tr>
                                        <td class="text-center align-middle">{{ $op['time'] }}</td>
                                        <td class="align-middle font-weight-bold">{{ $op['customer_name'] }}</td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-pill badge-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                                {{ $op['type'] === 'income' ? 'Ingreso' : 'Egreso' }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle text-uppercase">{{ $op['payment_method'] }}</td>
                                        <td class="text-right align-middle font-weight-bold text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                            ${{ number_format($op['total_amount'], 2) }}
                                        </td>
                                        <td class="align-middle">
                                            <ul class="mb-0 pl-3 list-unstyled">
                                                @foreach($op['concepts'] as $c)
                                                    <li>
                                                        <i class="fa-solid fa-circle text-primary font-size-7 mr-1" style="font-size: 7px; vertical-align: middle;"></i>
                                                        {{ $c['title'] }} — <strong>${{ number_format($c['amount'], 2) }}</strong>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                        <td class="align-middle text-muted">{{ $op['module'] }}</td>
                                        <td class="align-middle text-muted">{{ $op['user_name'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card-footer text-muted text-center small py-2 bg-light font-size-11">
                <strong>ZYA Celulares</strong> | Reporte Consolidado de Cortes de Caja | Impreso por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong> el {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    @else
    @php
        $isPreCut = ($dailyCut->cut_type ?? 'corte_z') === 'pre_corte';
        $reportTitle = $isPreCut ? 'REPORTE DE PRE-CORTE (CORTE X)' : 'REPORTE DE CORTE FINAL DIARIO (CORTE Z)';
        $openingBalance = (float) ($dailyCut->opening_balance ?? 0);
        $cashIncome = (float) ($dailyCut->cash_income ?? 0);
        $totalExpense = (float) ($dailyCut->total_expense ?? 0);
        $cardIncome = (float) ($dailyCut->card_income ?? 0);
        $transferIncome = (float) ($dailyCut->transfer_income ?? 0);
        $totalIncome = (float) ($dailyCut->total_income ?? ($cashIncome + $cardIncome + $transferIncome));
        $expectedCash = $expectedCash ?? round($openingBalance + $cashIncome - $totalExpense, 2);
        $declaredCash = isset($dailyCut->declared_cash) && $dailyCut->declared_cash !== null ? (float) $dailyCut->declared_cash : null;
        $difference = $declaredCash !== null ? round($declaredCash - $expectedCash, 2) : null;
        $globalAccountingBalance = round($openingBalance + $totalIncome - $totalExpense, 2);
        $denoms = $dailyCut->denomination_breakdown ?? [];
    @endphp

    {{-- Tarjeta Principal del Reporte --}}
    <div class="card shadow-sm border-dark mb-4">
        <div class="card-header bg-dark text-white text-center py-3">
            <h3 class="mb-1 font-weight-bold tracking-wide">{{ $reportTitle }}</h3>
            <div class="font-size-13 text-light">
                <span><strong>Fecha Operativa:</strong> {{ \Carbon\Carbon::parse($dailyCut->date)->format('d/m/Y') }}</span>
                <span class="mx-2">|</span>
                <span><strong>Sucursal:</strong> {{ $dailyCut->branch->name ?? (auth()->user()->branch->name ?? 'Allende') }}</span>
                <span class="mx-2">|</span>
                <span><strong>Folio:</strong> #{{ $dailyCut->id ?? 'EN CURSO' }}</span>
                <span class="mx-2">|</span>
                <span><strong>Modo:</strong> {{ strtoupper($dailyCut->cut_mode ?? 'manual') }}</span>
            </div>
        </div>

        <div class="card-body p-4">
            {{-- 1) RESUMEN DE CAJA EN EL ORDEN EXACTO SOLICITADO --}}
            <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">1</span>
                <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Resumen de Caja (Conciliación de Efectivo)</h5>
            </div>
            <p class="text-muted font-size-12 mb-3">
                Cálculo de flujo de efectivo en caja física y conciliación operativa de métodos bancarios.
            </p>

            <div class="row mb-4">
                {{-- Columna Izquierda: Efectivo Esperado en Caja --}}
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="p-3 rounded border bg-light h-100">
                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2 font-size-13 text-uppercase">
                            <i class="fa-solid fa-cash-register text-primary mr-1"></i>Efectivo en Caja Física
                        </h6>
                        <table class="table table-sm table-borderless mb-0 font-size-13">
                            <tbody>
                                <tr>
                                    <td class="text-muted pl-0">1. Saldo inicial o fondo de caja <small class="d-block text-muted">(dejado noche anterior)</small></td>
                                    <td class="text-right font-weight-bold text-dark pr-0">${{ number_format($openingBalance, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-success pl-0">(+) Total de ingresos en efectivo <small class="d-block text-muted">(cobrado hoy en caja)</small></td>
                                    <td class="text-right font-weight-bold text-success pr-0">${{ number_format($cashIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-danger pl-0">(−) Total de egresos en efectivo <small class="d-block text-muted">(gastos y retiros de caja)</small></td>
                                    <td class="text-right font-weight-bold text-danger pr-0">${{ number_format($totalExpense, 2) }}</td>
                                </tr>
                                <tr class="border-top" style="border-top: 2px solid #333 !important;">
                                    <th class="pl-0 text-dark font-size-14 align-middle">
                                        (=) TOTAL DE EFECTIVO ESPERADO EN CAJA
                                        <small class="d-block text-muted font-weight-normal font-size-11">
                                            Fórmula: Saldo Inicial + Ingresos Efectivo − Egresos Efectivo
                                        </small>
                                    </th>
                                    <th class="text-right pr-0 font-size-18 text-primary align-middle">
                                        ${{ number_format($expectedCash, 2) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Columna Derecha: Otros Métodos (FUERA del cálculo de efectivo esperado) --}}
                <div class="col-md-6">
                    <div class="p-3 rounded border bg-white h-100 shadow-2xs">
                        <h6 class="font-weight-bold text-secondary border-bottom pb-2 mb-2 font-size-13 text-uppercase">
                            <i class="fa-solid fa-credit-card text-info mr-1"></i>Otros Métodos y Saldos <span class="badge badge-light border text-muted">Fuera de Efectivo</span>
                        </h6>
                        <table class="table table-sm table-borderless mb-0 font-size-13">
                            <tbody>
                                <tr>
                                    <td class="text-muted pl-0">• Total de transferencias bancarias:</td>
                                    <td class="text-right font-weight-bold text-dark pr-0">${{ number_format($transferIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted pl-0">• Pagos con tarjeta (TDD / TDC):</td>
                                    <td class="text-right font-weight-bold text-dark pr-0">${{ number_format($cardIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-warning pl-0">• Notas pendientes de pago <small class="text-muted font-weight-normal">(saldo pendiente por cobrar)</small>:</td>
                                    <td class="text-right font-weight-bold text-warning pr-0">${{ number_format($pendingDueTotal ?? 0, 2) }}</td>
                                </tr>
                                <tr class="border-top pt-2">
                                    <td class="pl-0 text-muted font-weight-bold">Total Ingresos Globales (Todos los métodos):</td>
                                    <td class="text-right font-weight-bold text-success pr-0 font-size-14">${{ number_format($totalIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 text-muted font-weight-bold">Saldo Final Contable en Caja (Global):</td>
                                    <td class="text-right font-weight-bold text-dark pr-0 font-size-14">${{ number_format($globalAccountingBalance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 2) CONTEO FÍSICO POR DENOMINACIÓN Y RESULTADO DE CUADRE --}}
            <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">2</span>
                <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Conteo Físico por Denominación y Cuadre de Caja</h5>
            </div>
            <p class="text-muted font-size-12 mb-3">
                Arqueo físico de billetes y monedas declarado en el cierre y comparación contra el efectivo esperado en caja.
            </p>

            <div class="row mb-4">
                <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                    <table class="table table-sm table-bordered mb-0 text-center font-size-12">
                        <thead class="bg-light text-uppercase">
                            <tr>
                                <th>Denominación</th>
                                <th style="width: 100px;">Billetes / Monedas</th>
                                <th class="text-right" style="width: 120px;">Importe ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $1,000</td>
                                <td>{{ (int) ($denoms['b1000'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b1000'] ?? 0)) * 1000, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $500</td>
                                <td>{{ (int) ($denoms['b500'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b500'] ?? 0)) * 500, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $200</td>
                                <td>{{ (int) ($denoms['b200'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b200'] ?? 0)) * 200, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $100</td>
                                <td>{{ (int) ($denoms['b100'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b100'] ?? 0)) * 100, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $50</td>
                                <td>{{ (int) ($denoms['b50'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b50'] ?? 0)) * 50, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Billetes de $20</td>
                                <td>{{ (int) ($denoms['b20'] ?? 0) }} pzas</td>
                                <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b20'] ?? 0)) * 20, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-left pl-3">Monedas / Morralla</td>
                                <td>Importe directo</td>
                                <td class="text-right font-weight-bold">${{ number_format((float)($denoms['coins'] ?? 0), 2) }}</td>
                            </tr>
                            <tr class="table-secondary font-weight-bold font-size-13">
                                <td colspan="2" class="text-right text-uppercase">Total Efectivo Físico Contado:</td>
                                <td class="text-right text-success font-size-14">${{ number_format($declaredCash ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Tarjeta de Resultado de Cuadre --}}
                <div class="col-lg-5 col-md-12">
                    <div class="p-3 rounded border h-100 d-flex flex-column justify-content-between" style="background: #fafafa;">
                        <div>
                            <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-2 font-size-13 text-uppercase">
                                <i class="fa-solid fa-scale-balanced mr-1"></i>Resultado de Conciliación
                            </h6>
                            <div class="d-flex justify-content-between font-size-13 mb-2">
                                <span class="text-muted">Efectivo Físico Declarado:</span>
                                <strong class="text-dark">${{ $declaredCash !== null ? number_format($declaredCash, 2) : '0.00' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between font-size-13 mb-3">
                                <span class="text-muted">Efectivo Esperado en Caja:</span>
                                <strong class="text-primary">${{ number_format($expectedCash, 2) }}</strong>
                            </div>
                        </div>

                        <div>
                            @if($declaredCash === null)
                                <div class="alert alert-secondary py-2 px-3 mb-2 text-center font-weight-bold font-size-13">
                                    <i class="fa-solid fa-clock mr-1"></i>Arqueo físico pendiente de captura
                                </div>
                            @elseif(abs($difference) < 0.01)
                                <div class="alert alert-success py-2 px-3 mb-2 text-center font-weight-bold font-size-14 border-success">
                                    <i class="fa-solid fa-circle-check mr-1"></i>CAJA CUADRADA ($0.00)
                                </div>
                            @elseif($difference > 0)
                                <div class="alert alert-info py-2 px-3 mb-2 text-center font-weight-bold font-size-14 border-info">
                                    <i class="fa-solid fa-arrow-trend-up mr-1"></i>SOBRANTE: +${{ number_format($difference, 2) }}
                                </div>
                            @else
                                <div class="alert alert-danger py-2 px-3 mb-2 text-center font-weight-bold font-size-14 border-danger">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>FALTANTE: -${{ number_format(abs($difference), 2) }}
                                </div>
                            @endif

                            @if((float)($dailyCut->withdraw_amount ?? 0) > 0)
                                <div class="small text-muted text-center mt-2 border-top pt-2">
                                    Retiro de Efectivo al Cierre: <strong>${{ number_format($dailyCut->withdraw_amount, 2) }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3) BITÁCORA CRONOLÓGICA CON DETALLE POR OPERACIÓN DEL CLIENTE --}}
            <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                <span class="badge badge-dark font-size-13 px-2 py-1 mr-2">3</span>
                <h5 class="mb-0 font-weight-bold text-dark text-uppercase">Bitácora Cronológica con Detalle por Operación del Cliente</h5>
            </div>
            <p class="text-muted font-size-12 mb-3">
                Agrupación cronológica por visita/operación con total cobrado y desglose de cada concepto en puntos independientes con su importe respectivo.
            </p>

            @if(count($chronologicalOperations) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0 font-size-12">
                        <thead class="thead-light text-uppercase font-size-11">
                            <tr>
                                <th style="width: 60px;" class="text-center">Hora</th>
                                <th style="width: 170px;">Cliente / Operación</th>
                                <th style="width: 80px;" class="text-center">Tipo</th>
                                <th style="width: 90px;" class="text-center">Método</th>
                                <th style="width: 100px;" class="text-right">Total Op. ($)</th>
                                <th>Detalle de Conceptos por Puntos</th>
                                <th style="width: 110px;">Módulo</th>
                                <th style="width: 100px;">Cajero</th>
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
                                    <td class="text-center align-middle font-weight-bold text-uppercase font-size-11">
                                        {{ $op['payment_method'] }}
                                    </td>
                                    <td class="text-right align-middle font-weight-bold font-size-13 text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                        ${{ number_format($op['total_amount'], 2) }}
                                    </td>
                                    <td class="align-middle py-2">
                                        <ul class="mb-0 pl-3 list-unstyled">
                                            @foreach($op['concepts'] as $c)
                                                <li class="mb-1">
                                                    <i class="fa-solid fa-circle text-primary font-size-7 mr-1" style="font-size: 7px; vertical-align: middle;"></i>
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
            @else
                <div class="alert alert-light border text-center py-4 text-muted">
                    <i class="fa-solid fa-receipt fa-2x mb-2 d-block" style="opacity: 0.35;"></i>
                    No se registraron movimientos en esta fecha.
                </div>
            @endif
        </div>

        <div class="card-footer text-muted text-center small py-2 bg-light font-size-11">
            <strong>ZYA Celulares</strong> | Reporte Z Oficial de Conciliación Diaria | Impreso por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong> el {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
    @endif
</div>
@endsection
