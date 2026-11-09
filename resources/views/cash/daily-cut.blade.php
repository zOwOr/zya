@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="las la-calculator text-primary"></i> Corte de Caja Diario - {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
            </h3>
            <small class="text-muted">
                Sucursal: <strong>{{ $currentBranch->name ?? ($branches->firstWhere('id', $branchId)->name ?? 'Principal') }}</strong>
                &bull; Corte Automático:
                @if($currentBranch && $currentBranch->auto_cut_enabled)
                    <span class="badge badge-success"><i class="fa-solid fa-clock mr-1"></i>Activo a las {{ $currentBranch->auto_cut_time }} hrs</span>
                @else
                    <span class="badge badge-secondary"><i class="fa-solid fa-hand mr-1"></i>Solo Manual</span>
                @endif
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches') || auth()->user()->can('cash.edit'))
                <button type="button" class="btn btn-outline-primary mr-2 shadow-sm font-weight-bold" data-toggle="modal" data-target="#autoCutConfigModal">
                    <i class="las la-cog"></i> Configurar Cierre Automático
                </button>
            @endif
            <a href="{{ route('cash.index', ['branch_id' => $branchId, 'date' => $selectedDate]) }}" class="btn btn-outline-secondary mr-2 shadow-sm">
                <i class="las la-arrow-left"></i> Historial de Caja
            </a>
            <a href="{{ route('cash.printCut', ['date' => $selectedDate, 'branch_id' => $branchId, 'cut_id' => $finalDailyCut->id ?? null]) }}"
               class="btn btn-dark shadow-sm font-weight-bold" target="_blank">
                <i class="las la-print"></i> Imprimir Corte
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    {{-- Filtro por Fecha, Sucursal y Búsqueda --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('cash.dailyCut') }}" class="row align-items-end">
                <div class="col-md-3">
                    <label class="small font-weight-bold mb-1">Fecha de Corte:</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" class="form-control">
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                    <div class="col-md-3">
                        <label class="small font-weight-bold mb-1">Sucursal:</label>
                        <select name="branch_id" class="form-control">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="small font-weight-bold mb-1">Buscar cliente, referencia, módulo o método:</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Ej. nombre de cliente, efectivo, financiera...">
                </div>
                <div class="col-md-2 mt-2 mt-md-0">
                    <button class="btn btn-primary btn-block" type="submit">
                        <i class="las la-search"></i> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Estado del Corte del Día --}}
    @if($finalDailyCut)
        <div class="alert alert-success d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <i class="las la-check-circle la-lg mr-1"></i>
                <strong>Corte Final (Corte Z) Aplicado:</strong>
                Cerrado el {{ optional($finalDailyCut->closed_at ?? $finalDailyCut->created_at)->format('d/m/Y H:i') }}
                en modo <span class="badge badge-dark text-uppercase">{{ $finalDailyCut->cut_mode }}</span>
                por <strong>{{ $finalDailyCut->user->name ?? 'Cierre Programado Automático' }}</strong>.
            </div>
            <a href="{{ route('cash.printCut', ['cut_id' => $finalDailyCut->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-sm btn-success">
                <i class="las la-print"></i> Imprimir Corte Z
            </a>
        </div>
    @else
        <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <i class="las la-clock la-lg mr-1"></i>
                <strong>Caja Abierta / En Curso:</strong> Puedes generar <strong>Pre-Cortes (Corte X)</strong> parciales en cualquier momento o aplicar el <strong>Corte Final (Corte Z)</strong> al terminar el turno.
                @if($currentBranch && $currentBranch->auto_cut_enabled)
                    Si no se cierra manualmente, el sistema aplicará el Corte Z automático programado a las <strong>{{ $currentBranch->auto_cut_time }} hrs</strong>.
                @else
                    El corte automático está <strong>desactivado</strong> para esta sucursal (modo 100% manual).
                @endif
            </div>
        </div>
    @endif

    {{-- Tarjetas de Resumen Financiero y Métodos de Pago --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-info h-100 shadow-sm">
                <div class="card-header bg-info text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold">Saldo Inicial</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold mb-0">${{ number_format($dailyCut->opening_balance, 2) }}</h3>
                    <small class="text-muted">Arrastre del Corte Z anterior</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-success h-100 shadow-sm">
                <div class="card-header bg-success text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold">Total Ingresos del Día</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold text-success mb-1">${{ number_format($dailyCut->total_income, 2) }}</h3>
                    <div class="small text-left mt-2 border-top pt-2">
                        <div class="d-flex justify-content-between"><span>Efectivo:</span> <strong>${{ number_format($handCash, 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Tarjeta:</span> <strong>${{ number_format($due, 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Transferencia:</span> <strong>${{ number_format($cheque, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-danger h-100 shadow-sm">
                <div class="card-header bg-danger text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold">Total Egresos / Retiros</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold text-danger mb-0">${{ number_format($dailyCut->total_expense, 2) }}</h3>
                    <small class="text-muted">Gastos y retiros de caja</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-dark h-100 shadow-sm">
                <div class="card-header bg-dark text-white text-center py-2">
                    <h6 class="mb-0 font-weight-bold">Saldo Final Total</h6>
                </div>
                <div class="card-body text-center">
                    <h3 class="font-weight-bold mb-1">${{ number_format($dailyCut->balance, 2) }}</h3>
                    <small class="text-success font-weight-bold">
                        Efectivo esperado en cajón: ${{ number_format($dailyCut->opening_balance + $handCash - $dailyCut->total_expense, 2) }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones de Pre-Corte (Corte X) y Corte Final (Corte Z) --}}
    <div class="row mb-4">
        {{-- Panel Pre-Corte (Corte X) --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-file-invoice-dollar"></i> Pre-Corte Parcial (Corte X)</h6>
                    <span class="badge badge-light text-primary">No cierra la caja</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Genera una fotografía arqueo de la caja hasta este minuto para revisión o cambio de cajero, manteniendo la operación abierta.
                    </p>
                    <form method="POST" action="{{ route('cash.preCut') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="small font-weight-bold">Efectivo contado en cajón (opcional):</label>
                                <input type="number" step="0.01" name="declared_cash" class="form-control form-control-sm" placeholder="Ej. {{ number_format($dailyCut->opening_balance + $handCash - $dailyCut->total_expense, 2, '.', '') }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="small font-weight-bold">Observaciones del arqueo:</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Cambio de turno / Arqueo">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="las la-receipt"></i> Generar Pre-Corte (Corte X)
                        </button>
                    </form>

                    @if($preCuts->count())
                        <hr>
                        <h6 class="small font-weight-bold text-muted mb-2">Pre-Cortes (Corte X) realizados en esta fecha:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Usuario</th>
                                        <th>Ingresos</th>
                                        <th>Efectivo Declarado</th>
                                        <th>Dif.</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preCuts as $pc)
                                        <tr>
                                            <td>{{ $pc->created_at->format('H:i') }}</td>
                                            <td>{{ $pc->user->name ?? 'N/D' }}</td>
                                            <td>${{ number_format($pc->total_income, 2) }}</td>
                                            <td>{{ $pc->declared_cash !== null ? '$' . number_format($pc->declared_cash, 2) : '-' }}</td>
                                            <td class="{{ ($pc->difference_amount ?? 0) < 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                                {{ $pc->declared_cash !== null ? '$' . number_format($pc->difference_amount, 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('cash.printCut', ['cut_id' => $pc->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-xs btn-outline-dark py-0 px-1">
                                                    <i class="las la-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel Corte Final (Corte Z) --}}
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100 border-dark">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold"><i class="las la-lock"></i> Corte Final del Día (Corte Z)</h6>
                    <span class="badge badge-warning text-dark">Cierre Oficial Diario</span>
                </div>
                <div class="card-body">
                    @if(!$finalDailyCut)
                        <p class="small text-muted mb-3">
                            Registra el cierre definitivo del día con desglose completo por cliente, módulos integrados y saldo inicial para el día siguiente.
                        </p>
                        <form method="POST" action="{{ route('cash.applyCut') }}" id="cutForm">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="small font-weight-bold">Monto a retirar ($):</label>
                                    <input type="number" name="withdraw_amount" id="withdraw_amount" step="0.01" min="0" class="form-control form-control-sm" value="0">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small font-weight-bold">Efectivo físico contado ($):</label>
                                    <input type="number" name="declared_cash" step="0.01" min="0" class="form-control form-control-sm" placeholder="Opcional">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small font-weight-bold">Notas de Cierre:</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Corte Z de cierre">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm mt-2" id="cutButton">
                                <i class="las la-check-double"></i> Aplicar Corte Final (Corte Z)
                            </button>
                        </form>
                    @else
                        <div class="p-2 bg-light rounded border">
                            <p class="mb-1"><strong>Corte Z #{{ $finalDailyCut->id }}</strong> aplicado ({{ strtoupper($finalDailyCut->cut_mode) }}).</p>
                            <p class="mb-1 small">Retiro aplicado: <strong>${{ number_format($finalDailyCut->withdraw_amount, 2) }}</strong></p>
                            <p class="mb-1 small">Notas: {{ $finalDailyCut->notes ?? 'Sin observaciones' }}</p>
                            <a href="{{ route('cash.printCut', ['cut_id' => $finalDailyCut->id, 'branch_id' => $branchId]) }}" target="_blank" class="btn btn-sm btn-outline-dark mt-2">
                                <i class="las la-print"></i> Reimprimir Corte Z Oficial
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen por Módulo Integrado (Financieras, Préstamos, Tandas, Productos) --}}
    @if(isset($moduleBreakdown) && $moduleBreakdown->count())
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 font-weight-bold"><i class="las la-cubes text-primary"></i> Resumen por Módulo Operativo del Día</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Módulo</th>
                                <th class="text-center"># Movimientos</th>
                                <th class="text-right text-success">Ingresos ($)</th>
                                <th class="text-right text-danger">Egresos ($)</th>
                                <th class="text-right font-weight-bold">Neto ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleBreakdown as $mb)
                                <tr>
                                    <td class="font-weight-bold">{{ $mb['module'] }}</td>
                                    <td class="text-center">{{ $mb['count'] }}</td>
                                    <td class="text-right text-success">${{ number_format($mb['income'], 2) }}</td>
                                    <td class="text-right text-danger">${{ number_format($mb['expense'], 2) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format($mb['income'] - $mb['expense'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- DESGLOSE POR CLIENTE DE TODO EL MOVIMIENTO POR DÍA (Requerimiento Clave) --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">
                <i class="las la-user-check mr-1"></i> Desglose por Cliente de Todo el Movimiento del Día ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
            </h6>
            <span class="badge badge-light text-primary">{{ $customerBreakdown->count() }} Clientes</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Módulos Operados</th>
                            <th class="text-center"># Movs</th>
                            <th class="text-right">Efectivo</th>
                            <th class="text-right">Tarjeta</th>
                            <th class="text-right">Transferencia</th>
                            <th class="text-right">Comisiones</th>
                            <th class="text-right text-success">Total Ingresos</th>
                            <th class="text-right text-danger">Egresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerBreakdown as $cb)
                            <tr>
                                <td class="font-weight-bold">{{ $cb['customer_name'] }}</td>
                                <td><span class="badge badge-info">{{ $cb['modules'] ?: 'General' }}</span></td>
                                <td class="text-center">{{ $cb['movements_count'] }}</td>
                                <td class="text-right">${{ number_format($cb['cash'], 2) }}</td>
                                <td class="text-right">${{ number_format($cb['card'], 2) }}</td>
                                <td class="text-right">${{ number_format($cb['transfer'], 2) }}</td>
                                <td class="text-right">${{ number_format($cb['commission'], 2) }}</td>
                                <td class="text-right font-weight-bold text-success">${{ number_format($cb['total_income'], 2) }}</td>
                                <td class="text-right font-weight-bold text-danger">${{ number_format($cb['total_expense'], 2) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td colspan="9" class="p-2">
                                    <div class="small text-muted pl-3">
                                        <strong>Detalle de operaciones de {{ $cb['customer_name'] }}:</strong>
                                        <ul class="mb-0 pl-3">
                                            @foreach($cb['movements'] as $mItem)
                                                <li>
                                                    [{{ $mItem->created_at->format('H:i') }}]
                                                    <strong>{{ $mItem->module ?? 'Caja' }}</strong>:
                                                    {{ $mItem->description }}
                                                    — <strong>${{ number_format($mItem->amount, 2) }}</strong>
                                                    ({{ strtoupper($mItem->payment_method ?? 'efectivo') }})
                                                    @if($mItem->reference)
                                                        <code>Ref: {{ $mItem->reference }}</code>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No se registraron movimientos de clientes en esta fecha.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabla Cronológica de Movimientos del Día --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0 font-weight-bold">Bitácora Cronológica de Movimientos del Día</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
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
                            <th>Cajero</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cashFlows as $flow)
                            <tr>
                                <td>{{ $flow->created_at->format('H:i') }}</td>
                                <td class="font-weight-bold">{{ $flow->customer_display_name }}</td>
                                <td>
                                    <span class="badge badge-{{ $flow->type == 'income' ? 'success' : 'danger' }}">
                                        {{ $flow->type == 'income' ? 'Ingreso' : 'Egreso' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary text-uppercase">{{ $flow->payment_method ?? 'efectivo' }}</span>
                                    @if(($flow->payment_method ?? '') === 'mixto')
                                        <small class="d-block text-muted">
                                            Ef:${{ number_format($flow->cash_amount, 2) }} Tj:${{ number_format($flow->card_amount, 2) }} Tr:${{ number_format($flow->transfer_amount, 2) }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold">${{ number_format($flow->amount, 2) }}</td>
                                <td>{{ $flow->description }}</td>
                                <td>{{ $flow->module }}</td>
                                <td><code>{{ $flow->reference }}</code></td>
                                <td>{{ $flow->user->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay movimientos para esta fecha.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $cashFlows->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Modal de Configuración de Cierre Automático --}}
@if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches') || auth()->user()->can('cash.edit'))
<div class="modal fade" id="autoCutConfigModal" tabindex="-1" role="dialog" aria-labelledby="autoCutConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="autoCutConfigModalLabel">
                    <i class="las la-cog mr-1"></i> Configuración de Cierre Automático Diario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('cash.autoCutConfig') }}">
                @csrf
                <div class="modal-body">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                        <div class="form-group mb-3">
                            <label class="font-weight-bold font-size-14 text-dark">
                                <i class="las la-store mr-1 text-primary"></i> Seleccionar Sucursal a Configurar:
                            </label>
                            <select name="branch_id" id="modalBranchSelect" class="form-control font-weight-bold">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" 
                                            data-enabled="{{ $b->auto_cut_enabled ? '1' : '0' }}" 
                                            data-time="{{ $b->auto_cut_time ?? '22:00' }}"
                                            {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>
                                        {{ $b->name }} — [{{ $b->auto_cut_enabled ? 'Automático: ' . ($b->auto_cut_time ?? '22:00') : 'Manual' }}]
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Como administrador con acceso total multisucursal, puedes configurar individualmente cada sucursal.</small>
                        </div>
                    @else
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="alert alert-info py-2 px-3 mb-3">
                            <i class="las la-store mr-1"></i> Sucursal Asignada: <strong>{{ $currentBranch->name ?? 'Mi Sucursal' }}</strong>
                        </div>
                    @endif

                    <div class="card border p-3 bg-light mb-3">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="autoCutSwitch" name="auto_cut_enabled" value="1" {{ ($currentBranch && $currentBranch->auto_cut_enabled) ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold text-dark" for="autoCutSwitch">
                                Habilitar Corte Z Automático Diario
                            </label>
                        </div>
                        <small class="text-muted d-block">
                            <i class="fa-solid fa-circle-info mr-1 text-info"></i>
                            <strong>Modo Manual:</strong> Si este switch está desactivado, la caja permanecerá abierta de forma indefinida hasta que un operador autorizado aplique el Corte Z manualmente.
                        </small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold font-size-13 text-dark">Hora de Corte Programado (HH:MM):</label>
                        <input type="time" name="auto_cut_time" id="autoCutTimeInput" class="form-control" value="{{ $currentBranch->auto_cut_time ?? '22:00' }}" required>
                        <small class="text-muted d-block mt-1">A esta hora programada, el planificador del sistema verificará si la caja sigue abierta y aplicará el Corte Z oficial de forma automática.</small>
                    </div>

                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'))
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="font-weight-bold text-dark font-size-13 mb-2">
                                <i class="las la-list mr-1"></i> Resumen de Modo de Corte en Todas las Sucursales
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0 text-center font-size-12">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-left">Sucursal</th>
                                            <th>Modo Actual</th>
                                            <th>Hora Programada</th>
                                            <th>Acción Rápida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($branches as $b)
                                            <tr class="{{ (int)$branchId === (int)$b->id ? 'table-primary font-weight-bold' : '' }}">
                                                <td class="text-left">{{ $b->name }}</td>
                                                <td>
                                                    @if($b->auto_cut_enabled)
                                                        <span class="badge badge-success"><i class="las la-clock mr-1"></i>Automático</span>
                                                    @else
                                                        <span class="badge badge-secondary"><i class="las la-hand-paper mr-1"></i>Manual</span>
                                                    @endif
                                                </td>
                                                <td>{{ $b->auto_cut_time ?? '22:00' }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="selectBranchInModal('{{ $b->id }}', '{{ $b->auto_cut_enabled ? '1' : '0' }}', '{{ $b->auto_cut_time ?? '22:00' }}')">
                                                        Cargar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                        <i class="las la-save mr-1"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/pos-hardware.js') }}"></script>
<script>
// Apertura AUTOMÁTICA del cajón USB al enfocar el conteo de efectivo o al registrar corte
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
        // Disparo automático de cajón USB al procesar corte con éxito
        if (window.ZyaPosHardware) {
            ZyaPosHardware.autoOpenUsbDrawer();
        }
    @endif

    // Abrir automáticamente el cajón USB cuando el usuario va a contar el efectivo físico en el arqueo
    document.querySelectorAll('input[name="declared_cash"]').forEach(function (inp) {
        inp.addEventListener('focus', function () {
            if (window.ZyaPosHardware) {
                ZyaPosHardware.autoOpenUsbDrawer();
            }
        }, { once: true });
    });
});

function selectBranchInModal(branchId, isEnabled, cutTime) {
    const sel = document.getElementById('modalBranchSelect');
    if (sel) {
        sel.value = branchId;
    }
    const sw = document.getElementById('autoCutSwitch');
    const timeInp = document.getElementById('autoCutTimeInput');
    if (sw) sw.checked = (isEnabled === '1');
    if (timeInp) timeInp.value = cutTime;
}

const modalBranchSelect = document.getElementById('modalBranchSelect');
if (modalBranchSelect) {
    modalBranchSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const isEnabled = opt.getAttribute('data-enabled') === '1';
        const cutTime = opt.getAttribute('data-time') || '22:00';
        const sw = document.getElementById('autoCutSwitch');
        const timeInp = document.getElementById('autoCutTimeInput');
        if (sw) sw.checked = isEnabled;
        if (timeInp) timeInp.value = cutTime;
    });
}

const cutFormEl = document.getElementById('cutForm');
if (cutFormEl) {
    cutFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Confirmar Corte Final (Corte Z)?',
            text: 'Se registrará el cierre oficial del día con el desglose completo por cliente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar Corte Z',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                if (window.ZyaPosHardware) {
                    ZyaPosHardware.autoOpenUsbDrawer();
                }
                e.target.submit();
            }
        });
    });
}

const preCutForm = document.querySelector('form[action="{{ route('cash.preCut') }}"]');
if (preCutForm) {
    preCutForm.addEventListener('submit', function () {
        if (window.ZyaPosHardware) {
            ZyaPosHardware.autoOpenUsbDrawer();
        }
    });
}
</script>
@endsection
