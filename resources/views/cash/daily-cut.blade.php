    @extends('dashboard.body.main')

    @section('container')
        <div class="container">
            <h2 class="mb-4">
                Corte Diario - {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                <small class="text-muted d-block" style="font-size: 1rem;">
                    Sucursal: {{ auth()->user()->branch->name ?? 'Sin sucursal asignada' }}
                </small>
            </h2>

            <a href="{{ route('cash.index') }}" class="btn btn-secondary mb-3">Volver</a>

            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Banco - Saldo Total</h5>
                </div>
                <div class="card-body">
                    <p><strong>Efectivo:</strong> ${{ number_format($handCash, 2) }}</p>
                    <p><strong>Transferencia:</strong> ${{ number_format($cheque, 2) }}</p>
                    <p><strong>Tarjeta:</strong> ${{ number_format($due, 2) }}</p>
                    <hr>
                    <p><strong class="text-success">Total Banco:</strong> <span
                            class="h5">${{ number_format($totalBanco, 2) }}</span></p>
                </div>
            </div>



            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white text-center">
                            <h3>Total Ingresos</h3>
                        </div>
                        <div class="card-body text-center">
                            <h1>${{ number_format($dailyCut->total_income, 2) }}</h1>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white text-center">
                            <h3>Total Egresos</h3>
                        </div>
                        <div class="card-body text-center">
                            <h1> ${{ number_format($dailyCut->total_expense, 2) }}</h1>
                        </div>
                    </div>
                </div>
            </div>


            @if ($dailyCut)
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white text-center">
                                <h3>Saldo Inicial</h3>
                            </div>
                            <div class="card-body text-center">
                                <h1>${{ number_format($dailyCut->opening_balance, 2) }}</h1>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-dark">
                            <div class="card-header bg-dark text-white text-center">
                                <h3>Saldo Final</h3>
                            </div>
                            <div class="card-body text-center">
                                <h1>${{ number_format($dailyCut->balance, 2) }}</h1>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


            <h5>Movimientos de Hoy</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Descripción</th>
                        <th>Módulo</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cashFlows as $flow)
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
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay movimientos para hoy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


            @php
                $totalCheque = $externalPayments->where('method', 'Cheque')->sum('amount');
                $totalDue = $externalPayments->where('method', 'Due')->sum('amount');
            @endphp

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="alert alert-primary text-center">
                        <strong>Total Transferencias:</strong> ${{ number_format($totalCheque, 2) }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning text-center">
                        <strong>Total Tarjeta:</strong> ${{ number_format($totalDue, 2) }}
                    </div>
                </div>
            </div>


            {{-- TABLA DE PAGOS POR TRANSFERENCIA --}}
            @php
                $transferPayments = $externalPayments->where('method', 'Cheque');
            @endphp

            @if ($transferPayments->count())
                <h5 class="mt-5">Pagos por Transferencia</h5>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Orden</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transferPayments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>#{{ $payment->order_id }}</td>
                                <td>${{ number_format($payment->amount, 2) }}</td>
                                <td>Transferencia</td>
                                <td>{{ $payment->user->name ?? 'N/D' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- TABLA DE PAGOS A CRÉDITO --}}
            @php
                $creditPayments = $externalPayments->where('method', 'Due');
            @endphp

            @if ($creditPayments->count())
                <h5 class="mt-5">Pagos con Tarjeta</h5>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Orden</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($creditPayments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>#{{ $payment->order_id }}</td>
                                <td>${{ number_format($payment->amount, 2) }}</td>
                                <td>Crédito</td>
                                <td>{{ $payment->user->name ?? 'N/D' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif



            <div class="mt-3">
                {{ $cashFlows->links('pagination::bootstrap-5') }}
            </div>

            @php
                $cutExists = \App\Models\DailyCut::where('date', \Carbon\Carbon::today())->exists();
            @endphp

            @if (!$cutExists)
                <form method="POST" action="{{ route('cash.applyCut') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="withdraw_amount" class="form-label">Monto a retirar del saldo final (opcional):</label>
                        <input type="number" name="withdraw_amount" id="withdraw_amount" step="0.01"
                            class="form-control" value="0">
                    </div>
                    <button type="submit" class="btn btn-primary mb-3">
                        Aplicar Corte del Día
                    </button>
                </form>
            @endif

            @if ($cutExists && $dailyCut)
                <a href="{{ route('cash.printCut') }}" class="btn btn-outline-secondary mb-3" target="_blank">
                    <i class="bi bi-printer"></i> Imprimir Corte
                </a>
            @endif


            @if ($cutExists)
                <div class="alert alert-info">El corte del día ya fue aplicado.</div>
            @endif
        </div>
    @endsection
