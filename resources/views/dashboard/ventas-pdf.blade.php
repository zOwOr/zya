<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas y Movimientos POS</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 14px;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #111827;
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
        }
        table.data th {
            background: #1f2937;
            color: #ffffff;
            text-align: left;
            padding: 4px 5px;
            font-size: 8px;
            border: 1px solid #1f2937;
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
        .tfoot-row td {
            background: #e5e7eb !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ZYA CELULARES — VENTAS Y MOVIMIENTOS POS (DASHBOARD)</h1>
        <div class="meta">
            <strong>Período:</strong> {{ $periodLabel }} |
            <strong>Sucursal:</strong> {{ $branchName }} |
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Operaciones</div>
                <div class="summary-val">{{ $totals['completed_count'] }} completadas / {{ $totals['cancelled_count'] }} canc.</div>
            </td>
            <td>
                <div class="summary-label">Efectivo</div>
                <div class="summary-val">${{ number_format($totals['cash'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Tarjeta</div>
                <div class="summary-val">${{ number_format($totals['card'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Transferencia</div>
                <div class="summary-val">${{ number_format($totals['transfer'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Cobrado</div>
                <div class="summary-val">${{ number_format($totals['total'], 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha/Hora</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Conceptos</th>
                <th>Método</th>
                <th class="text-right">Efectivo</th>
                <th class="text-right">Tarjeta</th>
                <th class="text-right">Transfer.</th>
                <th class="text-right">Comisión</th>
                <th class="text-right">Total</th>
                <th>Estado</th>
                <th>Cajero</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $tx)
                <tr>
                    <td><strong>{{ $tx->folio }}</strong></td>
                    <td>{{ optional($tx->transaction_date)->format('d/m/Y H:i') }}</td>
                    <td>{{ $tx->branch->name ?? 'Principal' }}</td>
                    <td>
                        {{ $tx->customer_name ?? ($tx->externalCustomer?->tit_name ?? 'Público en General') }}
                        @if($tx->external_customer_id) (Ext.) @endif
                    </td>
                    <td>{{ $tx->concepts_summary }}</td>
                    <td>{{ strtoupper((string) ($tx->payment_method ?: 'efectivo')) }}</td>
                    <td class="text-right">${{ number_format((float) $tx->cash_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $tx->card_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $tx->transfer_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $tx->commission_total, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format((float) $tx->total_amount, 2) }}</strong></td>
                    <td>{{ ucfirst((string) $tx->status) }}</td>
                    <td>{{ $tx->user->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center; padding:12px;">No hay ventas o movimientos POS en este período.</td>
                </tr>
            @endforelse
            @if($movements->isNotEmpty())
                <tr class="tfoot-row">
                    <td colspan="6" class="text-right">TOTALES COMPLETADAS ({{ $totals['completed_count'] }}):</td>
                    <td class="text-right">${{ number_format($totals['cash'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['card'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['transfer'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['commission'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['total'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

