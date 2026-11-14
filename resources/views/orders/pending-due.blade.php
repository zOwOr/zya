@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    @if (session()->has('success'))
        <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
            <div class="iq-alert-text font-weight-bold">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="ri-close-line text-white"></i>
            </button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
            <div class="iq-alert-text font-weight-bold">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="ri-close-line text-white"></i>
            </button>
        </div>
    @endif

    @if (session('print_ticket_url'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                if (typeof window.printTicketDirectly === 'function') {
                    var ticketUrl = @json(session('print_ticket_url'));
                    if (window.ZyaPosHardware && typeof window.ZyaPosHardware.wasRecentlyKickedAcrossPages === 'function' && window.ZyaPosHardware.wasRecentlyKickedAcrossPages(10000)) {
                        var sep = ticketUrl.indexOf('?') !== -1 ? '&' : '?';
                        ticketUrl = ticketUrl + sep + 'skip_drawer=1';
                    }
                    window.printTicketDirectly(ticketUrl);
                }
            });
        </script>
    @endif

    {{-- Encabezado Principal --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1 font-weight-bold text-dark">
                <i class="fa-solid fa-receipt text-warning mr-2"></i>Notas de Venta con Saldo Pendiente
            </h4>
            <p class="mb-0 text-muted font-size-13">
                Administración de cuentas por cobrar, abonos parciales y liquidación de notas de venta (POS y Préstamos).
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
            <a href="{{ route('central-pos.index') }}" class="btn btn-primary font-weight-bold mr-2 shadow-sm">
                <i class="fa-solid fa-cash-register mr-1"></i>Punto de Venta Central
            </a>
            <a href="{{ route('order.pendingDue') }}" class="btn btn-outline-secondary font-weight-bold">
                <i class="fa-solid fa-rotate mr-1"></i>Limpiar Filtros
            </a>
        </div>
    </div>

    {{-- Tarjetas KPI de Resumen --}}
    <div class="row mb-3">
        <div class="col-xl-3 col-sm-6 mb-2">
            <div class="card card-block card-stretch card-height shadow-sm border-left border-warning mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="font-size-11 font-weight-bold text-uppercase text-muted d-block">Notas Pendientes</span>
                            <h3 class="mb-0 font-weight-bold text-warning">{{ $notesKpi['count'] }}</h3>
                            <small class="text-muted">Cuentas activas por cobrar</small>
                        </div>
                        <div class="icon iq-icon-box-2 bg-light-warning rounded-circle text-warning font-size-20">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-2">
            <div class="card card-block card-stretch card-height shadow-sm border-left border-primary mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="font-size-11 font-weight-bold text-uppercase text-muted d-block">Total Facturado</span>
                            <h3 class="mb-0 font-weight-bold text-primary">${{ number_format($notesKpi['total'], 2) }}</h3>
                            <small class="text-muted">Monto total original</small>
                        </div>
                        <div class="icon iq-icon-box-2 bg-light-primary rounded-circle text-primary font-size-20">
                            <i class="fa-solid fa-tag"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-2">
            <div class="card card-block card-stretch card-height shadow-sm border-left border-success mb-0">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="font-size-11 font-weight-bold text-uppercase text-muted d-block">Total Abonado</span>
                            <h3 class="mb-0 font-weight-bold text-success">${{ number_format($notesKpi['pay'], 2) }}</h3>
                            <small class="text-muted">Cobrado hasta la fecha</small>
                        </div>
                        <div class="icon iq-icon-box-2 bg-light-success rounded-circle text-success font-size-20">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-2">
            <div class="card card-block card-stretch card-height shadow-sm border-left border-danger mb-0" style="background: #fff8f8;">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="font-size-11 font-weight-bold text-uppercase text-danger d-block">Saldo Pendiente por Cobrar</span>
                            <h3 class="mb-0 font-weight-bold text-danger">${{ number_format($notesKpi['due'], 2) }}</h3>
                            <small class="text-danger font-weight-bold">Deuda global en sucursales</small>
                        </div>
                        <div class="icon iq-icon-box-2 bg-light-danger rounded-circle text-danger font-size-20">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pestañas de Navegación: Notas POS vs Préstamos --}}
    <ul class="nav nav-pills mb-3 bg-white p-2 rounded shadow-sm border">
        <li class="nav-item">
            <a class="nav-link font-weight-bold {{ $activeTab !== 'prestamos' ? 'active' : '' }}" href="{{ route('order.pendingDue', array_merge(request()->query(), ['tab' => 'notas'])) }}">
                <i class="fa-solid fa-receipt mr-1"></i>Notas de Venta POS / Productos ({{ $notesKpi['count'] }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-weight-bold {{ $activeTab === 'prestamos' ? 'active' : '' }}" href="{{ route('order.pendingDue', array_merge(request()->query(), ['tab' => 'prestamos'])) }}">
                <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Préstamos con Saldo Pendiente ({{ $loansKpi['count'] }})
            </a>
        </li>
    </ul>

    {{-- Filtros y Búsqueda --}}
    <div class="card shadow-sm border mb-3">
        <div class="card-body p-3">
            <form action="{{ route('order.pendingDue') }}" method="get" id="filterDueForm">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Buscar por Folio / Cliente / Teléfono</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                            <input type="text" name="search" class="form-control" placeholder="Ej. INV-0001, Juan..." value="{{ request('search') }}">
                        </div>
                    </div>

                    @if ($canAccessAllBranches)
                        <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                            <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Sucursal</label>
                            <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">-- Todas las Sucursales --</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Desde Fecha</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>

                    <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Hasta Fecha</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>

                    <div class="col-lg-1 col-md-2 col-sm-4 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Mostrar</label>
                        <select name="row" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="15" {{ request('row', 15) == 15 ? 'selected' : '' }}>15</option>
                            <option value="30" {{ request('row') == 30 ? 'selected' : '' }}>30</option>
                            <option value="50" {{ request('row') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('row') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 col-sm-8 mb-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary font-weight-bold flex-grow-1 mr-1">
                            <i class="fa-solid fa-filter mr-1"></i>Filtrar
                        </button>
                        <a href="{{ route('order.pendingDue', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-danger" title="Limpiar búsqueda">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Contenido Pestaña 1: Notas de Venta POS --}}
    @if ($activeTab !== 'prestamos')
        <div class="table-responsive bg-white rounded shadow-sm border mb-3">
            <table class="table mb-0 cash-table">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Folio / Nota</th>
                        <th>Sucursal</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Conceptos / Detalle</th>
                        <th class="text-right">Total Nota</th>
                        <th class="text-right">Pagado</th>
                        <th class="text-right">Saldo Pendiente</th>
                        <th>Método Inicial</th>
                        <th class="text-center" style="width: 170px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $resolvedBranch = $order->resolved_branch;
                            $firstDetail = $order->orderDetails->first();
                            $extraCount = max(0, $order->orderDetails->count() - 1);
                        @endphp
                        <tr>
                            <td class="font-size-12 text-muted font-weight-bold">
                                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <strong class="font-size-13 text-dark font-weight-bold d-block">
                                    {{ $order->invoice_no }}
                                </strong>
                                <small class="text-muted">ID: #{{ $order->id }}</small>
                            </td>
                            <td>
                                <span class="badge badge-light font-size-11 border text-dark font-weight-bold">
                                    <i class="fa-solid fa-store text-primary mr-1"></i>{{ $resolvedBranch?->name ?? 'Sucursal Matriz' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark font-size-13">
                                    {{ $order->customer?->tit_name ?? 'Cliente Mostrador' }}
                                </div>
                                @if ($order->customer?->phone)
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-phone font-size-10 mr-1"></i>{{ $order->customer->phone }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="font-size-12 text-dark font-weight-bold">
                                    {{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}
                                </span>
                            </td>
                            <td>
                                @if ($firstDetail)
                                    <div class="font-size-12 font-weight-bold text-dark text-truncate" style="max-width: 180px;">
                                        {{ $firstDetail->is_dynamic ? $firstDetail->dynamic_product_name : ($firstDetail->productRelation?->product_name ?? 'Producto') }}
                                    </div>
                                    @if ($extraCount > 0)
                                        <small class="badge badge-info font-size-10">+{{ $extraCount }} más</small>
                                    @endif
                                @else
                                    <span class="text-muted font-size-12">-</span>
                                @endif
                            </td>
                            <td class="text-right font-weight-bold text-dark font-size-13">
                                ${{ number_format($order->total, 2) }}
                            </td>
                            <td class="text-right font-weight-bold text-success font-size-13">
                                ${{ number_format($order->pay, 2) }}
                            </td>
                            <td class="text-right">
                                <span class="badge badge-danger font-size-13 px-2 py-1 font-weight-bold">
                                    ${{ number_format($order->due, 2) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $pStatus = strtolower((string)$order->payment_status);
                                @endphp
                                @if (str_contains($pStatus, 'efectivo') || $pStatus === 'handcash')
                                    <span class="badge badge-success font-size-11">Efectivo</span>
                                @elseif (str_contains($pStatus, 'tarjeta') || $pStatus === 'due')
                                    <span class="badge badge-primary font-size-11">Tarjeta</span>
                                @elseif (str_contains($pStatus, 'transf') || $pStatus === 'cheque')
                                    <span class="badge badge-info font-size-11">Transf.</span>
                                @elseif (str_contains($pStatus, 'mixto'))
                                    <span class="badge badge-warning font-size-11 text-dark">Mixto</span>
                                @else
                                    <span class="badge badge-secondary font-size-11">{{ $order->payment_status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-success font-weight-bold px-2 py-1 mr-1" 
                                            onclick="openPayDueModal({{ $order->id }})" title="Abonar Pago">
                                        <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Abonar
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-info px-2 py-1 mr-1"
                                            onclick="openHistoryModal({{ $order->id }})" title="Ver Historial de Abonos">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>

                                    <a href="{{ route('order.orderDetails', $order->id) }}" class="btn btn-sm btn-outline-secondary px-2 py-1" title="Ver Detalle de la Nota">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fa-3x text-success mb-2 d-block" style="opacity: 0.6;"></i>
                                <h5 class="font-weight-bold text-dark">¡No hay Notas de Venta con Saldo Pendiente!</h5>
                                <p class="mb-0 font-size-13 text-muted">Todas las ventas están al corriente o no coinciden con los filtros aplicados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="text-muted font-size-13">Mostrando {{ $orders->count() }} de {{ $orders->total() }} notas pendientes</span>
            <div>{{ $orders->links() }}</div>
        </div>

    {{-- Contenido Pestaña 2: Préstamos con Saldo Pendiente --}}
    @else
        <div class="table-responsive bg-white rounded shadow-sm border mb-3">
            <table class="table mb-0 cash-table">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Código Préstamo</th>
                        <th>Sucursal</th>
                        <th>Cliente</th>
                        <th>Tipo de Préstamo</th>
                        <th>Fecha Inicio / Fin</th>
                        <th class="text-right">Monto Total</th>
                        <th class="text-right">Pagado</th>
                        <th class="text-right">Saldo Pendiente</th>
                        <th>Estado</th>
                        <th class="text-center" style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingLoans as $loan)
                        <tr>
                            <td class="font-size-12 text-muted font-weight-bold">
                                {{ ($pendingLoans->currentPage() - 1) * $pendingLoans->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <strong class="font-size-13 text-dark font-weight-bold d-block">
                                    {{ $loan->loan_code }}
                                </strong>
                                <small class="text-muted">Cuotas: {{ $loan->term_periods }} ({{ $loan->payment_frequency_label }})</small>
                            </td>
                            <td>
                                <span class="badge badge-light font-size-11 border text-dark font-weight-bold">
                                    <i class="fa-solid fa-store text-primary mr-1"></i>{{ $loan->branch?->name ?? 'Sucursal' }}
                                </span>
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark font-size-13">
                                    {{ $loan->customer?->tit_name ?? 'Cliente' }}
                                </div>
                                @if ($loan->customer?->phone)
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-phone font-size-10 mr-1"></i>{{ $loan->customer->phone }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info font-size-11">{{ $loan->loanType?->name ?? 'Préstamo' }}</span>
                            </td>
                            <td class="font-size-12">
                                <div><strong class="text-muted">Inicio:</strong> {{ \Carbon\Carbon::parse($loan->start_date)->format('d/m/Y') }}</div>
                                <div><strong class="text-muted">Vence:</strong> {{ \Carbon\Carbon::parse($loan->due_date)->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-right font-weight-bold text-dark font-size-13">
                                ${{ number_format($loan->total_amount, 2) }}
                            </td>
                            <td class="text-right font-weight-bold text-success font-size-13">
                                ${{ number_format($loan->paid_amount, 2) }}
                            </td>
                            <td class="text-right">
                                <span class="badge badge-danger font-size-13 px-2 py-1 font-weight-bold">
                                    ${{ number_format($loan->balance_amount, 2) }}
                                </span>
                            </td>
                            <td>
                                @if ($loan->status === 'vencido')
                                    <span class="badge badge-danger font-size-11 font-weight-bold">Vencido</span>
                                @else
                                    <span class="badge badge-warning font-size-11 text-dark font-weight-bold">Activo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('central-pos.index') }}?customer_id={{ $loan->customer_id }}" class="btn btn-sm btn-success font-weight-bold px-2 py-1 mr-1" title="Abonar en Punto de Venta">
                                    <i class="fa-solid fa-cash-register mr-1"></i>Abonar en POS
                                </a>
                                <a href="{{ route('loans.show', $loan->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-2 py-1" title="Ver Contrato / Pagaré">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fa-3x text-success mb-2 d-block" style="opacity: 0.6;"></i>
                                <h5 class="font-weight-bold text-dark">¡No hay Préstamos con Saldo Pendiente!</h5>
                                <p class="mb-0 font-size-13 text-muted">Todos los préstamos están completamente liquidados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="text-muted font-size-13">Mostrando {{ $pendingLoans->count() }} de {{ $pendingLoans->total() }} préstamos activos</span>
            <div>{{ $pendingLoans->links() }}</div>
        </div>
    @endif
</div>

{{-- MODAL MODERNO PARA ABONAR PAGO A NOTA DE VENTA --}}
<div class="modal fade" id="modalPayDue" tabindex="-1" role="dialog" aria-labelledby="modalPayDueTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow border-0">
            <form action="{{ route('order.updateDue') }}" method="post" id="payDueForm">
                @csrf
                <input type="hidden" name="order_id" id="modal_order_id">

                <div class="modal-header bg-primary text-white py-2 px-3">
                    <h5 class="modal-title font-weight-bold text-white" id="modalPayDueTitle">
                        <i class="fa-solid fa-hand-holding-dollar mr-2"></i>Registrar Abono a Nota de Venta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-3">
                    {{-- Tarjeta Informativa de la Cuenta --}}
                    <div class="bg-light p-3 rounded border mb-3">
                        <div class="row">
                            <div class="col-sm-6 mb-2 mb-sm-0">
                                <span class="text-muted font-size-11 font-weight-bold text-uppercase d-block">Folio de Nota</span>
                                <h5 class="font-weight-bold text-dark mb-1" id="lblModalInvoiceNo">-</h5>
                                <span class="font-size-12 text-muted" id="lblModalCustomerName">-</span>
                            </div>
                            <div class="col-sm-6 text-sm-right">
                                <span class="text-muted font-size-11 font-weight-bold text-uppercase d-block">Saldo Pendiente Actual</span>
                                <h3 class="font-weight-bold text-danger mb-0" id="lblModalDueAmount">$0.00</h3>
                                <small class="text-muted">Total Nota: <span id="lblModalTotalAmount">$0.00</span> | Pagado: <span id="lblModalPaidAmount" class="text-success font-weight-bold">$0.00</span></small>
                            </div>
                        </div>
                    </div>

                    {{-- Monto a Abonar --}}
                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="dueInput" class="font-size-12 font-weight-bold text-dark text-uppercase mb-0">
                                Monto a Abonar Hoy ($) <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-xs btn-outline-danger font-weight-bold" id="btnSettleFullDue">
                                <i class="fa-solid fa-bolt mr-1"></i>Liquidar Total (<span id="lblSettleTotal">$0.00</span>)
                            </button>
                        </div>
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend"><span class="input-group-text font-weight-bold bg-white">$</span></div>
                            <input type="number" step="0.01" min="0.01" class="form-control font-weight-bold text-success font-size-18" 
                                   id="dueInput" name="due" required placeholder="0.00">
                        </div>
                        <small class="text-muted font-size-11">Ingresa el monto que el cliente abona en esta operación.</small>
                    </div>

                    {{-- Selector de Método de Pago --}}
                    <div class="form-group mb-3">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Método de Pago</label>
                        <div class="row text-center" id="modalPaymentMethodsGrid">
                            <div class="col-3 pr-1">
                                <div class="border rounded p-2 pay-method-choice active cursor-pointer bg-light" data-val="efectivo">
                                    <i class="fa-solid fa-money-bill-wave text-success font-size-18 d-block mb-1"></i>
                                    <span class="font-size-12 font-weight-bold text-dark d-block">Efectivo</span>
                                </div>
                            </div>
                            <div class="col-3 px-1">
                                <div class="border rounded p-2 pay-method-choice cursor-pointer bg-white" data-val="tarjeta">
                                    <i class="fa-solid fa-credit-card text-primary font-size-18 d-block mb-1"></i>
                                    <span class="font-size-12 font-weight-bold text-dark d-block">Tarjeta</span>
                                </div>
                            </div>
                            <div class="col-3 px-1">
                                <div class="border rounded p-2 pay-method-choice cursor-pointer bg-white" data-val="transferencia">
                                    <i class="fa-solid fa-mobile-screen-button text-transfer font-size-18 d-block mb-1"></i>
                                    <span class="font-size-12 font-weight-bold text-dark d-block">Transf.</span>
                                </div>
                            </div>
                            <div class="col-3 pl-1">
                                <div class="border rounded p-2 pay-method-choice cursor-pointer bg-white" data-val="mixto">
                                    <i class="fa-solid fa-layer-group text-warning font-size-18 d-block mb-1"></i>
                                    <span class="font-size-12 font-weight-bold text-dark d-block">Mixto</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="modalPaymentMethodInput" value="efectivo">
                    </div>

                    {{-- 1. Detalle para Efectivo --}}
                    <div id="modalBoxEfectivo" class="p-2 rounded border mb-3 bg-light">
                        <div class="row mb-2">
                            <div class="col-6 pr-1">
                                <label class="font-size-11 font-weight-bold text-success mb-0 text-uppercase">Monto Efectivo ($)</label>
                                <input type="number" step="0.01" min="0" name="cash_amount" id="modalCashAmount" class="form-control form-control-sm font-weight-bold text-success font-size-14" placeholder="0.00">
                            </div>
                            <div class="col-6 pl-1">
                                <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Efectivo Recibido ($)</label>
                                <input type="number" step="0.01" min="0" name="cash_received" id="modalCashReceived" class="form-control form-control-sm font-weight-bold font-size-14" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Cambio a Entregar</label>
                            <input type="text" id="modalChangeDisplay" class="form-control form-control-sm cash-change-display" value="$0.00" readonly>
                        </div>
                    </div>

                    {{-- 2. Detalle para Tarjeta --}}
                    <div id="modalBoxTarjeta" class="p-2 rounded border mb-3 bg-light d-none">
                        <div class="row">
                            <div class="col-6 pr-1">
                                <label class="font-size-11 font-weight-bold text-primary mb-0 text-uppercase">Monto Tarjeta ($)</label>
                                <input type="number" step="0.01" min="0" name="card_amount" id="modalCardAmount" class="form-control form-control-sm font-weight-bold text-primary font-size-14" placeholder="0.00">
                            </div>
                            <div class="col-6 pl-1">
                                <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">No. Autoriz. / Voucher</label>
                                <input type="text" name="card_ref" id="modalCardRef" class="form-control form-control-sm font-size-13" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    {{-- 3. Detalle para Transferencia --}}
                    <div id="modalBoxTransferencia" class="p-2 rounded border mb-3 bg-light d-none">
                        <div class="row">
                            <div class="col-6 pr-1">
                                <label class="font-size-11 font-weight-bold text-transfer mb-0 text-uppercase">Monto Transf. ($)</label>
                                <input type="number" step="0.01" min="0" name="transfer_amount" id="modalTransferAmount" class="form-control form-control-sm font-weight-bold text-transfer font-size-14" placeholder="0.00">
                            </div>
                            <div class="col-6 pl-1">
                                <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Folio SPEI / Rastreo</label>
                                <input type="text" name="transfer_ref" id="modalTransferRef" class="form-control form-control-sm font-size-13" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    {{-- 4. Detalle para Pago Mixto con Notificación de Cantidad Faltante --}}
                    <div id="modalBoxMixto" class="p-2 rounded border mb-3 bg-light d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="font-weight-bold text-warning text-uppercase">
                                <i class="fa-solid fa-sliders mr-1"></i>Desglose del Pago Mixto:
                            </small>
                            <small class="text-muted font-size-11">Abono Total: <strong id="lblModalMixTarget" class="text-dark">$0.00</strong></small>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 pr-1">
                                <label class="font-size-11 font-weight-bold text-success mb-0">Efectivo ($)</label>
                                <input type="number" step="0.01" min="0" name="cash_amount_mix" id="modalMixCash" class="form-control form-control-sm font-weight-bold text-success" value="0">
                            </div>
                            <div class="col-4 px-1">
                                <label class="font-size-11 font-weight-bold text-primary mb-0">Tarjeta ($)</label>
                                <input type="number" step="0.01" min="0" name="card_amount_mix" id="modalMixCard" class="form-control form-control-sm font-weight-bold text-primary" value="0">
                            </div>
                            <div class="col-4 pl-1">
                                <label class="font-size-11 font-weight-bold text-transfer mb-0">Transf. ($)</label>
                                <input type="number" step="0.01" min="0" name="transfer_amount_mix" id="modalMixTransfer" class="form-control form-control-sm font-weight-bold text-transfer" value="0">
                            </div>
                        </div>

                        <!-- Notificación en Tiempo Real de Cantidad Faltante en Pago Mixto -->
                        <div id="modalMixFaltanteAlertContainer" class="mb-2">
                            <!-- Render dinámico por JS -->
                        </div>

                        <!-- Efectivo Recibido y Cambio para porción en efectivo en Mixto -->
                        <div id="modalMixCashDrawerRow" class="row mb-2">
                            <div class="col-6 pr-1">
                                <label class="font-size-11 font-weight-bold text-muted mb-0">Efectivo Recibido ($)</label>
                                <input type="number" step="0.01" min="0" id="modalMixCashReceived" class="form-control form-control-sm font-weight-bold" placeholder="0.00">
                            </div>
                            <div class="col-6 pl-1">
                                <label class="font-size-11 font-weight-bold text-muted mb-0">Cambio Efectivo</label>
                                <input type="text" id="modalMixChangeDisplay" class="form-control form-control-sm cash-change-display" value="$0.00" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 pr-1">
                                <label class="font-size-11 text-muted mb-0">Ref. Tarjeta</label>
                                <input type="text" id="modalMixCardRef" class="form-control form-control-sm" placeholder="Voucher/Auth">
                            </div>
                            <div class="col-6 pl-1">
                                <label class="font-size-11 text-muted mb-0">Ref. SPEI</label>
                                <input type="text" id="modalMixTransferRef" class="form-control form-control-sm" placeholder="Folio rastreo">
                            </div>
                        </div>
                    </div>

                    {{-- Referencia u Observaciones --}}
                    <div class="form-group mb-0">
                        <label for="modalNote" class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Nota / Observaciones (Opcional)</label>
                        <input type="text" name="note" id="modalNote" class="form-control form-control-sm" placeholder="Ej. Abono quincenal, anticipo de fin de mes...">
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success font-weight-bold px-4" id="btnSubmitAbono">
                        <i class="fa-solid fa-circle-check mr-2"></i>Confirmar y Registrar Abono
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PARA VER HISTORIAL DE ABONOS PREVIOS --}}
<div class="modal fade" id="modalAbonosHistory" tabindex="-1" role="dialog" aria-labelledby="modalHistoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold text-white" id="modalHistoryTitle">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>Historial de Pagos y Abonos
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                    <div>
                        <strong class="font-size-13 text-dark d-block" id="histOrderFolio">-</strong>
                        <small class="text-muted" id="histOrderCustomer">-</small>
                    </div>
                    <div class="text-right">
                        <span class="font-size-11 font-weight-bold text-muted text-uppercase d-block">Saldo Actual</span>
                        <h4 class="font-weight-bold text-danger mb-0" id="histOrderDue">$0.00</h4>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm cash-table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Monto Abonado</th>
                                <th>Método</th>
                                <th>Cajero</th>
                                <th>Sucursal</th>
                                <th>Nota</th>
                                <th class="text-center">Ticket</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentModalOrder = null;

    function openPayDueModal(orderId) {
        $.ajax({
            type: 'GET',
            url: '/order/due/' + orderId,
            dataType: 'json',
            success: function(data) {
                currentModalOrder = data;
                $('#modal_order_id').val(data.id);
                $('#lblModalInvoiceNo').text(data.invoice_no);
                $('#lblModalCustomerName').text(data.customer_name + (data.customer_phone ? ' | Tel: ' + data.customer_phone : ''));
                $('#lblModalDueAmount').text('$' + parseFloat(data.due).toFixed(2));
                $('#lblModalTotalAmount').text('$' + parseFloat(data.total).toFixed(2));
                $('#lblModalPaidAmount').text('$' + parseFloat(data.pay).toFixed(2));
                $('#lblSettleTotal').text('$' + parseFloat(data.due).toFixed(2));

                $('#dueInput').val(parseFloat(data.due).toFixed(2)).attr('max', data.due);
                $('#modalCashReceived').val('');
                $('#modalChangeDisplay').val('$0.00');
                $('#modalNote').val('');

                // Reset payment method to efectivo
                selectModalMethod('efectivo');

                $('#modalPayDue').modal('show');
            },
            error: function() {
                alert('No se pudo cargar la información de la orden.');
            }
        });
    }

    function selectModalMethod(method) {
        $('#modalPaymentMethodInput').val(method);
        $('#modalPaymentMethodsGrid .pay-method-choice').removeClass('active bg-light border-primary').addClass('bg-white');
        $('#modalPaymentMethodsGrid .pay-method-choice[data-val="' + method + '"]').addClass('active bg-light border-primary').removeClass('bg-white');

        $('#modalBoxEfectivo, #modalBoxTarjeta, #modalBoxTransferencia, #modalBoxMixto').addClass('d-none');

        const abono = parseFloat($('#dueInput').val()) || 0;

        if (method === 'efectivo') {
            $('#modalBoxEfectivo').removeClass('d-none');
            $('#modalCashAmount').val(abono > 0 ? abono.toFixed(2) : '');
        } else if (method === 'tarjeta') {
            $('#modalBoxTarjeta').removeClass('d-none');
            $('#modalCardAmount').val(abono > 0 ? abono.toFixed(2) : '');
        } else if (method === 'transferencia') {
            $('#modalBoxTransferencia').removeClass('d-none');
            $('#modalTransferAmount').val(abono > 0 ? abono.toFixed(2) : '');
        } else if (method === 'mixto') {
            $('#modalBoxMixto').removeClass('d-none');
            $('#lblModalMixTarget').text('$' + abono.toFixed(2));
            $('#modalMixCash').val(abono > 0 ? abono : 0);
            $('#modalMixCard').val(0);
            $('#modalMixTransfer').val(0);
            updateModalFaltanteAlert();
        }

        updateModalChange();
    }

    function updateModalFaltanteAlert() {
        const abono = parseFloat($('#dueInput').val()) || 0;
        const cash = parseFloat($('#modalMixCash').val()) || 0;
        const card = parseFloat($('#modalMixCard').val()) || 0;
        const transfer = parseFloat($('#modalMixTransfer').val()) || 0;
        const sum = Math.round((cash + card + transfer) * 100) / 100;
        const diff = Math.round((abono - sum) * 100) / 100;
        const container = document.getElementById('modalMixFaltanteAlertContainer');
        if (!container) return;

        if (diff > 0.005) {
            container.innerHTML = `
                <div class="alert alert-danger py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-circle-exclamation mr-1"></i>Cantidad Faltante:</span>
                    <span class="badge badge-danger font-size-14 px-2 py-1">$${diff.toFixed(2)}</span>
                </div>
                <small class="text-danger d-block font-size-11 font-weight-bold">
                    Asignado: $${sum.toFixed(2)} / Abono: $${abono.toFixed(2)}. Falta cubrir <strong>$${diff.toFixed(2)}</strong>.
                </small>
            `;
        } else if (Math.abs(diff) <= 0.005) {
            container.innerHTML = `
                <div class="alert alert-success py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-circle-check mr-1"></i>¡Abono Cubierto al 100%!</span>
                    <span class="badge badge-success font-size-13">$${sum.toFixed(2)}</span>
                </div>
            `;
        } else {
            const extra = Math.abs(diff);
            container.innerHTML = `
                <div class="alert alert-warning py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-triangle-exclamation mr-1"></i>Excedente sobre el Abono:</span>
                    <span class="badge badge-warning font-size-13 text-dark">+$${extra.toFixed(2)}</span>
                </div>
                <small class="text-warning d-block font-size-11 font-weight-bold">
                    La suma asignada ($${sum.toFixed(2)}) supera el abono por $${extra.toFixed(2)}.
                </small>
            `;
        }
    }

    function updateModalChange() {
        const method = $('#modalPaymentMethodInput').val();
        if (method === 'efectivo') {
            const cashToPay = parseFloat($('#modalCashAmount').val()) || 0;
            const received = parseFloat($('#modalCashReceived').val()) || 0;
            const change = Math.max(0, received - cashToPay);
            $('#modalChangeDisplay').val('$' + change.toFixed(2));
        } else if (method === 'mixto') {
            const cashPortion = parseFloat($('#modalMixCash').val()) || 0;
            const received = parseFloat($('#modalMixCashReceived').val()) || 0;
            const change = Math.max(0, received - cashPortion);
            $('#modalMixChangeDisplay').val('$' + change.toFixed(2));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#btnSettleFullDue').on('click', function () {
            if (currentModalOrder) {
                const fullDue = parseFloat(currentModalOrder.due).toFixed(2);
                $('#dueInput').val(fullDue);
                const method = $('#modalPaymentMethodInput').val();
                if (method === 'efectivo') $('#modalCashAmount').val(fullDue);
                if (method === 'tarjeta') $('#modalCardAmount').val(fullDue);
                if (method === 'transferencia') $('#modalTransferAmount').val(fullDue);
                if (method === 'mixto') {
                    $('#lblModalMixTarget').text('$' + fullDue);
                    $('#modalMixCash').val(fullDue);
                    $('#modalMixCard').val(0);
                    $('#modalMixTransfer').val(0);
                    updateModalFaltanteAlert();
                }
                updateModalChange();
            }
        });

        $('#dueInput').on('input', function () {
            if (currentModalOrder) {
                let val = parseFloat($(this).val()) || 0;
                if (val > currentModalOrder.due) {
                    val = currentModalOrder.due;
                    $(this).val(val);
                }
                const method = $('#modalPaymentMethodInput').val();
                if (method === 'efectivo') $('#modalCashAmount').val(val > 0 ? val : '');
                if (method === 'tarjeta') $('#modalCardAmount').val(val > 0 ? val : '');
                if (method === 'transferencia') $('#modalTransferAmount').val(val > 0 ? val : '');
                if (method === 'mixto') {
                    $('#lblModalMixTarget').text('$' + val.toFixed(2));
                    updateModalFaltanteAlert();
                }
            }
            updateModalChange();
        });

        // Captura directa en montos por método
        $('#modalCashAmount').on('input', function () {
            const v = parseFloat($(this).val()) || 0;
            $('#dueInput').val(v);
            updateModalChange();
        });

        $('#modalCardAmount').on('input', function () {
            const v = parseFloat($(this).val()) || 0;
            $('#dueInput').val(v);
        });

        $('#modalTransferAmount').on('input', function () {
            const v = parseFloat($(this).val()) || 0;
            $('#dueInput').val(v);
        });

        $('#modalPaymentMethodsGrid .pay-method-choice').on('click', function () {
            selectModalMethod($(this).data('val'));
        });

        $('#modalCashReceived, #modalMixCashReceived').on('input', function () {
            updateModalChange();
        });

        $('#modalMixCash, #modalMixCard, #modalMixTransfer').on('input', function () {
            updateModalFaltanteAlert();
            updateModalChange();
        });

        $('#payDueForm').on('submit', function (e) {
            const method = $('#modalPaymentMethodInput').val();
            const abono = parseFloat($('#dueInput').val()) || 0;

            if (abono <= 0) {
                e.preventDefault();
                alert('El monto del abono debe ser mayor a $0.00');
                return false;
            }

            if (currentModalOrder && abono > currentModalOrder.due + 0.05) {
                e.preventDefault();
                alert('El monto no puede ser mayor al saldo debido ($' + currentModalOrder.due.toFixed(2) + ').');
                return false;
            }

            // Limpiar inputs dinámicos anteriores
            $('#payDueForm input[name="cash_amount"], #payDueForm input[name="card_amount"], #payDueForm input[name="transfer_amount"], #payDueForm input[name="cash_received"]').remove();

            if (method === 'efectivo') {
                $('<input>').attr({type: 'hidden', name: 'cash_amount', value: abono}).appendTo('#payDueForm');
                $('<input>').attr({type: 'hidden', name: 'cash_received', value: $('#modalCashReceived').val() || abono}).appendTo('#payDueForm');
            } else if (method === 'tarjeta') {
                $('<input>').attr({type: 'hidden', name: 'card_amount', value: abono}).appendTo('#payDueForm');
            } else if (method === 'transferencia') {
                $('<input>').attr({type: 'hidden', name: 'transfer_amount', value: abono}).appendTo('#payDueForm');
            } else if (method === 'mixto') {
                const c = parseFloat($('#modalMixCash').val()) || 0;
                const d = parseFloat($('#modalMixCard').val()) || 0;
                const t = parseFloat($('#modalMixTransfer').val()) || 0;
                const sum = Math.round((c + d + t) * 100) / 100;
                const diff = Math.round((abono - sum) * 100) / 100;

                if (diff > 0.05) {
                    e.preventDefault();
                    alert('⚠️ En Pago Mixto hay una Cantidad Faltante de $' + diff.toFixed(2) + '. Completa la asignación antes de registrar el abono.');
                    return false;
                }
                if (diff < -0.05) {
                    e.preventDefault();
                    alert('⚠️ En Pago Mixto la suma asignada supera el abono por $' + Math.abs(diff).toFixed(2) + '. Ajusta los montos.');
                    return false;
                }

                $('<input>').attr({type: 'hidden', name: 'cash_amount', value: c}).appendTo('#payDueForm');
                $('<input>').attr({type: 'hidden', name: 'card_amount', value: d}).appendTo('#payDueForm');
                $('<input>').attr({type: 'hidden', name: 'transfer_amount', value: t}).appendTo('#payDueForm');
                $('<input>').attr({type: 'hidden', name: 'cash_received', value: $('#modalMixCashReceived').val() || c}).appendTo('#payDueForm');
            }

            // Disparo de apertura automática de cajón USB si se cobró efectivo (esperar cierre antes de enviar POST)
            const hasCash = (method === 'efectivo' || (method === 'mixto' && (parseFloat($('#modalMixCash').val()) || 0) > 0));
            const formEl = this;
            $('#btnSubmitAbono').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-2"></i>Registrando...');

            if (hasCash && window.ZyaPosHardware && typeof window.ZyaPosHardware.autoOpenUsbDrawerBounded === 'function' && !$(formEl).data('drawerSubmitting')) {
                e.preventDefault();
                $(formEl).data('drawerSubmitting', true);
                Promise.resolve(window.ZyaPosHardware.autoOpenUsbDrawerBounded(600))
                    .finally(function () {
                        formEl.submit();
                    });
                return false;
            } else if (hasCash && window.ZyaPosHardware) {
                try {
                    ZyaPosHardware.autoOpenUsbDrawer();
                } catch (hwErr) {}
            }
        });
    });

    function openHistoryModal(orderId) {
        $('#histOrderFolio').text('Cargando...');
        $('#histOrderCustomer').text('');
        $('#histOrderDue').text('$0.00');
        $('#historyTableBody').html('<tr><td colspan="7" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Cargando historial...</td></tr>');
        $('#modalAbonosHistory').modal('show');

        $.ajax({
            type: 'GET',
            url: '/orders/' + orderId + '/abonos-history',
            dataType: 'json',
            success: function(data) {
                $('#histOrderFolio').text('Nota: ' + data.order.invoice_no);
                $('#histOrderCustomer').text('Cliente: ' + data.order.customer_name + ' | Total: $' + data.order.total.toFixed(2) + ' | Pagado: $' + data.order.pay.toFixed(2));
                $('#histOrderDue').text('$' + data.order.due.toFixed(2));

                let rows = '';
                if (!data.histories || data.histories.length === 0) {
                    rows = '<tr><td colspan="7" class="text-center py-3 text-muted">No se han registrado abonos previos a esta nota.</td></tr>';
                } else {
                    data.histories.forEach(h => {
                        let methodBadge = '<span class="badge badge-success font-size-11">Efectivo</span>';
                        if (h.method === 'Cheque') methodBadge = '<span class="badge badge-info font-size-11">Transf.</span>';
                        if (h.method === 'Due') methodBadge = '<span class="badge badge-primary font-size-11">Tarjeta</span>';

                        rows += `
                            <tr>
                                <td class="font-size-12 font-weight-bold">${h.date}</td>
                                <td class="font-weight-bold text-success font-size-13">$${h.amount.toFixed(2)}</td>
                                <td>${methodBadge}</td>
                                <td class="font-size-12">${h.user_name}</td>
                                <td class="font-size-12">${h.branch_name}</td>
                                <td class="font-size-11 text-muted">${h.note || '-'}</td>
                                <td class="text-center">
                                    <button type="button" onclick="window.printTicketDirectly('${h.ticket_url}${(h.ticket_url && h.ticket_url.indexOf('?') !== -1) ? '&' : '?'}skip_drawer=1')" class="btn btn-xs btn-outline-primary" title="Imprimir Ticket">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#historyTableBody').html(rows);
            },
            error: function() {
                $('#historyTableBody').html('<tr><td colspan="7" class="text-center py-3 text-danger">Error al cargar el historial.</td></tr>');
            }
        });
    }
</script>
@endsection
