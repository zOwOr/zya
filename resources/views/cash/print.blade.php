@extends('dashboard.body.main')

@section('container')
    <div class="container my-5" style="max-width: 900px;">
        <script>
            window.onload = () => window.print();
        </script>

        {{-- Encabezado del Corte --}}
        <div class="card shadow-sm border-primary mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h1 class="mb-0">Corte Diario</h1>
                <small class="fst-italic">{{ \Carbon\Carbon::parse($dailyCut->date)->format('d/m/Y') }}</small>
            </div>

            <div class="card-body">
                <h5 class="card-title mb-4 text-center text-secondary">
                    Sucursal: <span class="fw-bold">{{ auth()->user()->branch->name ?? 'Sin sucursal' }}</span>
                </h5>

                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th class="text-primary">Saldo Inicial</th>
                            <td>${{ number_format($dailyCut->opening_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="text-success">Total Ingresos</th>
                            <td>${{ number_format($dailyCut->total_income, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="text-danger">Total Egresos</th>
                            <td>${{ number_format($dailyCut->total_expense, 2) }}</td>
                        </tr>
                        <tr class="table-primary fw-bold">
                            <th>Saldo Final</th>
                            <td>${{ number_format($dailyCut->balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-muted text-center fst-italic">
                Generado por: <span class="fw-bold">{{ auth()->user()->name }}</span>
            </div>
        </div>

        {{-- Detalle de Movimientos --}}
        @if($cashFlows->count())
            <h4 class="mb-3 text-center">Detalle de Movimientos del Día</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Descripción</th>
                        <th>Módulo</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashFlows as $flow)
                        <tr>
                            <td>{{ $flow->created_at->format('H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $flow->type == 'income' ? 'success' : 'danger' }}">
                                    {{ $flow->type == 'income' ? 'Ingreso' : 'Egreso' }}
                                </span>
                            </td>
                            <td>${{ number_format($flow->amount, 2) }}</td>
                            <td>{{ $flow->description }}</td>
                            <td>{{ $flow->module }}</td>
                            <td>{{ $flow->reference }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-center text-muted">No hubo movimientos en este día.</p>
        @endif

        <style>
            /* Para impresión limpia */
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .card {
                    box-shadow: none !important;
                    border: 1px solid #000 !important;
                }
                .table-bordered th,
                .table-bordered td {
                    border: 1px solid #000 !important;
                }
                .badge {
                    color: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </div>
@endsection
