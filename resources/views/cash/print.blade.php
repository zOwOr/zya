@extends('dashboard.body.main')

@section('container')
    <div class="container my-5" style="max-width: 700px;">
        <script>
            window.onload = () => window.print();
        </script>

        <div class="card shadow-sm border-primary">
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

        <style>
            /* Para impresión limpia */
            @media print {
                body {
                    -webkit-print-color-adjust: exact; /* colores en impresión */
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
            }
        </style>
    </div>
@endsection
