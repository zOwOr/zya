<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Órdenes y Ventas</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 14px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #2563eb;
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
            background: #eff6ff;
        }
        .summary-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .summary-val {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background: #1d4ed8;
            color: #ffffff;
            text-align: left;
            padding: 4px 5px;
            font-size: 8px;
            border: 1px solid #1d4ed8;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            font-size: 8px;
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
        <h1>ZYA CELULARES — {{ mb_strtoupper($statusLabel) }}</h1>
        <div class="meta">
            <strong>Período:</strong> {{ $periodLabel }} |
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Total Órdenes</div>
                <div class="summary-val">{{ $totals['count'] }}</div>
            </td>
            <td>
                <div class="summary-label">Total Ventas</div>
                <div class="summary-val">${{ number_format($totals['total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Pagado</div>
                <div class="summary-val">${{ number_format($totals['pay'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Saldo Pendiente</div>
                <div class="summary-val">${{ number_format($totals['due'], 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Nota de Venta</th>
                <th>Fecha</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Método de Pago</th>
                <th class="text-right">Total</th>
                <th class="text-right">Pagado</th>
                <th class="text-right">Saldo Pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><strong>{{ $order->invoice_no }}</strong></td>
                    <td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $order->resolved_branch?->name ?? 'Principal' }}</td>
                    <td>{{ $order->customer?->tit_name ?? 'Público en General' }}</td>
                    <td>{{ $order->payment_status }}</td>
                    <td class="text-right">${{ number_format((float) $order->total, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $order->pay, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $order->due, 2) }}</td>
                    <td>{{ ucfirst((string) $order->order_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:12px;">Sin órdenes para los filtros seleccionados.</td>
                </tr>
            @endforelse
            @if($orders->isNotEmpty())
                <tr class="tfoot-row">
                    <td colspan="5" class="text-right">TOTALES ({{ $totals['count'] }} órdenes):</td>
                    <td class="text-right">${{ number_format($totals['total'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['pay'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['due'], 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

