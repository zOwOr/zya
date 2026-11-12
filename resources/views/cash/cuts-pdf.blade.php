<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cortes Z</title>
    <style>
        @page { margin: 10mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.8px; color: #1e293b; line-height: 1.25; }
        h2 { margin: 0 0 3px 0; font-size: 13px; color: #0f172a; }
        .subtitle { font-size: 8.5px; color: #475569; margin-bottom: 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; }
        .summary-box { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 8px; margin-bottom: 8px; border-radius: 3px; }
        .section-title { font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #1e3a8a; margin: 9px 0 4px 0; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 3.5px 4px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; font-size: 8px; text-transform: uppercase; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-row td, .totals-row th { background: #eff6ff; font-weight: bold; color: #0f172a; border-top: 1.5px solid #1e3a8a; }
        .badge-z { background: #dcfce7; color: #166534; padding: 1px 4px; border-radius: 3px; font-weight: bold; font-size: 7.5px; }
        .badge-x { background: #e0f2fe; color: #075985; padding: 1px 4px; border-radius: 3px; font-weight: bold; font-size: 7.5px; }
    </style>
</head>
<body>
    <h2>ZYA CELULARES - REPORTE DE CORTES DE CAJA (CORTES Z / X)</h2>
    <div class="subtitle">
        <strong>Sucursal(es):</strong> {{ $branchLabel }} &nbsp;|&nbsp;
        @if($filterType === 'dia')
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
        @elseif($filterType === 'rango')
            <strong>Período:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        @else
            <strong>Alcance:</strong> Todos los registros
        @endif
        &nbsp;|&nbsp; <strong>Tipo:</strong> {{ $onlyZ ? 'Solo Cortes Z' : 'Cortes Z y Pre-Cortes X' }}
        &nbsp;|&nbsp; <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name ?? 'Sistema' }}
    </div>

    <div class="summary-box">
        <strong>Total Cortes:</strong> {{ $cuts->count() }} &nbsp;|&nbsp;
        <strong>Ingresos Totales:</strong> ${{ number_format($totals['total_income'], 2) }} &nbsp;|&nbsp;
        <strong>Efec. Caja:</strong> ${{ number_format($totals['cash_income'], 2) }} &nbsp;|&nbsp;
        <strong>Tarjeta:</strong> ${{ number_format($totals['card_income'], 2) }} &nbsp;|&nbsp;
        <strong>Transf.:</strong> ${{ number_format($totals['transfer_income'], 2) }} &nbsp;|&nbsp;
        <strong>Egresos Efec.:</strong> ${{ number_format($totals['total_expense'], 2) }} &nbsp;|&nbsp;
        <strong>Efec. Declarado (Z):</strong> ${{ number_format($totals['declared_cash'], 2) }}
    </div>

    <div class="section-title">Relación Detallada de Cortes</div>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:5%;">Folio</th>
                <th class="text-center" style="width:7%;">Tipo</th>
                <th style="width:12%;">Sucursal</th>
                <th class="text-center" style="width:8%;">Fecha</th>
                <th class="text-center" style="width:6%;">Hora</th>
                <th style="width:12%;">Cajero</th>
                <th class="text-right" style="width:8%;">Fondo Ini.</th>
                <th class="text-right" style="width:9%;">Total Ing.</th>
                <th class="text-right" style="width:9%;">Ing. Efec.</th>
                <th class="text-right" style="width:8%;">Egr. Efec.</th>
                <th class="text-right" style="width:8%;">Declarado</th>
                <th class="text-right" style="width:8%;">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cuts as $cut)
                @php
                    $isZ = $cut->cut_type === 'corte_z';
                    $diff = (float) ($cut->difference_amount ?? 0);
                @endphp
                <tr>
                    <td class="text-center"><strong>#{{ $cut->id }}</strong></td>
                    <td class="text-center">
                        <span class="{{ $isZ ? 'badge-z' : 'badge-x' }}">{{ $isZ ? 'Corte Z' : 'Pre-Corte X' }}</span>
                    </td>
                    <td><strong>{{ $cut->branch?->name ?? 'Sucursal' }}</strong></td>
                    <td class="text-center">{{ $cut->date ? \Carbon\Carbon::parse($cut->date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ optional($cut->closed_at ?? $cut->created_at)->format('H:i') }}</td>
                    <td>{{ $cut->user?->name ?? 'Automático' }}</td>
                    <td class="text-right">${{ number_format((float) ($cut->opening_balance ?? 0), 2) }}</td>
                    <td class="text-right"><strong>${{ number_format((float) ($cut->total_income ?? 0), 2) }}</strong></td>
                    <td class="text-right">${{ number_format((float) ($cut->cash_income ?? 0), 2) }}</td>
                    <td class="text-right">${{ number_format((float) ($cut->total_expense ?? 0), 2) }}</td>
                    <td class="text-right">{{ $cut->declared_cash !== null ? '$' . number_format((float) $cut->declared_cash, 2) : '-' }}</td>
                    <td class="text-right">{{ $cut->declared_cash !== null ? (($diff >= 0 ? ($diff > 0 ? '+$' : '$') : '-$') . number_format(abs($diff), 2)) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 12px;">No se encontraron cortes de caja para los filtros seleccionados.</td>
                </tr>
            @endforelse
            @if($cuts->count() > 0)
                <tr class="totals-row">
                    <td colspan="7" class="text-right">TOTALES ACUMULADOS:</td>
                    <td class="text-right">${{ number_format($totals['total_income'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['cash_income'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['total_expense'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['declared_cash'], 2) }}</td>
                    <td class="text-right">{{ ($totals['difference'] < 0 ? '-$' : '$') . number_format(abs($totals['difference']), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if(!empty($financieraSummary['items']))
        <div class="section-title">Resumen de Abonos a Financieras del Período (Solo Financieras con Movimientos)</div>
        <table>
            <thead>
                <tr>
                    <th>Financiera</th>
                    <th class="text-center">Operaciones</th>
                    <th class="text-right">Suma Abonos ($)</th>
                    <th class="text-right">Comisiones ($)</th>
                    <th class="text-right">Total Cobrado ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($financieraSummary['items'] as $finRow)
                    <tr>
                        <td><strong>{{ $finRow['name'] }}</strong></td>
                        <td class="text-center">{{ $finRow['count'] }}</td>
                        <td class="text-right">${{ number_format($finRow['amount'], 2) }}</td>
                        <td class="text-right">${{ number_format($finRow['commission'], 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($finRow['total'], 2) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td>TOTAL GENERAL DE ABONOS A FINANCIERAS</td>
                    <td class="text-center">{{ $financieraSummary['grand_count'] }}</td>
                    <td class="text-right">${{ number_format($financieraSummary['grand_amount'], 2) }}</td>
                    <td class="text-right">${{ number_format($financieraSummary['grand_commission'], 2) }}</td>
                    <td class="text-right">${{ number_format($financieraSummary['grand_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>

