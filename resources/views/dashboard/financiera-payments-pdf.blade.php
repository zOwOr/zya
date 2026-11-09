<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos de Financieras</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { margin-bottom: 4px; color: #1a237e; }
        .subtitle { margin-bottom: 14px; font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background-color: #f0f2f5; font-weight: bold; }
        .text-right { text-align: right; }
        .summary-box { margin-bottom: 12px; padding: 8px; background: #f8f9fa; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h2>ZYA ERP - Reporte de Abonos y Pagos de Financieras</h2>
    <div class="subtitle">
        Periodo consultado: <strong>{{ \Carbon\Carbon::parse($finStartDate)->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($finEndDate)->format('d/m/Y') }}</strong>
        | Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name ?? 'Sistema' }}
    </div>

    <div class="summary-box">
        <strong>Total Operaciones:</strong> {{ $totals['count'] }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Suma Abonos Financiera:</strong> ${{ number_format($totals['amount'], 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Suma Comisiones:</strong> ${{ number_format($totals['commission'], 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total Cobrado en Caja:</strong> ${{ number_format($totals['total_paid'], 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha / Hora</th>
                <th>Financiera</th>
                <th>Device ID / TAG</th>
                <th>IMEI</th>
                <th>Cliente</th>
                <th>Sucursal</th>
                <th>Método</th>
                <th class="text-right">Abono ($)</th>
                <th class="text-right">Comisión ($)</th>
                <th class="text-right">Total ($)</th>
                <th>Cajero</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ optional($p->payment_date)->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->finSupplier->name ?? ($p->finSale->financiera ?? 'Financiera') }}</td>
                    <td><strong>{{ $p->device_tag ?? '-' }}</strong></td>
                    <td>{{ $p->imei ?? '-' }}</td>
                    <td>{{ $p->customer_name ?? ($p->finSale->customer_name ?? 'Cliente') }}</td>
                    <td>{{ $p->branch->name ?? 'General' }}</td>
                    <td>{{ strtoupper($p->payment_method) }}</td>
                    <td class="text-right">${{ number_format($p->amount, 2) }}</td>
                    <td class="text-right">${{ number_format($p->commission_amount, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($p->total_paid, 2) }}</strong></td>
                    <td>{{ $p->user->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">No se encontraron pagos de financieras en este rango de fechas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

