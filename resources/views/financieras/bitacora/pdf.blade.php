<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Abonos y Enganches a Financieras</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 14px;
        }
        .header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #1d4ed8;
        }
        .meta {
            margin-top: 4px;
            font-size: 8.5px;
            color: #4b5563;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 8px;
            background: #f8fafc;
        }
        .summary-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .summary-val {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data th {
            background: #1e3a8a;
            color: #ffffff;
            text-align: left;
            padding: 4px 5px;
            font-size: 8px;
            border: 1px solid #1e3a8a;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            font-size: 8px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td {
            background: #f9fafb;
        }
        .text-right {
            text-align: right !important;
        }
        .badge {
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-abono {
            background: #dcfce7;
            color: #166534;
        }
        .badge-enganche {
            background: #dbeafe;
            color: #1e40af;
        }
        .tfoot-row td {
            background: #e5e7eb !important;
            font-weight: bold;
            font-size: 8.5px;
        }
    </style>
</head>
<body>
    @php
        $totals = $bitacora['totals'];
        $movLabel = match ($bitacora['movementType']) {
            'abono' => 'Solo Abonos',
            'enganche' => 'Solo Enganches',
            default => 'Abonos + Enganches',
        };
    @endphp

    <div class="header">
        <h1>ZYA CELULARES — BITÁCORA DE ABONOS Y ENGANCHES A FINANCIERAS</h1>
        <div class="meta">
            <strong>Período:</strong> {{ $bitacora['periodLabel'] }} |
            <strong>Sucursal:</strong> {{ $bitacora['branchName'] }} |
            <strong>Tipo:</strong> {{ $movLabel }} |
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Total Abonos ({{ $totals['abonos_count'] }})</div>
                <div class="summary-val">${{ number_format($totals['abonos_total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Enganches ({{ $totals['enganches_count'] }})</div>
                <div class="summary-val">${{ number_format($totals['enganches_total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Comisiones</div>
                <div class="summary-val">${{ number_format($totals['abonos_commission'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total General ({{ $totals['count'] }} movs)</div>
                <div class="summary-val">${{ number_format($totals['grand_total'], 2) }}</div>
            </td>
        </tr>
    </table>

    @if($bitacora['groupedByFinanciera']->isNotEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th>Financiera</th>
                    <th class="text-right">Movimientos</th>
                    <th class="text-right">Cant. Abonos</th>
                    <th class="text-right">Total Abonos ($)</th>
                    <th class="text-right">Cant. Enganches</th>
                    <th class="text-right">Total Enganches ($)</th>
                    <th class="text-right">Total ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bitacora['groupedByFinanciera'] as $grp)
                    <tr>
                        <td><strong>{{ $grp['name'] }}</strong></td>
                        <td class="text-right">{{ $grp['count'] }}</td>
                        <td class="text-right">{{ $grp['abonos_count'] }}</td>
                        <td class="text-right">${{ number_format($grp['abonos_total'], 2) }}</td>
                        <td class="text-right">{{ $grp['enganches_count'] }}</td>
                        <td class="text-right">${{ number_format($grp['enganches_total'], 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($grp['total'], 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Tipo</th>
                <th>Folio/Recibo</th>
                <th>Financiera</th>
                <th>TAG</th>
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
            @forelse($bitacora['allEntries'] as $row)
                <tr>
                    <td>{{ $row['date_formatted'] }}</td>
                    <td>
                        <span class="badge {{ $row['movement_type'] === 'abono' ? 'badge-abono' : 'badge-enganche' }}">
                            {{ $row['movement_label'] }}
                        </span>
                    </td>
                    <td>{{ $row['folio'] }}</td>
                    <td>{{ $row['financiera_name'] }}</td>
                    <td>{{ $row['tag_contrato'] }}</td>
                    <td>{{ $row['imei'] }}</td>
                    <td>{{ $row['customer_name'] }}{{ !empty($row['is_external_customer']) ? ' (Ext.)' : '' }}</td>
                    <td>{{ $row['branch_name'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td class="text-right">${{ number_format($row['amount'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['commission'], 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($row['total'], 2) }}</strong></td>
                    <td>{{ $row['user_name'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center; padding:12px;">Sin registros para mostrar en este período.</td>
                </tr>
            @endforelse
            @if($bitacora['allEntries']->isNotEmpty())
                <tr class="tfoot-row">
                    <td colspan="9" class="text-right">TOTALES GENERALES ({{ $totals['count'] }} movimientos):</td>
                    <td class="text-right">${{ number_format($totals['abonos_amount'] + $totals['enganches_total'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['abonos_commission'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['grand_total'], 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

