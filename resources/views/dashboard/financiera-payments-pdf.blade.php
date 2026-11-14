<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Abonos a Financieras</title>
    <style>
        @page { margin: 12mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.3; }
        h2 { margin: 0 0 3px 0; color: #0f172a; font-size: 14px; }
        .subtitle { margin-bottom: 10px; font-size: 9px; color: #475569; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
        .summary-box { margin-bottom: 10px; padding: 7px 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; }
        .group-header {
            background: #1e3a8a;
            color: #fff;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 10.5px;
            margin-top: 10px;
            border-radius: 3px 3px 0 0;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; font-size: 8.5px; text-transform: uppercase; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .subtotal-row td { background-color: #eff6ff; font-weight: bold; color: #1e3a8a; border-top: 1.5px solid #1e3a8a; }
        .grand-total-box {
            margin-top: 12px;
            padding: 8px 10px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 4px;
        }
        .grand-total-box table { margin: 0; }
        .grand-total-box td { border: none; color: #ffffff; padding: 2px 4px; font-size: 10.5px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #334155; margin: 8px 0 4px 0; }
    </style>
</head>
<body>
    <h2>ZYA CELULARES - REPORTE DIARIO DE ABONOS POR FINANCIERA</h2>
    <div class="subtitle">
        @if(!empty($finPeriodLabel))
            Período consultado: <strong>{{ $finPeriodLabel }}</strong>
        @elseif($finStartDate === $finEndDate)
            Fecha consultada: <strong>{{ \Carbon\Carbon::parse($finStartDate)->format('d/m/Y') }}</strong>
        @else
            Periodo consultado: <strong>{{ \Carbon\Carbon::parse($finStartDate)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($finEndDate)->format('d/m/Y') }}</strong>
        @endif
        &nbsp;|&nbsp; Sucursal: <strong>{{ $branchName ?? 'Todas las Sucursales' }}</strong>
        &nbsp;|&nbsp; Financiera(s): <strong>{{ $financieraFilterLabel ?? 'Todas las Financieras' }}</strong>
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name ?? 'Sistema' }}
    </div>

    <div class="summary-box">
        <strong>Total Movimientos:</strong> {{ $totals['count'] }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Suma Abonos:</strong> ${{ number_format($totals['amount'], 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Comisiones:</strong> ${{ number_format($totals['commission'], 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total General del Día:</strong> <strong>${{ number_format($totals['total_paid'], 2) }}</strong>
    </div>

    @if($groupedPayments->count() > 0)
        <div class="section-title">Resumen por Financiera</div>
        <table>
            <thead>
                <tr>
                    <th>Financiera</th>
                    <th class="text-center">Operaciones</th>
                    <th class="text-right">Suma Abonos ($)</th>
                    <th class="text-right">Comisiones ($)</th>
                    <th class="text-right">Subtotal Cobrado ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedPayments as $group)
                    <tr>
                        <td><strong>{{ $group['name'] }}</strong></td>
                        <td class="text-center">{{ $group['count'] }}</td>
                        <td class="text-right">${{ number_format($group['amount'], 2) }}</td>
                        <td class="text-right">${{ number_format($group['commission'], 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($group['total'], 2) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td>TOTAL GENERAL DEL DÍA / PERIODO</td>
                    <td class="text-center">{{ $totals['count'] }}</td>
                    <td class="text-right">${{ number_format($totals['amount'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['commission'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['total_paid'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @forelse($groupedPayments as $group)
        <div class="group-header">
            {{ strtoupper($group['name']) }} ({{ $group['count'] }} {{ $group['count'] === 1 ? 'abono' : 'abonos' }})
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:12%;">Fecha / Hora</th>
                    <th style="width:12%;">Sucursal</th>
                    <th style="width:20%;">Cliente</th>
                    <th style="width:16%;">Folio / TAG</th>
                    <th style="width:10%;">Método</th>
                    <th class="text-right" style="width:10%;">Abono ($)</th>
                    <th class="text-right" style="width:10%;">Comisión</th>
                    <th class="text-right" style="width:10%;">Total ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['payments'] as $p)
                    @php
                        $folioText = $p->posTransaction?->transaction_code ?: ($p->receipt_code ?: $p->tag_contrato);
                        $tagSuffix = ($p->tag_contrato && $folioText !== $p->tag_contrato) ? ' / TAG: ' . $p->tag_contrato : '';
                    @endphp
                    <tr>
                        <td>{{ optional($p->payment_date)->format('d/m/Y H:i') }}</td>
                        <td>{{ $p->branch?->name ?? 'General' }}</td>
                        <td>{{ $p->customer_name ?? ($p->sale?->customer_name ?? 'Cliente') }}</td>
                        <td><strong>{{ $folioText ?: '-' }}</strong>{{ $tagSuffix }}</td>
                        <td>{{ strtoupper((string) $p->payment_method) }}</td>
                        <td class="text-right">${{ number_format((float) $p->amount, 2) }}</td>
                        <td class="text-right">${{ number_format((float) $p->commission_amount, 2) }}</td>
                        <td class="text-right"><strong>${{ number_format((float) $p->total_amount, 2) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="5" class="text-right">SUBTOTAL {{ strtoupper($group['name']) }} ({{ $group['count'] }} movs):</td>
                    <td class="text-right">${{ number_format($group['amount'], 2) }}</td>
                    <td class="text-right">${{ number_format($group['commission'], 2) }}</td>
                    <td class="text-right">${{ number_format($group['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="text-center" style="padding: 16px;">No se encontraron abonos de financieras en la fecha o periodo seleccionado.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <div class="grand-total-box">
        <table>
            <tr>
                <td><strong>TOTAL GENERAL DEL DÍA ({{ $totals['count'] }} abonos)</strong></td>
                <td class="text-right">Abonos: <strong>${{ number_format($totals['amount'], 2) }}</strong></td>
                <td class="text-right">Comisiones: <strong>${{ number_format($totals['commission'], 2) }}</strong></td>
                <td class="text-right">Total Cobrado: <strong>${{ number_format($totals['total_paid'], 2) }}</strong></td>
            </tr>
        </table>
    </div>
</body>
</html>
