@extends('dashboard.body.main')

@section('container')
<div class="container my-4" style="max-width: 960px;">
    <script>
        window.onload = () => window.print();
    </script>

    <div class="card shadow-sm border-dark mb-4">
        <div class="card-header bg-dark text-white text-center">
            <h3 class="mb-0">
                {{ ($dailyCut->cut_type ?? 'corte_z') === 'pre_corte' ? 'REPORTE DE PRE-CORTE (CORTE X)' : 'REPORTE DE CORTE FINAL DIARIO (CORTE Z)' }}
            </h3>
            <small>
                Fecha Operativa: {{ \Carbon\Carbon::parse($dailyCut->date)->format('d/m/Y') }}
                | Modo: {{ strtoupper($dailyCut->cut_mode ?? 'manual') }}
            </small>
        </div>

        <div class="card-body">
            <h5 class="text-center mb-3">
                Sucursal: <strong>{{ $dailyCut->branch->name ?? (auth()->user()->branch->name ?? 'Principal') }}</strong>
            </h5>

            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th>Saldo Inicial</th>
                                <td class="text-right">${{ number_format($dailyCut->opening_balance ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="text-success">Total Ingresos</th>
                                <td class="text-right font-weight-bold text-success">${{ number_format($dailyCut->total_income ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="text-danger">Total Egresos / Retiros</th>
                                <td class="text-right font-weight-bold text-danger">${{ number_format($dailyCut->total_expense ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-active font-weight-bold">
                                <th>Saldo Final en Caja (Efectivo Esperado)</th>
                                <td class="text-right">${{ number_format(($dailyCut->opening_balance ?? 0) + ($dailyCut->cash_income ?? 0) - ($dailyCut->total_expense ?? 0), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <th>Ingresos en Efectivo</th>
                                <td class="text-right">${{ number_format($dailyCut->cash_income ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Ingresos en Tarjeta</th>
                                <td class="text-right">${{ number_format($dailyCut->card_income ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Ingresos por Transferencia</th>
                                <td class="text-right">${{ number_format($dailyCut->transfer_income ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Efectivo Físico Declarado</th>
                                <td class="text-right">
                                    {{ isset($dailyCut->declared_cash) && $dailyCut->declared_cash !== null ? '$' . number_format($dailyCut->declared_cash, 2) : 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted text-center small">
            Impreso por: <strong>{{ auth()->user()->name ?? 'Sistema' }}</strong> | {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    {{-- Desglose por Cliente --}}
    @if(isset($customerBreakdown) && $customerBreakdown->count())
        <h5 class="mb-2 font-weight-bold">Desglose de Movimientos por Cliente</h5>
        <table class="table table-sm table-bordered mb-4">
            <thead class="thead-light">
                <tr>
                    <th>Cliente</th>
                    <th class="text-center"># Op.</th>
                    <th class="text-right">Ingresos ($)</th>
                    <th class="text-right">Egresos ($)</th>
                    <th>Detalle de Conceptos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customerBreakdown as $clientName => $items)
                    <tr>
                        <td class="font-weight-bold">{{ $clientName }}</td>
                        <td class="text-center">{{ $items->count() }}</td>
                        <td class="text-right">${{ number_format($items->where('type', 'income')->sum('amount'), 2) }}</td>
                        <td class="text-right">${{ number_format($items->where('type', 'expense')->sum('amount'), 2) }}</td>
                        <td class="small">
                            @foreach($items as $it)
                                <div>[{{ $it->created_at->format('H:i') }}] {{ $it->module }}: {{ $it->description }} (${{ number_format($it->amount, 2) }} - {{ strtoupper($it->payment_method ?? 'efectivo') }})</div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Bitácora General --}}
    @if($cashFlows->count())
        <h5 class="mb-2 font-weight-bold">Bitácora Cronológica del Día</h5>
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Método</th>
                    <th class="text-right">Monto</th>
                    <th>Descripción</th>
                    <th>Módulo</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashFlows as $flow)
                    <tr>
                        <td>{{ $flow->created_at->format('H:i') }}</td>
                        <td>{{ $flow->customer_display_name }}</td>
                        <td>{{ $flow->type == 'income' ? 'Ingreso' : 'Egreso' }}</td>
                        <td>{{ strtoupper($flow->payment_method ?? 'efectivo') }}</td>
                        <td class="text-right">${{ number_format($flow->amount, 2) }}</td>
                        <td>{{ $flow->description }}</td>
                        <td>{{ $flow->module }}</td>
                        <td>{{ $flow->reference }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
