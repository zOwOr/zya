@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    /* =========================================================
       DISEÑO ESTANDARIZADO MÓDULO DE PRÉSTAMOS (UX MEJORADO)
       ========================================================= */
    .border-left-primary {
        border-left: 4px solid #32BDEA !important;
    }
    .border-left-dark {
        border-left: 4px solid #343a40 !important;
    }
    .border-left-success {
        border-left: 4px solid #1aa053 !important;
    }
    .border-left-info {
        border-left: 4px solid #00cae3 !important;
    }

    .loans-table-wrapper {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .loans-table {
        margin-bottom: 0;
        font-size: 13px;
    }
    .loans-table thead th {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        white-space: nowrap;
        vertical-align: middle;
    }
    .loans-table tbody td {
        padding: 12px 14px;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9;
    }
    .loans-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .loan-folio-link {
        color: #0d6efd;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .loan-folio-link:hover {
        color: #0b5ed7;
        text-decoration: underline;
    }
    .badge-pill-custom {
        border-radius: 50rem;
        padding: 4px 10px;
        font-weight: 600;
        font-size: 11px;
        letter-spacing: 0.3px;
    }
    .loan-progress {
        height: 6px;
        border-radius: 10px;
        background-color: #e2e8f0;
        min-width: 95px;
        overflow: hidden;
    }
    .loan-progress .progress-bar {
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        border-radius: 10px;
    }
    .btn-action-loan {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 11px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.15s ease;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

@php
    $isArticleView = $isArticleCreditView ?? ($operationType === 'credito_articulo');
    $currentIndexRoute = $isArticleView ? route('article-credits.index') : route('loans.index');
    $currentCreateRoute = $isArticleView ? route('article-credits.create') : route('loans.create');
@endphp
            <!-- Encabezado Estandarizado -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="mb-1 font-weight-bold">
                        <i class="{{ $isArticleView ? 'fa-solid fa-box-open text-info' : 'las la-hand-holding-usd text-primary' }}"></i>
                        {{ $isArticleView ? 'Módulo de Venta a Crédito de Artículos' : 'Módulo de Préstamos' }}
                    </h3>
                    <p class="mb-0 text-muted font-size-13">
                        @if ($isArticleView)
                            Ventas a crédito de estufas, tablets, laptops, planchas y otros artículos con ficha técnica, pagaré y recibo de entrega de mercancía.
                        @else
                            Gestión de préstamos por cliente, amortizaciones (día programado vs. real), pagaré y recibo de entrega.
                        @endif
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    <a href="{{ route('central-pos.index') }}" class="btn btn-outline-info mr-2 shadow-sm font-weight-bold">
                        <i class="las la-cash-register mr-1"></i>Punto de Venta (4 en 1)
                    </a>
                    @if ($isArticleView)
                        <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary mr-2 shadow-sm font-weight-bold">
                            <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Ir a Préstamos
                        </a>
                    @else
                        <a href="{{ route('article-credits.index') }}" class="btn btn-outline-info mr-2 shadow-sm font-weight-bold">
                            <i class="fa-solid fa-box-open mr-1"></i>Crédito de Artículos
                        </a>
                    @endif
                    @if (!$isArticleView && (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.catalogs')))
                        <a href="{{ route('loans.types.index') }}" class="btn btn-outline-primary mr-2 shadow-sm font-weight-bold">
                            <i class="las la-sliders-h mr-1"></i>Tipos de Préstamo
                        </a>
                    @endif
                    @if (auth()->user()->can('prestamos.create'))
                        <a href="{{ $currentCreateRoute }}" class="btn btn-primary shadow-sm font-weight-bold">
                            <i class="las la-plus mr-1"></i>{{ $isArticleView ? 'Nueva Venta a Crédito' : 'Nuevo Préstamo' }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- KPIs Estandarizados (Estilo PosDash / ERP) -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card border-0 border-left-primary shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted font-size-11 text-uppercase font-weight-bold">{{ $isArticleView ? 'Créditos Activos' : 'Préstamos Activos' }}</span>
                                <h3 class="mb-0 font-weight-bold text-dark mt-1">{{ $stats['active_count'] }}</h3>
                                <small class="text-danger font-weight-bold mt-1 d-block font-size-11">
                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $stats['mora_count'] }} en mora / vencidos
                                </small>
                            </div>
                            <i class="{{ $isArticleView ? 'fa-solid fa-box-open text-info' : 'las la-hand-holding-usd text-primary' }}" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card border-0 border-left-dark shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted font-size-11 text-uppercase font-weight-bold">{{ $isArticleView ? 'Monto Financiado' : 'Capital Colocado' }}</span>
                                <h3 class="mb-0 font-weight-bold text-dark mt-1">${{ number_format($stats['total_lent'], 2) }}</h3>
                                <small class="text-muted mt-1 d-block font-size-11">
                                    {{ $isArticleView ? ('Valor mercancía: $' . number_format($stats['total_price'] ?? 0, 2)) : 'Sin impacto de egreso en caja' }}
                                </small>
                            </div>
                            <i class="las la-wallet text-dark" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card border-0 border-left-success shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted font-size-11 text-uppercase font-weight-bold">{{ $isArticleView ? 'Abonos Recibidos' : 'Total Recaudado' }}</span>
                                <h3 class="mb-0 font-weight-bold text-success mt-1">${{ number_format($stats['total_collected'], 2) }}</h3>
                                <small class="text-muted mt-1 d-block font-size-11">
                                    {{ $isArticleView ? ('Enganches (Caja): $' . number_format($stats['total_down_payment'] ?? 0, 2)) : 'Reflejado en Caja y Cortes' }}
                                </small>
                            </div>
                            <i class="las la-coins text-success" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                    <div class="card border-0 border-left-info shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted font-size-11 text-uppercase font-weight-bold">{{ $isArticleView ? 'Saldo por Cobrar' : 'Comisiones Apertura' }}</span>
                                <h3 class="mb-0 font-weight-bold text-info mt-1">
                                    ${{ number_format($isArticleView ? ($stats['total_balance'] ?? 0) : $stats['total_commissions'], 2) }}
                                </h3>
                                <small class="text-muted mt-1 d-block font-size-11">
                                    {{ $isArticleView ? 'Pendiente total financiado' : 'Ingresos directos por apertura' }}
                                </small>
                            </div>
                            <i class="las la-receipt text-info" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros Estandarizados -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="{{ $currentIndexRoute }}" class="row align-items-end">
                        @if (!$isArticleView)
                            <input type="hidden" name="operation_type" value="{{ $operationType }}">
                        @endif
                        <div class="col-md-3 mb-2">
                            <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Buscar Folio, Cliente o Artículo</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ $isArticleView ? 'CRED-, cliente, marca, IMEI, serie...' : 'Código PREST-, cliente o tel...' }}">
                            </div>
                        </div>
                        @if (!$isArticleView)
                            <div class="col-md-3 mb-2">
                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Tipo de Préstamo</label>
                                <select name="loan_type_id" class="form-control form-control-sm">
                                    <option value="">Todos los tipos</option>
                                    @foreach ($loanTypes as $type)
                                        <option value="{{ $type->id }}" {{ (string)$loanTypeId === (string)$type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-2 mb-2">
                            <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Estado</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="">Todos</option>
                                <option value="activo" {{ $status === 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="mora" {{ $status === 'mora' ? 'selected' : '' }}>En Mora (Pendiente)</option>
                                <option value="liquidado" {{ $status === 'liquidado' ? 'selected' : '' }}>Liquidado</option>
                                <option value="cerrado_manual" {{ $status === 'cerrado_manual' ? 'selected' : '' }}>Cerrado Manual</option>
                                <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.all_branches'))
                            <div class="col-md-2 mb-2">
                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Sucursal</label>
                                <select name="branch_id" class="form-control form-control-sm">
                                    <option value="">Todas las sucursales</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string)$branchId === (string)$branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-2 mb-2 d-flex">
                            <button type="submit" class="btn btn-primary btn-sm mr-1 flex-grow-1 font-weight-bold shadow-sm">
                                <i class="fa-solid fa-filter mr-1"></i>Filtrar
                            </button>
                            <a href="{{ $currentIndexRoute }}" class="btn btn-light btn-sm border" title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla Estandarizada -->
            <div class="card border-0 shadow-sm loans-table-wrapper mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover loans-table mb-0 datatable-export" data-can-export="{{ auth()->user()->can('prestamos.export') ? 'true' : 'false' }}">
                            <thead>
                                <tr>
                                    <th style="min-width: 140px;">Folio / Fecha</th>
                                    <th style="min-width: 160px;">Cliente</th>
                                    <th style="min-width: 155px;">{{ $isArticleView ? 'Artículo / Modalidad' : 'Tipo de Préstamo' }}</th>
                                    <th style="min-width: 100px;">Sucursal</th>
                                    <th style="min-width: 125px;">{{ $isArticleView ? 'Precio / Financiado' : 'Capital / Total' }}</th>
                                    <th style="min-width: 90px;">{{ $isArticleView ? 'Enganche' : 'Comisión' }}</th>
                                    <th style="min-width: 145px;">Abonado / Saldo</th>
                                    <th style="min-width: 125px;">Cuotas</th>
                                    <th style="min-width: 105px;">Pagaré</th>
                                    <th style="min-width: 95px;">Estado</th>
                                    <th class="text-right" style="min-width: 170px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loans as $loan)
                                    @php
                                        $pendingCount = $loan->installments->whereIn('status', ['pendiente', 'parcial', 'vencido'])->count();
                                        $overdueCount = $loan->installments->where('status', 'vencido')->count();
                                        $pctPaid = $loan->total_amount > 0 ? min(100, round(($loan->paid_amount / $loan->total_amount) * 100)) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('loans.show', $loan->id) }}" class="loan-folio-link">
                                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>{{ $loan->loan_code }}
                                            </a>
                                            <small class="d-block text-muted mt-1 font-size-11">
                                                <i class="fa-regular fa-calendar mr-1"></i>{{ $loan->start_date ? $loan->start_date->format('d/m/Y') : '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-dark font-size-13">
                                                {{ $loan->customer?->tit_name ?? 'Cliente #' . $loan->customer_id }}
                                            </div>
                                            <small class="d-block text-muted font-size-11 mt-1">
                                                <i class="fa-solid fa-phone font-size-10 mr-1"></i>{{ $loan->customer?->tit_phone ?? 'Sin teléfono' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($loan->isArticleCredit())
                                                <span class="badge badge-info badge-pill-custom font-weight-bold">
                                                    {{ $loan->article_summary_label }}
                                                </span>
                                                @if ($loan->article_status_label)
                                                    <small class="d-block text-dark font-size-11 mt-1">Estado: {{ $loan->article_status_label }}</small>
                                                @endif
                                            @else
                                                <span class="badge badge-light border badge-pill-custom text-dark font-weight-bold">
                                                    {{ $loan->loanType?->name ?? 'General' }}
                                                </span>
                                            @endif
                                            <small class="d-block text-muted font-size-11 mt-1">
                                                <i class="fa-solid fa-repeat font-size-10 mr-1"></i>{{ $loan->payment_frequency_label }} ({{ $loan->term_periods }} pagos)
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border badge-pill-custom text-secondary">
                                                <i class="fa-solid fa-store font-size-10 mr-1"></i>{{ $loan->branch?->name ?? 'Principal' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-dark font-size-13">${{ number_format((float) $loan->total_amount, 2) }}</div>
                                            <small class="text-muted d-block font-size-11">
                                                @if ($loan->isArticleCredit())
                                                    Venta: ${{ number_format($loan->resolved_credit_sale_total, 2) }}
                                                @else
                                                    Cap: ${{ number_format((float) ($loan->principal_amount ?? 0), 2) }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if ($loan->isArticleCredit())
                                                <span class="text-success font-weight-bold font-size-13">${{ number_format((float) ($loan->down_payment ?? 0), 2) }}</span>
                                                <small class="text-muted d-block font-size-10 text-uppercase">
                                                    @if ($loan->article_price !== null && (float) $loan->article_price > 0)
                                                        Contado: ${{ number_format((float) $loan->article_price, 2) }}
                                                    @else
                                                        Interés: Sin calcular
                                                    @endif
                                                </small>
                                            @else
                                                <span class="text-info font-weight-bold font-size-13">${{ number_format($loan->opening_commission, 2) }}</span>
                                                <small class="text-muted d-block font-size-10 text-uppercase">Apertura</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-center font-size-12 mb-1">
                                                <strong class="text-success">${{ number_format($loan->paid_amount, 2) }}</strong>
                                                <span class="text-muted font-size-10 font-weight-bold">{{ $pctPaid }}%</span>
                                            </div>
                                            <div class="progress loan-progress" title="{{ $pctPaid }}% pagado">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $pctPaid }}%;"></div>
                                            </div>
                                            <small class="text-danger font-weight-bold d-block mt-1 font-size-11">
                                                Saldo: ${{ number_format($loan->balance_amount, 2) }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($pendingCount > 0 && $loan->isOpen())
                                                <span class="badge badge-warning badge-pill-custom text-dark">
                                                    <i class="fa-regular fa-clock mr-1"></i>{{ $pendingCount }} pendiente{{ $pendingCount > 1 ? 's' : '' }}
                                                </span>
                                                @if ($overdueCount > 0)
                                                    <small class="text-danger font-weight-bold d-block mt-1 font-size-11">
                                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $overdueCount }} en mora
                                                    </small>
                                                @endif
                                            @else
                                                <span class="badge badge-success badge-pill-custom">
                                                    <i class="fa-solid fa-circle-check mr-1"></i>Al corriente
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($loan->promissory_note_path)
                                                <a href="{{ asset('storage/' . $loan->promissory_note_path) }}" target="_blank" class="badge badge-success badge-pill-custom d-inline-block mb-1" title="Pagaré firmado cargado">
                                                    <i class="fa-solid fa-file-signature mr-1"></i>Pagaré OK
                                                </a>
                                            @else
                                                <span class="badge badge-secondary badge-pill-custom d-inline-block mb-1" title="Pendiente subir pagaré firmado">
                                                    <i class="fa-solid fa-file-circle-exclamation mr-1"></i>Sin firmar
                                                </span>
                                            @endif

                                            @if (auth()->user()->isSuperAdmin() && $loan->video_path)
                                                <a href="{{ asset('storage/' . $loan->video_path) }}" target="_blank" class="badge badge-dark badge-pill-custom d-inline-block" title="Video de expediente disponible">
                                                    <i class="fa-solid fa-video mr-1"></i>Video
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($loan->status === 'activo')
                                                <span class="badge badge-primary badge-pill-custom">ACTIVO</span>
                                            @elseif ($loan->status === 'mora')
                                                <span class="badge badge-danger badge-pill-custom"><i class="fa-solid fa-bell mr-1"></i>EN MORA</span>
                                            @elseif ($loan->status === 'liquidado')
                                                <span class="badge badge-success badge-pill-custom">LIQUIDADO</span>
                                            @elseif ($loan->status === 'cerrado_manual')
                                                <span class="badge badge-info badge-pill-custom">CERRADO</span>
                                            @elseif ($loan->status === 'cancelado')
                                                <span class="badge badge-dark badge-pill-custom"><i class="fa-solid fa-ban mr-1"></i>CANCELADO</span>
                                            @else
                                                <span class="badge badge-secondary badge-pill-custom">{{ strtoupper($loan->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right" style="white-space: nowrap;">
                                            <div class="d-inline-flex align-items-center">
                                                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-sm btn-primary btn-action-loan shadow-sm mr-1 font-weight-bold" title="Ver tabla de pagos y abonar">
                                                    <i class="fa-solid fa-eye mr-1"></i>Gestionar
                                                </a>
                                                <a href="{{ route('loans.pagare.print', $loan->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary btn-action-loan mr-1" title="Imprimir Formato Pagaré">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                                <a href="{{ route('loans.delivery-receipt.print', $loan->id) }}" target="_blank" class="btn btn-sm btn-outline-info btn-action-loan" title="{{ $loan->isArticleCredit() ? 'Recibo de Entrega de Mercancía' : 'Recibo de Entrega de Préstamo' }}">
                                                    <i class="fa-solid fa-receipt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-folder-open fa-2x text-muted mb-2 d-block" style="opacity: 0.4;"></i>
                                            <span class="font-weight-bold">No se encontraron préstamos registrados</span>
                                            <small class="d-block text-muted mt-1">Prueba cambiando los filtros de búsqueda o crea un nuevo préstamo.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($loans->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $loans->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar Tipos de Préstamo y los campos que requiere cada uno -->
@if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.catalogs'))
<div class="modal fade" id="loanTypesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fa-solid fa-sliders mr-2"></i>Catálogo de Tipos de Préstamo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 class="font-weight-bold mb-2">Tipos Existentes y Campos que Guardan</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Tipo de Préstamo</th>
                                <th>Tasa %</th>
                                <th>Comisión Apertura</th>
                                <th>Frecuencia</th>
                                <th>Información Específica que Solicita</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanTypes as $lt)
                                <tr>
                                    <td class="font-weight-bold">{{ $lt->name }}</td>
                                    <td>{{ $lt->default_interest_rate }}%</td>
                                    <td>${{ number_format($lt->default_opening_fee, 2) }}</td>
                                    <td>{{ ucfirst($lt->default_frequency) }} ({{ $lt->default_periods }})</td>
                                    <td>
                                        @if (!empty($lt->required_fields))
                                            @foreach ($lt->required_fields as $f)
                                                <span class="badge badge-light border mr-1">{{ $f['label'] ?? $f['key'] }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Estándar</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>
                <h6 class="font-weight-bold mb-3">Agregar Nuevo Tipo de Préstamo</h6>
                <form action="{{ route('loans.types.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nombre del Tipo de Préstamo *</label>
                            <input type="text" name="name" class="form-control" placeholder="Ej. Préstamo Vehicular / Nómina" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Tasa de Interés Default (%) *</label>
                            <input type="number" step="0.01" name="default_interest_rate" class="form-control" value="15" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Comisión por Apertura ($) *</label>
                            <input type="number" step="0.01" name="default_opening_fee" class="form-control" value="100" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Frecuencia de Pago Default *</label>
                            <select name="default_frequency" class="form-control" required>
                                <option value="semanal">Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Número de Pagos Default *</label>
                            <input type="number" name="default_periods" class="form-control" value="4" min="1" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Descripción Breve</label>
                            <input type="text" name="description" class="form-control" placeholder="Opcional...">
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Campos de Información a Guardar para este Tipo de Préstamo</label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="field_labels[]" class="form-control form-control-sm" placeholder="Campo 1 (Ej. Número de Serie / Placa)">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="field_labels[]" class="form-control form-control-sm" placeholder="Campo 2 (Ej. Descripción de Garantía)">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="text" name="field_labels[]" class="form-control form-control-sm" placeholder="Campo 3 (Ej. Aval / Referencia)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Guardar Tipo de Préstamo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
