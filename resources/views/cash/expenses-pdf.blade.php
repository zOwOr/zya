<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Gastos por Periodo</title>
    <style>
        @page { margin: 12mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.3; }
        h2 { margin: 0 0 3px 0; font-size: 14px; color: #991b1b; }
        .subtitle { font-size: 9px; color: #475569; margin-bottom: 10px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; }
        .summary-box { background: #fef2f2; border: 1px solid #fecaca; padding: 7px 10px; margin-bottom: 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; font-size: 8.5px; text-transform: uppercase; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-row td { background: #fee2e2; font-weight: bold; color: #7f1d1d; border-top: 1.5px solid #991b1b; }
    </style>
</head>
<body>
    <h2>ZYA CELULARES - REPORTE DE GASTOS Y SALIDAS DE CAJA</h2>
    <div class="subtitle">
        <strong>Período ({{ $periodLabel }}):</strong> Del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        &nbsp;|&nbsp; <strong>Sucursal:</strong> {{ $branchLabel }}
        &nbsp;|&nbsp; <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name ?? 'Sistema' }}
    </div>

    <div class="summary-box">
        <strong>Movimientos de Gasto:</strong> {{ $expenses->count() }} &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total de Gastos del Período:</strong> <strong>${{ number_format($totalExpense, 2) }}</strong>
    </div>

    @if($byBranch->count() > 1)
        <table>
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th class="text-center">Cantidad de Gastos</th>
                    <th class="text-right">Total Gastos ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byBranch as $row)
                    <tr>
                        <td><strong>{{ $row['branch_name'] }}</strong></td>
                        <td class="text-center">{{ $row['count'] }}</td>
                        <td class="text-right">${{ number_format($row['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:13%;">Fecha / Hora</th>
                <th style="width:13%;">Sucursal</th>
                <th style="width:14%;">Módulo / Ref.</th>
                <th style="width:32%;">Concepto / Detalle del Gasto</th>
                <th style="width:14%;">Registró</th>
                <th class="text-right" style="width:14%;">Importe ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $exp)
                <tr>
                    <td>{{ optional($exp->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $exp->branch?->name ?? 'General' }}</td>
                    <td>
                        {{ $exp->resolved_expense_type_label ?: ($exp->module ?: 'Caja') }}
                        @if($exp->reference)
                            <br><small>{{ $exp->reference }}</small>
                        @endif
                    </td>
                    <td>{{ $exp->description ?: 'Sin motivo adicional' }}</td>
                    <td>{{ $exp->user?->name ?? 'Sistema' }}</td>
                    <td class="text-right"><strong>${{ number_format((float) $exp->amount, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 14px;">No se registraron gastos para el periodo y sucursal seleccionados.</td>
                </tr>
            @endforelse
            @if($expenses->count() > 0)
                <tr class="totals-row">
                    <td colspan="5" class="text-right">TOTAL GENERAL DE GASTOS DEL PERÍODO:</td>
                    <td class="text-right">${{ number_format($totalExpense, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

