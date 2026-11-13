<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas de Financieras</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 14px;
        }
        .header {
            border-bottom: 2px solid #047857;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #047857;
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
            background: #ecfdf5;
        }
        .summary-label {
            font-size: 8px;
            color: #4b5563;
            text-transform: uppercase;
        }
        .summary-val {
            font-size: 11px;
            font-weight: bold;
            color: #064e3b;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background: #065f46;
            color: #ffffff;
            text-align: left;
            padding: 4px 5px;
            font-size: 8px;
            border: 1px solid #065f46;
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
        <h1>ZYA CELULARES — REPORTE DE VENTAS FINANCIERAS</h1>
        <div class="meta">
            <strong>Período:</strong> {{ $periodLabel }} |
            <strong>Sucursal:</strong> {{ $branchName }} |
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Registros</div>
                <div class="summary-val">{{ $totals['active_count'] }} activas / {{ $totals['count'] }} total</div>
            </td>
            <td>
                <div class="summary-label">Valor Equipos</div>
                <div class="summary-val">${{ number_format($totals['price_total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Enganches Efectivos</div>
                <div class="summary-val">${{ number_format($totals['down_payment_total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Monto a Crédito</div>
                <div class="summary-val">${{ number_format($totals['credit_total'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Abonos Registrados</div>
                <div class="summary-val">${{ number_format($totals['abonos_total'], 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Financiera</th>
                <th>TAG</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Equipo / IMEI</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Enganche</th>
                <th class="text-right">Crédito</th>
                <th class="text-right">Abonos</th>
                <th>Estado</th>
                <th>Vendedor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                @php
                    $effectiveEnganche = (!is_null($sale->enganche_descuento) && $sale->enganche_descuento !== '')
                        ? (float) $sale->enganche_descuento
                        : (float) $sale->down_payment;
                @endphp
                <tr>
                    <td><strong>{{ $sale->sale_code }}</strong></td>
                    <td>{{ optional($sale->sale_date)->format('d/m/Y') }}</td>
                    <td>{{ $sale->sale_type === 'contado' ? 'Contado' : 'Crédito' }}</td>
                    <td>{{ $sale->financiera?->name ?? 'Directo' }}</td>
                    <td>{{ $sale->tag_contrato ?: '-' }}</td>
                    <td>{{ $sale->branch?->name ?? 'General' }}</td>
                    <td>{{ $sale->customer_name }}</td>
                    <td>
                        {{ trim(($sale->device?->brand?->name ?? '') . ' ' . ($sale->device?->model ?? '')) }}
                        @if($sale->device?->imei)
                            <br><span style="color:#6b7280;">IMEI: {{ $sale->device->imei }}</span>
                        @endif
                    </td>
                    <td class="text-right">${{ number_format((float) $sale->price, 2) }}</td>
                    <td class="text-right">${{ number_format($effectiveEnganche, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $sale->credit_amount, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $sale->total_abonos_paid, 2) }}</td>
                    <td>{{ ucfirst((string) $sale->status) }}</td>
                    <td>{{ $sale->seller_display_name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align:center; padding:12px;">Sin ventas registradas para los filtros seleccionados.</td>
                </tr>
            @endforelse
            @if($sales->isNotEmpty())
                <tr class="tfoot-row">
                    <td colspan="8" class="text-right">TOTALES ACTIVAS ({{ $totals['active_count'] }}):</td>
                    <td class="text-right">${{ number_format($totals['price_total'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['down_payment_total'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['credit_total'], 2) }}</td>
                    <td class="text-right">${{ number_format($totals['abonos_total'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

