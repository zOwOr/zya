@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    .cut-print-sheet {
        max-width: 960px;
        margin: 10px auto;
        font-size: 11px;
        line-height: 1.25;
        color: #0f172a;
    }
    .cut-section-head {
        display: flex;
        align-items: center;
        border-bottom: 1.5px solid #cbd5e1;
        padding-bottom: 2px;
        margin: 8px 0 5px 0;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        color: #0f172a;
    }
    .cut-section-num {
        display: inline-block;
        background: #0f172a;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 3px;
        margin-right: 6px;
    }
    .cut-compact-box {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 6px 8px;
        background: #fff;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .cut-compact-table th,
    .cut-compact-table td {
        padding: 3px 5px !important;
        font-size: 10.5px;
        vertical-align: middle !important;
    }
    .cut-dense-table th,
    .cut-dense-table td {
        padding: 2.5px 4px !important;
        font-size: 9.8px;
        line-height: 1.2;
        vertical-align: middle !important;
    }
    .cut-dense-table ul {
        margin: 0;
        padding-left: 0;
        list-style: none;
    }
    .cut-dense-table li {
        margin-bottom: 1px;
    }

    @page {
        size: letter portrait;
        margin: 7mm 8mm;
    }

    @media print {
        .iq-sidebar,
        .iq-top-navbar,
        .iq-footer,
        .no-print,
        .iq-menu-bt {
            display: none !important;
        }
        html,
        body,
        .wrapper,
        .content-page,
        .container,
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            color: #000 !important;
            min-height: 0 !important;
        }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-shadow: none !important;
        }
        .cut-print-sheet {
            max-width: 100% !important;
            margin: 0 !important;
            font-size: 9.5px !important;
            line-height: 1.2 !important;
        }
        .card {
            border: 1px solid #94a3b8 !important;
            margin-bottom: 0 !important;
        }
        .card-header.bg-dark {
            padding: 5px 8px !important;
            background: #1e293b !important;
        }
        .card-header h3 {
            font-size: 13px !important;
            margin-bottom: 2px !important;
        }
        .card-body {
            padding: 6px 8px !important;
        }
        .card-footer {
            padding: 4px 8px !important;
            font-size: 8.5px !important;
        }
        .cut-section-head {
            margin: 5px 0 3px 0 !important;
            font-size: 10px !important;
            break-after: avoid;
            page-break-after: avoid;
        }
        .cut-compact-box {
            padding: 4px 6px !important;
        }
        .cut-compact-table th,
        .cut-compact-table td {
            padding: 2px 4px !important;
            font-size: 9.2px !important;
        }
        .cut-dense-table th,
        .cut-dense-table td {
            padding: 2px 3px !important;
            font-size: 8.6px !important;
            line-height: 1.15 !important;
        }
        .cut-dense-table thead,
        .cut-compact-table thead {
            display: table-header-group;
        }
        .cut-dense-table tr,
        .cut-compact-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    }
</style>
@endsection

@section('container')
<div class="container cut-print-sheet">
    <script>
        window.onload = () => window.print();
    </script>

    <div class="no-print d-flex justify-content-between align-items-center mb-2 p-2 bg-light border rounded">
        <span class="font-size-12 text-muted font-weight-bold">
            <i class="las la-file-alt text-primary mr-1"></i> Vista previa optimizada para impresión compacta (Carta Vertical)
        </span>
        <div>
            <a href="{{ route('cash.dailyCut') }}" class="btn btn-sm btn-outline-secondary mr-1">
                <i class="las la-arrow-left"></i> Volver
            </a>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-dark font-weight-bold">
                <i class="las la-print mr-1"></i> Imprimir Ahora
            </button>
        </div>
    </div>

    @if($isMultiCutReport ?? false)
        {{-- ========================================================= --}}
        {{-- REPORTE CONSOLIDADO DE CORTES (RANGO O TODOS LOS REGISTROS) --}}
        {{-- ========================================================= --}}
        <div class="card border-dark">
            <div class="card-header bg-dark text-white text-center py-2">
                <h3 class="mb-1 font-weight-bold">
                    @if($filterType === 'rango')
                        REPORTE CONSOLIDADO DE CORTES DE CAJA
                    @elseif($filterType === 'dia')
                        REPORTE CONSOLIDADO DE CORTES DE CAJA DEL DÍA
                    @else
                        REPORTE HISTÓRICO GENERAL DE CORTES DE CAJA
                    @endif
                </h3>
                <div class="font-size-11 text-light">
                    @if($filterType === 'rango')
                        <span><strong>Período:</strong> Del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
                    @elseif($filterType === 'dia')
                        <span><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</span>
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

            <div class="card-body p-2">
                {{-- 1) RESUMEN FINANCIERO DEL PERIODO --}}
                <div class="cut-section-head">
                    <span class="cut-section-num">1</span> Resumen Financiero del Período
                </div>

                <div class="row no-gutters mb-2">
                    <div class="col-3 pr-1">
                        <div class="cut-compact-box text-center bg-light h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold" style="font-size:9px;">Total Ingresos</small>
                            <div class="font-weight-bold text-success" style="font-size:13px;">${{ number_format($totalIncome, 2) }}</div>
                            <div style="font-size:8.5px;" class="text-muted">Efec: ${{ number_format($cashIncome, 2) }} | Tarj: ${{ number_format($cardIncome, 2) }} | Trf: ${{ number_format($transferIncome, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-3 px-1">
                        <div class="cut-compact-box text-center bg-light h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold" style="font-size:9px;">Total Egresos Efec.</small>
                            <div class="font-weight-bold text-danger" style="font-size:13px;">${{ number_format($totalExpense, 2) }}</div>
                            <div style="font-size:8.5px;" class="text-muted">Salidas en efectivo</div>
                        </div>
                    </div>
                    <div class="col-3 px-1">
                        <div class="cut-compact-box text-center bg-light h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold" style="font-size:9px;">Efectivo Declarado</small>
                            <div class="font-weight-bold text-dark" style="font-size:13px;">${{ number_format($totalDeclaredCash, 2) }}</div>
                            <div style="font-size:8.5px;" class="text-muted">En Cortes Z cerrados</div>
                        </div>
                    </div>
                    <div class="col-3 pl-1">
                        <div class="cut-compact-box text-center bg-light h-100">
                            <small class="text-muted text-uppercase d-block font-weight-bold" style="font-size:9px;">Diferencia Acumulada</small>
                            @if(round($totalDifference, 2) == 0.0)
                                <div class="font-weight-bold text-success" style="font-size:13px;">$0.00 (Cuadre Exacto)</div>
                            @elseif($totalDifference < 0)
                                <div class="font-weight-bold text-danger" style="font-size:13px;">-${{ number_format(abs($totalDifference), 2) }} (Faltante)</div>
                            @else
                                <div class="font-weight-bold text-warning" style="font-size:13px;">+${{ number_format($totalDifference, 2) }} (Sobrante)</div>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($financieraSummary['items']))
                    <div class="cut-section-head">
                        <span class="cut-section-num">F</span> Resumen de Abonos a Financieras (Solo Financieras con Movimientos)
                    </div>
                    <table class="table table-bordered cut-compact-table mb-2">
                        <thead class="bg-light">
                            <tr>
                                <th>Financiera</th>
                                <th class="text-center">Abonos (#)</th>
                                <th class="text-right">Suma Abonos ($)</th>
                                <th class="text-right">Comisiones ($)</th>
                                <th class="text-right">Total Financiera ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($financieraSummary['items'] as $finRow)
                                <tr>
                                    <td class="font-weight-bold">{{ $finRow['name'] }}</td>
                                    <td class="text-center">{{ $finRow['count'] }}</td>
                                    <td class="text-right">${{ number_format($finRow['amount'], 2) }}</td>
                                    <td class="text-right">${{ number_format($finRow['commission'], 2) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format($finRow['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <th>TOTAL GENERAL DE ABONOS A FINANCIERAS</th>
                                <th class="text-center">{{ $financieraSummary['grand_count'] }}</th>
                                <th class="text-right">${{ number_format($financieraSummary['grand_amount'], 2) }}</th>
                                <th class="text-right">${{ number_format($financieraSummary['grand_commission'], 2) }}</th>
                                <th class="text-right text-primary">${{ number_format($financieraSummary['grand_total'], 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                @endif

                {{-- 2) RELACIÓN DETALLADA DE CORTES REGISTRADOS --}}
                <div class="cut-section-head">
                    <span class="cut-section-num">2</span> Relación Detallada de Cortes (Z y X)
                </div>

                @if($cutsHistory->count() > 0)
                    <table class="table table-bordered table-striped cut-dense-table mb-2">
                        <thead class="thead-dark text-center">
                            <tr>
                                <th>Folio</th>
                                <th>Tipo</th>
                                <th>Sucursal</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Cajero</th>
                                <th class="text-right">Fondo Ini.</th>
                                <th class="text-right">Total Ing.</th>
                                <th class="text-right">Ing. Efec.</th>
                                <th class="text-right">Egr. Efec.</th>
                                <th class="text-right">Declarado</th>
                                <th class="text-center">Dif.</th>
                                <th class="text-center">Modo</th>
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
                                    <td class="text-center">{{ $isZ ? 'Corte Z' : 'Pre-Corte X' }}</td>
                                    <td>{{ $cut->branch->name ?? 'Principal' }}</td>
                                    <td class="text-center">{{ $cut->date ? \Carbon\Carbon::parse($cut->date)->format('d/m/Y') : '-' }}</td>
                                    <td class="text-center">{{ optional($cut->closed_at ?? $cut->created_at)->format('H:i') }}</td>
                                    <td>{{ $cut->user->name ?? 'Automático' }}</td>
                                    <td class="text-right">${{ number_format($cut->opening_balance ?? 0, 2) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format($cut->total_income ?? 0, 2) }}</td>
                                    <td class="text-right text-success">${{ number_format($cut->cash_income ?? 0, 2) }}</td>
                                    <td class="text-right text-danger">${{ number_format($cut->total_expense ?? 0, 2) }}</td>
                                    <td class="text-right font-weight-bold">{{ $cut->declared_cash !== null ? '$' . number_format($cut->declared_cash, 2) : '-' }}</td>
                                    <td class="text-center font-weight-bold">
                                        @if($cut->declared_cash !== null)
                                            {{ $diff < 0 ? '-$' . number_format(abs($diff), 2) : ($diff > 0 ? '+$' . number_format($diff, 2) : '$0.00') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center text-uppercase">{{ $cut->cut_mode ?? 'manual' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="thead-light font-weight-bold">
                            <tr>
                                <th colspan="7" class="text-right">TOTALES ACUMULADOS:</th>
                                <th class="text-right">${{ number_format($cutsHistory->sum('total_income'), 2) }}</th>
                                <th class="text-right text-success">${{ number_format($cutsHistory->sum('cash_income'), 2) }}</th>
                                <th class="text-right text-danger">${{ number_format($cutsHistory->sum('total_expense'), 2) }}</th>
                                <th class="text-right">${{ number_format($totalDeclaredCash, 2) }}</th>
                                <th class="text-center">${{ number_format($totalDifference, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <div class="text-center py-2 text-muted border rounded mb-2">
                        No se registraron cortes en el período seleccionado.
                    </div>
                @endif

                {{-- 3) OPERACIONES Y MOVIMIENTOS REGISTRADOS EN EL PERIODO --}}
                @if(count($chronologicalOperations) > 0)
                    <div class="cut-section-head">
                        <span class="cut-section-num">3</span> Bitácora de Operaciones del Período ({{ count($chronologicalOperations) }})
                    </div>
                    <table class="table table-bordered table-hover cut-dense-table mb-0">
                        <thead class="thead-light text-uppercase">
                            <tr>
                                <th style="width: 45px;" class="text-center">Hora</th>
                                <th style="width: 145px;">Cliente / Operación</th>
                                <th style="width: 55px;" class="text-center">Tipo</th>
                                <th style="width: 72px;" class="text-center">Método</th>
                                <th style="width: 75px;" class="text-right">Monto ($)</th>
                                <th>Detalle de Conceptos</th>
                                <th style="width: 85px;">Módulo</th>
                                <th style="width: 80px;">Cajero</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chronologicalOperations as $op)
                                <tr>
                                    <td class="text-center">{{ $op['time'] }}</td>
                                    <td class="font-weight-bold">{{ $op['customer_name'] }}</td>
                                    <td class="text-center">{{ $op['type'] === 'income' ? 'Ingreso' : 'Egreso' }}</td>
                                    <td class="text-center text-uppercase">{{ $op['payment_method'] }}</td>
                                    <td class="text-right font-weight-bold text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">${{ number_format($op['total_amount'], 2) }}</td>
                                    <td>
                                        <ul>
                                            @foreach($op['concepts'] as $c)
                                                <li>• {{ $c['title'] }} — <strong>${{ number_format($c['amount'], 2) }}</strong></li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-muted">{{ $op['module'] }}</td>
                                    <td class="text-muted">{{ $op['user_name'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="card-footer text-muted text-center bg-light">
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

    {{-- Tarjeta Principal Compacta del Reporte Z / X --}}
    <div class="card border-dark">
        <div class="card-header bg-dark text-white text-center py-2">
            <h3 class="mb-1 font-weight-bold">{{ $reportTitle }}</h3>
            <div class="font-size-11 text-light">
                <span><strong>Fecha Operativa:</strong> {{ \Carbon\Carbon::parse($dailyCut->date)->format('d/m/Y') }}</span>
                <span class="mx-2">|</span>
                <span><strong>Sucursal:</strong> {{ $dailyCut->branch->name ?? (auth()->user()->branch->name ?? 'Allende') }}</span>
                <span class="mx-2">|</span>
                <span><strong>Folio:</strong> #{{ $dailyCut->id ?? 'EN CURSO' }}</span>
                <span class="mx-2">|</span>
                <span><strong>Modo:</strong> {{ strtoupper($dailyCut->cut_mode ?? 'manual') }}</span>
            </div>
        </div>

        <div class="card-body p-2">
            {{-- 1) RESUMEN COMPACTO DE CAJA (EFECTIVO VS OTROS MÉTODOS) --}}
            <div class="cut-section-head">
                <span class="cut-section-num">1</span> Resumen de Caja (Conciliación de Efectivo y Saldos)
            </div>

            <div class="row no-gutters mb-2">
                {{-- Izquierda: Flujo Físico de Caja --}}
                <div class="col-6 pr-1">
                    <div class="cut-compact-box bg-light h-100">
                        <div class="font-weight-bold text-uppercase border-bottom pb-1 mb-1" style="font-size: 9.8px;">
                            Efectivo en Caja Física
                        </div>
                        <table class="table table-borderless cut-compact-table mb-0">
                            <tbody>
                                <tr>
                                    <td class="pl-0 text-muted">1. Saldo inicial o fondo de caja</td>
                                    <td class="pr-0 text-right font-weight-bold">${{ number_format($openingBalance, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 text-success">(+) Ingresos en efectivo (caja)</td>
                                    <td class="pr-0 text-right font-weight-bold text-success">${{ number_format($cashIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 text-danger">(−) Egresos y gastos en efectivo</td>
                                    <td class="pr-0 text-right font-weight-bold text-danger">${{ number_format($totalExpense, 2) }}</td>
                                </tr>
                                <tr class="border-top" style="border-top: 1.5px solid #0f172a !important;">
                                    <th class="pl-0 text-dark">(=) EFECTIVO ESPERADO EN CAJA</th>
                                    <th class="pr-0 text-right text-primary" style="font-size: 12px;">${{ number_format($expectedCash, 2) }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Derecha: Otros Métodos y Saldo Final Global --}}
                <div class="col-6 pl-1">
                    <div class="cut-compact-box h-100">
                        <div class="font-weight-bold text-uppercase text-secondary border-bottom pb-1 mb-1" style="font-size: 9.8px;">
                            Otros Métodos y Saldo Contable
                        </div>
                        <table class="table table-borderless cut-compact-table mb-0">
                            <tbody>
                                <tr>
                                    <td class="pl-0 text-muted">• Transferencias bancarias:</td>
                                    <td class="pr-0 text-right font-weight-bold">${{ number_format($transferIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 text-muted">• Pagos con tarjeta (TDD / TDC):</td>
                                    <td class="pr-0 text-right font-weight-bold">${{ number_format($cardIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 text-warning">• Notas pendientes por cobrar:</td>
                                    <td class="pr-0 text-right font-weight-bold text-warning">${{ number_format($pendingDueTotal ?? 0, 2) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="pl-0 font-weight-bold text-muted">Ingresos Globales (Todos métodos):</td>
                                    <td class="pr-0 text-right font-weight-bold text-success">${{ number_format($totalIncome, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-0 font-weight-bold text-dark">Saldo Final Contable Global:</td>
                                    <td class="pr-0 text-right font-weight-bold text-dark">${{ number_format($globalAccountingBalance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 2) CONTEO FÍSICO COMPACTO (GRID 2 COLUMNAS) Y RESULTADO DE CONCILIACIÓN --}}
            <div class="cut-section-head">
                <span class="cut-section-num">2</span> Conteo Físico por Denominación y Resultado de Conciliación
            </div>

            <div class="row no-gutters mb-2">
                <div class="col-7 pr-1">
                    <div class="cut-compact-box p-0">
                        <table class="table table-bordered cut-compact-table mb-0 text-center">
                            <thead class="bg-light text-uppercase">
                                <tr>
                                    <th>Denom.</th>
                                    <th>Pzas</th>
                                    <th class="text-right">Importe</th>
                                    <th>Denom.</th>
                                    <th>Pzas</th>
                                    <th class="text-right">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold text-left">$1,000</td>
                                    <td>{{ (int) ($denoms['b1000'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b1000'] ?? 0)) * 1000, 2) }}</td>
                                    <td class="font-weight-bold text-left">$50</td>
                                    <td>{{ (int) ($denoms['b50'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b50'] ?? 0)) * 50, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-left">$500</td>
                                    <td>{{ (int) ($denoms['b500'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b500'] ?? 0)) * 500, 2) }}</td>
                                    <td class="font-weight-bold text-left">$20</td>
                                    <td>{{ (int) ($denoms['b20'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b20'] ?? 0)) * 20, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-left">$200</td>
                                    <td>{{ (int) ($denoms['b200'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b200'] ?? 0)) * 200, 2) }}</td>
                                    <td class="font-weight-bold text-left">Monedas</td>
                                    <td>-</td>
                                    <td class="text-right font-weight-bold">${{ number_format((float)($denoms['coins'] ?? 0), 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold text-left">$100</td>
                                    <td>{{ (int) ($denoms['b100'] ?? 0) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format(((int)($denoms['b100'] ?? 0)) * 100, 2) }}</td>
                                    <th colspan="2" class="text-right bg-light">TOTAL CONTADO:</th>
                                    <th class="text-right text-success bg-light">${{ number_format($declaredCash ?? 0, 2) }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-5 pl-1">
                    <div class="cut-compact-box bg-light h-100 d-flex flex-column justify-content-between">
                        <div class="font-weight-bold text-uppercase border-bottom pb-1 mb-1" style="font-size: 9.8px;">
                            Resultado de Conciliación
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Efectivo Físico Declarado:</span>
                            <strong>${{ $declaredCash !== null ? number_format($declaredCash, 2) : '0.00' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Efectivo Esperado en Caja:</span>
                            <strong class="text-primary">${{ number_format($expectedCash, 2) }}</strong>
                        </div>
                        <div class="mt-1">
                            @if($declaredCash === null)
                                <div class="alert alert-secondary py-1 px-2 mb-0 text-center font-weight-bold" style="font-size: 10px;">
                                    Arqueo físico pendiente de captura
                                </div>
                            @elseif(abs($difference) < 0.01)
                                <div class="alert alert-success py-1 px-2 mb-0 text-center font-weight-bold border-success" style="font-size: 10.5px;">
                                    CAJA CUADRADA ($0.00)
                                </div>
                            @elseif($difference > 0)
                                <div class="alert alert-info py-1 px-2 mb-0 text-center font-weight-bold border-info" style="font-size: 10.5px;">
                                    Sobrante: +${{ number_format($difference, 2) }}
                                </div>
                            @else
                                <div class="alert alert-danger py-1 px-2 mb-0 text-center font-weight-bold border-danger" style="font-size: 10.5px;">
                                    Faltante: -${{ number_format(abs($difference), 2) }}
                                </div>
                            @endif

                            @if((float)($dailyCut->withdraw_amount ?? 0) > 0)
                                <div class="text-muted text-center mt-1" style="font-size: 9px;">
                                    Retiro al Cierre: <strong>${{ number_format($dailyCut->withdraw_amount, 2) }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- RESUMEN DE ABONOS A FINANCIERAS (INCISO A - SOLO FINANCIERAS CON MOVIMIENTO) --}}
            @if(!empty($financieraSummary['items']))
                <div class="cut-section-head">
                    <span class="cut-section-num">F</span> Resumen de Abonos a Financieras (Solo Financieras con Movimientos)
                </div>
                <table class="table table-bordered cut-compact-table mb-2">
                    <thead class="bg-light text-uppercase">
                        <tr>
                            <th>Financiera</th>
                            <th class="text-center"># Abonos</th>
                            <th class="text-right">Abonos ($)</th>
                            <th class="text-right">Comisiones ($)</th>
                            <th class="text-right">Total Cobrado ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($financieraSummary['items'] as $finRow)
                            <tr>
                                <td class="font-weight-bold">{{ $finRow['name'] }}</td>
                                <td class="text-center">{{ $finRow['count'] }}</td>
                                <td class="text-right">${{ number_format($finRow['amount'], 2) }}</td>
                                <td class="text-right">${{ number_format($finRow['commission'], 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($finRow['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <th>TOTAL GENERAL DE ABONOS A FINANCIERAS</th>
                            <th class="text-center">{{ $financieraSummary['grand_count'] }}</th>
                            <th class="text-right">${{ number_format($financieraSummary['grand_amount'], 2) }}</th>
                            <th class="text-right">${{ number_format($financieraSummary['grand_commission'], 2) }}</th>
                            <th class="text-right text-primary">${{ number_format($financieraSummary['grand_total'], 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            @endif

            {{-- 3) BITÁCORA CRONOLÓGICA COMPACTA --}}
            <div class="cut-section-head">
                <span class="cut-section-num">3</span> Bitácora Cronológica con Detalle por Operación del Cliente ({{ count($chronologicalOperations) }})
            </div>

            @if(count($chronologicalOperations) > 0)
                <table class="table table-bordered table-hover cut-dense-table mb-0">
                    <thead class="thead-light text-uppercase">
                        <tr>
                            <th style="width: 42px;" class="text-center">Hora</th>
                            <th style="width: 145px;">Cliente / Operación</th>
                            <th style="width: 52px;" class="text-center">Tipo</th>
                            <th style="width: 72px;" class="text-center">Método</th>
                            <th style="width: 72px;" class="text-right">Total ($)</th>
                            <th>Detalle de Conceptos</th>
                            <th style="width: 82px;">Módulo</th>
                            <th style="width: 78px;">Cajero</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chronologicalOperations as $op)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $op['time'] }}</td>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $op['customer_name'] }}</div>
                                    @if($op['reference'])
                                        <small class="text-muted"><code>{{ $op['reference'] }}</code></small>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                    {{ $op['type'] === 'income' ? 'Ingreso' : 'Egreso' }}
                                </td>
                                <td class="text-center text-uppercase font-weight-bold">{{ $op['payment_method'] }}</td>
                                <td class="text-right font-weight-bold text-{{ $op['type'] === 'income' ? 'success' : 'danger' }}">
                                    ${{ number_format($op['total_amount'], 2) }}
                                </td>
                                <td>
                                    <ul>
                                        @foreach($op['concepts'] as $c)
                                            <li>
                                                • {{ $c['title'] }} <strong>— ${{ number_format($c['amount'], 2) }}</strong>
                                                @if(!empty($c['commission']) && (float)$c['commission'] > 0 && ($c['section'] ?? '') !== 'prestamo_apertura')
                                                    <span class="text-info">(+Com. ${{ number_format($c['commission'], 2) }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="text-muted">{{ $op['module'] }}</td>
                                <td class="text-muted">{{ $op['user_name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="border rounded text-center py-2 text-muted">
                    No se registraron movimientos en esta fecha.
                </div>
            @endif
        </div>

        <div class="card-footer text-muted text-center bg-light">
            <strong>ZYA Celulares</strong> | Reporte Z Oficial de Conciliación Diaria | Impreso por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong> el {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
    @endif
</div>
@endsection
