@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    /* =========================================================
       DISEÑO ESTANDARIZADO POS & COBROS CENTRALIZADO (UX MEJORADO)
       ========================================================= */

    /* Barra Superior de Pestañas Multi-Proceso (Light Modern Theme) */
    .pos-tabs-bar {
        display: flex;
        align-items: flex-end;
        overflow-x: auto;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-bottom: 2px solid #cbd5e1;
        padding: 8px 12px 0 12px;
        border-radius: 12px 12px 0 0;
        gap: 6px;
        scrollbar-width: thin;
    }
    .pos-tabs-bar::-webkit-scrollbar {
        height: 4px;
    }
    .pos-tabs-bar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .pos-tab-item {
        display: inline-flex;
        align-items: center;
        background: #ffffff;
        color: #64748b;
        padding: 9px 16px;
        border-radius: 10px 10px 0 0;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
        border-bottom: none;
        transition: all 0.2s ease;
        position: relative;
        bottom: -2px;
    }
    .pos-tab-item:hover {
        background: #f1f5f9;
        color: #334155;
    }
    .pos-tab-item.active {
        background: #ffffff;
        color: #0f172a;
        font-weight: 700;
        border-color: #cbd5e1;
        border-top: 3px solid #32BDEA;
        border-bottom: 2px solid #ffffff;
        box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.04);
        z-index: 2;
    }
    .pos-tab-item .tab-badge {
        background: #e2e8f0;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        border-radius: 20px;
        padding: 2px 7px;
        margin-left: 8px;
        transition: all 0.2s;
    }
    .pos-tab-item.active .tab-badge {
        background: #32BDEA;
        color: #ffffff;
    }
    .pos-tab-item .tab-close {
        margin-left: 8px;
        color: #94a3b8;
        font-size: 16px;
        line-height: 1;
        padding: 2px 4px;
        border-radius: 50%;
        transition: all 0.15s;
    }
    .pos-tab-item .tab-close:hover {
        color: #ef4444;
        background: #fee2e2;
    }
    .btn-new-tab {
        align-self: center;
        margin-bottom: 4px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 14px;
    }

    /* Navegación de Secciones (1: Financieras, 2: Préstamos, 3: Tandas, 4: Productos) */
    .section-pill-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .section-pill-nav .nav-item {
        margin: 0;
    }
    .section-pill-nav .nav-link {
        border-radius: 10px;
        font-weight: 600;
        font-size: 13px;
        padding: 9px 14px;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .section-pill-nav .nav-link:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    .section-pill-nav .nav-link.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 2px 6px rgba(50, 189, 234, 0.15);
        position: relative;
    }
    .section-pill-nav .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 15%;
        right: 15%;
        height: 3px;
        border-radius: 3px 3px 0 0;
    }

    /* Indicadores y bordes por Sección */
    .pill-fin.active { border-color: #32BDEA; }
    .pill-fin.active::after { background: #32BDEA; }
    .pill-loan.active { border-color: #10b981; }
    .pill-loan.active::after { background: #10b981; }
    .pill-tanda.active { border-color: #8b5cf6; }
    .pill-tanda.active::after { background: #8b5cf6; }
    .pill-prod.active { border-color: #f59e0b; }
    .pill-prod.active::after { background: #f59e0b; }

    /* Panel de Carrito / Checkout */
    .cart-checkout-panel {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        background: #ffffff;
    }
    .cart-checkout-panel .card-header {
        background: #ffffff;
        border-bottom: 1px solid #edf2f7;
        padding: 12px 16px;
        border-radius: 12px 12px 0 0;
    }
    .cart-summary-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 11.5px;
    }
    .cart-summary-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .cart-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
    }
    .cart-items-scroll {
        max-height: 390px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 8px;
    }
    .cart-items-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .cart-items-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .cart-group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 9px;
        margin: 8px 0 6px;
        border-radius: 6px;
        background: #e2e8f0;
        color: #0f172a;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.35px;
    }
    .cart-group-header:first-child {
        margin-top: 0;
    }
    .cart-item-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--cart-accent, #3b82f6);
        border-radius: 8px;
        padding: 9px 10px;
        margin-bottom: 7px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: box-shadow 0.15s ease, transform 0.15s ease, background-color 0.25s ease;
    }
    .cart-item-card:last-child {
        margin-bottom: 0;
    }
    .cart-item-card:hover {
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
    }
    @keyframes cartCardFlash {
        0% { background-color: #eff6ff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35); }
        100% { background-color: #ffffff; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
    }
    .cart-item-card.is-highlighted {
        animation: cartCardFlash 1.4s ease;
    }
    .cart-item-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }
    .cart-item-index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 21px;
        height: 20px;
        padding: 0 5px;
        border-radius: 10px;
        background: #0f172a;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
    }
    .cart-item-title {
        font-weight: 700;
        font-size: 13px;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
    }
    .cart-item-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }
    .cart-meta-chip {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 1px 6px;
        font-size: 10.5px;
        line-height: 1.35;
    }
    .cart-item-controls {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        padding-top: 7px;
        border-top: 1px dashed #e2e8f0;
    }
    .cart-qty-stepper {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    .cart-qty-stepper button {
        border: none;
        background: #f8fafc;
        color: #334155;
        width: 24px;
        height: 26px;
        padding: 0;
        font-weight: 800;
        font-size: 13px;
        line-height: 1;
        cursor: pointer;
    }
    .cart-qty-stepper button:hover:not(:disabled) {
        background: #e2e8f0;
    }
    .cart-qty-stepper button:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .cart-qty-stepper input {
        width: 38px;
        height: 26px;
        border: none;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        padding: 0 2px;
        -moz-appearance: textfield;
    }
    .cart-qty-stepper input::-webkit-outer-spin-button,
    .cart-qty-stepper input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .cart-line-subtotal {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        text-align: right;
        min-width: 72px;
    }

    /* Caja de Totales Resaltada */
    .cart-totals-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
    }
    .cart-grand-total {
        font-size: 26px;
        font-weight: 800;
        color: #1e3a8a;
        line-height: 1;
    }

    /* Selector de Métodos de Pago Moderno */
    .pay-methods-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
    .pay-method-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 8px 4px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: center;
        user-select: none;
    }
    .pay-method-btn i {
        font-size: 16px;
        margin-bottom: 3px;
    }
    .pay-method-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .pay-method-btn.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    /* Caja de Cambio Resaltada (UX de Prevención de Errores) */
    .cash-change-display {
        background: #ecfdf5 !important;
        border: 2px solid #a7f3d0 !important;
        color: #047857 !important;
        font-weight: 800 !important;
        font-size: 18px !important;
        text-align: right;
    }

    /* Botón de Checkout */
    .btn-checkout-unified {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: #ffffff;
        font-weight: 700;
        font-size: 15px;
        padding: 12px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: all 0.2s ease;
    }
    .btn-checkout-unified:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        transform: translateY(-1px);
        color: #ffffff;
    }
    .btn-checkout-unified:active {
        transform: translateY(0);
    }
    .btn-checkout-unified.btn-checkout-pending {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        color: #ffffff;
    }
    .btn-checkout-unified.btn-checkout-pending:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        box-shadow: 0 6px 16px rgba(217, 119, 6, 0.45);
        color: #ffffff;
    }
    .pos-pending-alert-card {
        background: #fffbeb;
        border: 2px solid #f59e0b;
        border-radius: 10px;
        padding: 10px 12px;
    }

    /* Estilos de Alta Visibilidad y Contraste para Método Transferencia */
    .text-transfer,
    .text-transf {
        color: #0284c7 !important;
    }
    input.text-transfer,
    input.text-transf,
    label.text-transfer,
    label.text-transf {
        color: #0369a1 !important;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <!-- Encabezado Estandarizado del Sistema -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0 font-weight-bold">
                <i class="las la-cash-register text-primary"></i> Punto de Venta
            </h3>
            <small class="text-muted d-flex align-items-center flex-wrap">
                <span>Sucursal Activa:</span>
                @if (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.all_branches'))
                    <form method="GET" action="{{ route('central-pos.index') }}" class="d-inline-block ml-2">
                        <select name="branch_id" class="form-control form-control-sm d-inline-block w-auto font-weight-bold border-primary" onchange="this.form.submit()">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $b->id == $branchId ? 'selected' : '' }}>
                                    📍 {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <strong class="ml-1 text-primary">📍 {{ $branches->firstWhere('id', $branchId)->name ?? (auth()->user()->branch->name ?? 'Principal') }}</strong>
                @endif
                <span class="mx-2">&bull;</span>
                <span>Operador: <strong>{{ auth()->user()->name }}</strong></span>
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center mt-2 mt-md-0">
            <button type="button" id="btnPosTicketHistory" class="btn btn-outline-dark btn-sm mr-2 shadow-sm font-weight-bold" data-toggle="modal" data-target="#posHistoryModal">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Historial de Tickets
            </button>
            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.open_drawer') || auth()->user()->can('cash.open_drawer'))
                <div class="btn-group btn-group-sm mr-2 shadow-sm" role="group" aria-label="Control de cajón">
                    <button type="button" id="btnOpenPhysicalDrawer" class="btn btn-warning font-weight-bold">
                        <i class="las la-lock-open mr-1"></i> Abrir Cajón Físico
                    </button>
                    <button type="button" id="btnConfigurePhysicalDrawer" class="btn btn-outline-warning font-weight-bold" title="Configurar / cambiar puerto USB-COM y baudios" onclick="window.open('{{ route('central-pos.open-drawer') }}', 'zyaDrawerConfig', 'width=540,height=660,resizable=yes,scrollbars=yes')">
                        <i class="las la-cog"></i>
                    </button>
                </div>
            @endif
            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('orders.menu') || auth()->user()->can('orders.read'))
                <a href="{{ route('order.pendingDue') }}" class="btn btn-outline-warning btn-sm mr-2 shadow-sm font-weight-bold">
                    <i class="fa-solid fa-receipt mr-1"></i> Notas Pendientes
                </a>
            @endif
            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('cash.menu') || auth()->user()->can('cash.read') || auth()->user()->can('cash.pre_cut'))
                <a href="{{ route('cash.dailyCut', ['branch_id' => $branchId]) }}" class="btn btn-outline-info btn-sm mr-2 shadow-sm font-weight-bold">
                    <i class="las la-calculator mr-1"></i> Pre-Corte X / Z
                </a>
            @endif
            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.menu') || auth()->user()->can('prestamos.read') || auth()->user()->can('cobros.prestamos'))
                <a href="{{ route('loans.index') }}" class="btn btn-outline-primary btn-sm shadow-sm font-weight-bold">
                    <i class="las la-hand-holding-usd mr-1"></i> Préstamos
                </a>
            @endif
        </div>
    </div>

    <!-- Alerta de Venta/Cobro Exitoso con Reimpresión Directa -->
    <div id="posSaleSuccessAlert" class="alert alert-success alert-dismissible fade show d-none shadow-sm mb-3" role="alert" style="border-left: 5px solid #059669;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="fa-solid fa-circle-check fa-lg mr-2 text-success"></i>
                <strong class="font-size-14">¡Cobro realizado con éxito!</strong>
                <span class="ml-2 font-size-13 text-dark">Folio: <strong id="posSuccessTxCode" class="badge badge-dark font-size-13"></strong></span>
                <span class="text-muted ml-2 font-size-12 d-none d-md-inline">Ticket enviado directo a la impresora sin abrir nueva pestaña.</span>
            </div>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-dark font-weight-bold mr-2 shadow-sm" id="posBtnReimprimir">
                    <i class="fa-solid fa-print mr-1"></i> Reimprimir Ticket
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark font-weight-bold mr-2 shadow-sm" id="posBtnVerTicket" title="Ver ticket en pantalla / WhatsApp o cambiar tamaño">
                    <i class="fa-solid fa-eye mr-1"></i> Ver Ticket / Captura
                </button>
                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="document.getElementById('posSaleSuccessAlert').classList.add('d-none')">
                    <i class="fa-solid fa-check mr-1"></i> Listo
                </button>
            </div>
        </div>
    </div>

    <!-- Barra Superior de Pestañas Multi-Proceso (Light Modern Theme) -->
    <div class="pos-tabs-bar" id="posTabsContainer">
        <!-- Render dinámico por JS -->
    </div>

    @php
        $canFinancieras = auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.financieras') || auth()->user()->can('financieras.ventas.abonar');
        $canPrestamos = auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.prestamos') || auth()->user()->can('prestamos.abonar') || auth()->user()->can('prestamos.create');
        $canTandas = auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.tandas') || auth()->user()->can('tandas.abonar');
        $canProductos = auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.productos') || auth()->user()->can('pos.create') || auth()->user()->can('orders.create');
        $canGifts = $canProductos || auth()->user()->can('gifts.deliver') || auth()->user()->can('gifts.read');
        $canSecProductos = $canProductos || $canGifts;

        $activeTab = $canFinancieras ? 'sec-financieras' : ($canPrestamos ? 'sec-prestamos' : ($canTandas ? 'sec-tandas' : ($canSecProductos ? 'sec-productos' : 'none')));
    @endphp

    <!-- Cuerpo Principal del Punto de Venta Centralizado -->
    <div class="card border-0 shadow-sm rounded-0 rounded-bottom mb-4">
        <div class="card-body p-3">
            <div class="row">
                <!-- COLUMNA IZQUIERDA: Las 4 Secciones Interactivas -->
                <div class="col-lg-7">
                    <ul class="nav section-pill-nav" id="centralSectionTabs" role="tablist">
                        @if ($canFinancieras)
                        <li class="nav-item">
                            <a class="nav-link pill-fin {{ $activeTab === 'sec-financieras' ? 'active' : '' }}" data-toggle="pill" href="#sec-financieras" role="tab">
                                <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                                <span>1. Financieras (TAG / Device ID)</span>
                            </a>
                        </li>
                        @endif
                        @if ($canPrestamos)
                        <li class="nav-item">
                            <a class="nav-link pill-loan {{ $activeTab === 'sec-prestamos' ? 'active' : '' }}" data-toggle="pill" href="#sec-prestamos" role="tab">
                                <i class="fa-solid fa-hand-holding-dollar text-success"></i>
                                <span>2. Préstamos</span>
                            </a>
                        </li>
                        @endif
                        @if ($canTandas)
                        <li class="nav-item">
                            <a class="nav-link pill-tanda {{ $activeTab === 'sec-tandas' ? 'active' : '' }}" data-toggle="pill" href="#sec-tandas" role="tab">
                                <i class="fa-solid fa-piggy-bank" style="color: #8b5cf6;"></i>
                                <span>3. Tandas</span>
                            </a>
                        </li>
                        @endif
                        @if ($canSecProductos)
                        <li class="nav-item">
                            <a class="nav-link pill-prod {{ $activeTab === 'sec-productos' ? 'active' : '' }}" data-toggle="pill" href="#sec-productos" role="tab">
                                <i class="fa-solid fa-boxes-stacked text-warning"></i>
                                <span>4. Productos (Stock y Libre)</span>
                            </a>
                        </li>
                        @endif
                    </ul>

                    <div class="tab-content border rounded p-3 bg-white shadow-sm">
                        @if ($canFinancieras)
                        <!-- ==================== SECCIÓN 1: FINANCIERAS ==================== -->
                        <div class="tab-pane fade {{ $activeTab === 'sec-financieras' ? 'show active' : '' }}" id="sec-financieras" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-primary mb-0">
                                    <i class="fa-solid fa-landmark mr-1"></i>Cobro de Abono por Equipo de Financiera
                                </h6>
                                <span class="badge badge-light border text-muted">Device ID / TAG / IMEI</span>
                            </div>
                            <p class="text-muted font-size-12 mb-3">
                                Selecciona la financiera y busca el equipo del cliente por <strong>Device ID</strong>, <strong>TAG</strong>, <strong>IMEI</strong> o nombre.
                            </p>
                            <div class="row">
                                <div class="col-md-5 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Catálogo de Financiera</label>
                                    <select id="finCatalogSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Todas las Financieras --">
                                        <option value="">-- Todas las Financieras --</option>
                                        @foreach ($financieras as $fin)
                                            <option value="{{ $fin->id }}">{{ $fin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Device ID / TAG / IMEI / Nombre *</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="finSearchInput" class="form-control" placeholder="Escribe TAG, Device ID, IMEI o cliente...">
                                        <div class="input-group-append">
                                            <button type="button" id="btnSearchFinanciera" class="btn btn-primary font-weight-bold">
                                                <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="finSearchResults" class="mt-2"></div>

                            <!-- Formulario de abono de financiera seleccionado -->
                            <div id="finSelectedContractBox" class="p-3 rounded border mt-3 d-none" style="background: #f0f9ff; border-color: #bae6fd !important;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge badge-primary" id="finSelFinanciera"></span>
                                        <span class="badge badge-light border ml-1 d-none" id="finSelBranchBadge"></span>
                                        <h6 class="font-weight-bold mb-0 mt-1 text-dark" id="finSelCustomer"></h6>
                                        <small class="text-muted font-weight-bold" id="finSelDevice"></small>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-size-11 text-muted d-block text-uppercase">Abono Sugerido:</span>
                                        <strong class="text-success font-size-16" id="finSelSuggested">$0.00</strong>
                                    </div>
                                </div>
                                <input type="hidden" id="finSelSaleId">
                                <input type="hidden" id="finSelTitle">
                                <input type="hidden" id="finSelCustomerName">
                                <div class="row mt-2">
                                    <div class="col-md-4 form-group mb-2">
                                        <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Monto Abono ($) *</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" step="0.01" min="0" id="finAbonoAmount" class="form-control form-control-sm font-weight-bold" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group mb-2">
                                        <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Comisión Extra ($)</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" step="0.01" min="0" id="finCommissionAmount" class="form-control form-control-sm" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group mb-2 d-flex align-items-end">
                                        <button type="button" id="btnAddFinAbonoToTab" class="btn btn-success btn-sm btn-block font-weight-bold shadow-sm">
                                            <i class="fa-solid fa-cart-plus mr-1"></i>Agregar al Proceso
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pago de financiera por equipo comprado en empresa externa -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold text-warning text-dark mb-0 font-size-13">
                                        <i class="fa-solid fa-building-circle-arrow-right text-warning mr-1"></i>Pago de Financiera (Equipo de Empresa Externa)
                                    </h6>
                                    <button type="button" class="btn btn-xs btn-outline-warning text-dark font-weight-bold" data-toggle="collapse" data-target="#finExternalCompanyBox">
                                        <i class="fa-solid fa-plus mr-1"></i>Capturar Pago Externo
                                    </button>
                                </div>
                                <div id="finExternalCompanyBox" class="collapse p-3 rounded border" style="background: #fffbeb; border-color: #fde68a !important;">
                                    <div class="row">
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Financiera *</label>
                                            <select id="finExtFinancieraSelect" class="form-control form-control-sm">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach ($financieras as $fin)
                                                    @if (strtolower((string) ($fin->code ?? $fin->name)) !== 'contado')
                                                        <option value="{{ $fin->id }}">{{ $fin->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Referencia / TAG / Contrato *</label>
                                            <input type="text" id="finExtReference" class="form-control form-control-sm" placeholder="Ej. PJ-99812 o Ref">
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">IMEI / Equipo (opcional)</label>
                                            <input type="text" id="finExtImei" class="form-control form-control-sm" placeholder="IMEI o modelo...">
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Monto Abono ($) *</label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                <input type="number" step="0.01" min="0" id="finExtAmount" class="form-control form-control-sm font-weight-bold" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4 form-group mb-2">
                                            <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Comisión Extra ($)</label>
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                <input type="number" step="0.01" min="0" id="finExtCommission" class="form-control form-control-sm" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-4 form-group mb-2 d-flex align-items-end">
                                            <button type="button" id="btnAddExtFinToTab" class="btn btn-warning text-dark btn-sm btn-block font-weight-bold shadow-sm">
                                                <i class="fa-solid fa-cart-plus mr-1"></i>Agregar Pago Externo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if ($canPrestamos)
                        <!-- ==================== SECCIÓN 2: PRÉSTAMOS ==================== -->
                        <div class="tab-pane fade {{ $activeTab === 'sec-prestamos' ? 'show active' : '' }}" id="sec-prestamos" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-primary mb-0">
                                    <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Préstamos: Abonar Cuotas o Crear Préstamo
                                </h6>
                                <a href="{{ route('loans.create') }}" class="btn btn-xs btn-outline-primary font-weight-bold">
                                    <i class="fa-solid fa-up-right-from-square mr-1"></i>Expediente / Pagaré
                                </a>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">
                                    <i class="fa-solid fa-user mr-1 text-primary"></i>Seleccionar Cliente (Búsqueda / Autocomplete)
                                </label>
                                <select id="posLoanCustomerSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Buscar cliente por nombre o teléfono --">
                                    <option value="">-- Buscar cliente por nombre o teléfono --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" data-name="{{ $c->tit_name }}" data-phone="{{ $c->tit_phone }}">
                                            {{ $c->tit_name }} ({{ $c->tit_phone ?? 'Sin tel' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Alerta de préstamos abiertos / pagos pendientes -->
                            <div id="posLoanAlertBanner" class="alert alert-warning py-2 px-3 d-none font-size-13 mb-3 border"></div>

                            <!-- Tabla de préstamos activos del cliente para abonar -->
                            <div id="posCustomerOpenLoansContainer" class="mb-3"></div>

                            <!-- Sub-sección Colapsable para crear un préstamo rápido -->
                            <div class="card border mb-2 shadow-none bg-light">
                                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center" role="button" data-toggle="collapse" data-target="#collapseNewLoan" aria-expanded="false" style="cursor: pointer;">
                                    <span class="font-weight-bold font-size-13 text-dark">
                                        <i class="fa-solid fa-plus-circle text-primary mr-1"></i>Crear Nuevo Préstamo en este Proceso
                                    </span>
                                    <span class="badge badge-light border text-muted">
                                        <i class="fa-solid fa-chevron-down mr-1"></i>Expandir Formulario
                                    </span>
                                </div>
                                <div id="collapseNewLoan" class="collapse">
                                    <div class="card-body p-3">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fa-solid fa-circle-info text-info mr-1"></i>Nota: Solo entra a Caja la Comisión por Apertura. El capital no impacta egreso en esta venta.
                                        </small>
                                        <div class="row">
                                            <div class="col-md-5 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Tipo de Préstamo (Opcional)</label>
                                                <select id="posNewLoanType" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Sin tipo / Directo --">
                                                    <option value="">-- Sin tipo / Préstamo Directo --</option>
                                                    @foreach ($loanTypes as $lt)
                                                        <option value="{{ $lt->id }}" data-rate="{{ $lt->default_interest_rate }}" data-fee="{{ $lt->default_opening_fee }}" data-freq="{{ $lt->default_frequency }}" data-periods="{{ $lt->default_periods }}">
                                                            {{ $lt->name }} ({{ number_format($lt->default_interest_rate, 0) }}%)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Capital Prestado ($)</label>
                                                <input type="number" step="0.01" min="1" id="posNewLoanPrincipal" class="form-control form-control-sm font-weight-bold" value="5000">
                                            </div>
                                            <div class="col-md-4 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-primary text-uppercase mb-1">Abono por Cuota ($)</label>
                                                <input type="number" step="0.01" min="0.01" id="posNewLoanInstallment" class="form-control form-control-sm font-weight-bold text-primary border-primary" value="854" placeholder="Ej. 854.00">
                                            </div>

                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">No. Pagos</label>
                                                <input type="number" min="1" id="posNewLoanPeriods" class="form-control form-control-sm font-weight-bold" value="8">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Frecuencia</label>
                                                <select id="posNewLoanFreq" class="form-control form-control-sm font-weight-bold">
                                                    <option value="12_26">12 y 26</option>
                                                    <option value="quincenal">Quincenal (15 y 30)</option>
                                                    <option value="semanal">Semanal</option>
                                                    <option value="mensual">Mensual</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Tasa Calculada (%)</label>
                                                <input type="number" step="0.01" min="0" id="posNewLoanRate" class="form-control form-control-sm font-weight-bold" value="36.64">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-success text-uppercase mb-1">Comisión Apertura ($)</label>
                                                <input type="number" step="0.01" min="0" id="posNewLoanComm" class="form-control form-control-sm font-weight-bold text-success" value="150">
                                            </div>

                                            <div class="col-md-8 form-group mb-2 d-flex align-items-center">
                                                <div class="bg-light px-2 py-1 rounded border w-100">
                                                    <span class="font-size-12">Total a Pagar: <strong id="posNewLoanTotalPreview" class="text-primary font-size-13">$6,832.00</strong></span>
                                                    <span class="text-muted font-size-11 ml-2">| Interés: <strong id="posNewLoanInterestPreview">$1,832.00 (36.64%)</strong></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 form-group mb-2 d-flex align-items-end">
                                                <button type="button" id="btnAddNewLoanToTab" class="btn btn-primary btn-sm btn-block font-weight-bold shadow-sm">
                                                    <i class="fa-solid fa-plus mr-1"></i>Agregar Apertura
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if ($canTandas)
                        <!-- ==================== SECCIÓN 3: TANDAS ==================== -->
                        <div class="tab-pane fade {{ $activeTab === 'sec-tandas' ? 'show active' : '' }}" id="sec-tandas" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-primary mb-0">
                                    <i class="fa-solid fa-piggy-bank mr-1"></i>Cobro de Tandas (Clientes Básicos Directos)
                                </h6>
                                <a href="{{ route('tandas.create') }}" class="btn btn-xs btn-outline-primary font-weight-bold">
                                    <i class="fa-solid fa-plus mr-1"></i>Nueva Tanda
                                </a>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Seleccionar Tanda Activa *</label>
                                    <select id="posTandaSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Elige una tanda --">
                                        <option value="">-- Elige una tanda --</option>
                                        @foreach ($tandas as $tanda)
                                            @php
                                                $tandaParticipants = $tanda->participants->map(function ($p) {
                                                    return [
                                                        'id' => $p->id,
                                                        'name' => $p->display_name,
                                                        'phone' => $p->display_phone,
                                                        'position' => $p->position,
                                                    ];
                                                })->values();
                                            @endphp
                                            <option value="{{ $tanda->id }}"
                                                data-name="{{ $tanda->description }}"
                                                data-amount="{{ $tanda->payment_amount }}"
                                                data-participants='@json($tandaParticipants)'>
                                                {{ $tanda->description }} (Aportación: ${{ number_format($tanda->payment_amount, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Participante de la Tanda</label>
                                    <select id="posTandaParticipantSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Selecciona participante existente --">
                                        <option value="">-- Selecciona participante existente --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Opción para crear nuevo participante en la tanda al instante -->
                            <div class="p-2 rounded border mb-3 bg-light">
                                <small class="font-weight-bold text-dark d-block mb-1">
                                    <i class="fa-solid fa-user-plus text-primary mr-1"></i>¿Nuevo participante en esta tanda? Llena aquí:
                                </small>
                                <div class="row">
                                    <div class="col-md-5 mb-1">
                                        <input type="text" id="posTandaNewName" class="form-control form-control-sm" placeholder="Nombre completo...">
                                    </div>
                                    <div class="col-md-4 mb-1">
                                        <input type="text" id="posTandaNewPhone" class="form-control form-control-sm" placeholder="Teléfono...">
                                    </div>
                                    <div class="col-md-3 mb-1">
                                        <input type="number" id="posTandaNewPos" class="form-control form-control-sm" placeholder="# Turno" min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-end">
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">No. Periodo a Pagar *</label>
                                    <input type="number" id="posTandaPeriodNo" class="form-control form-control-sm font-weight-bold" value="1" min="1">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Monto Aportación ($) *</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" step="0.01" min="0.01" id="posTandaAmount" class="form-control form-control-sm font-weight-bold" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <button type="button" id="btnAddTandaToTab" class="btn btn-success btn-sm btn-block font-weight-bold shadow-sm">
                                        <i class="fa-solid fa-cart-plus mr-1"></i>Agregar Aportación
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if ($canSecProductos)
                        <!-- ==================== SECCIÓN 4: VENTA DE PRODUCTOS (INVENTARIO + DINÁMICOS + REGALOS/GIFTS) ==================== -->
                        <div class="tab-pane fade {{ $activeTab === 'sec-productos' ? 'show active' : '' }}" id="sec-productos" role="tabpanel">
                            <!-- Sub-pestañas para Catálogo vs Producto Libre vs Regalos -->
                            <ul class="nav nav-pills mb-3 bg-light p-1 rounded" id="pills-prod-subtabs" role="tablist">
                                @if ($canProductos)
                                <li class="nav-item">
                                    <a class="nav-link active font-size-12 py-1 px-3 font-weight-bold" id="pills-stock-tab" data-toggle="pill" href="#subtab-stock" role="tab">
                                        <i class="fa-solid fa-barcode mr-1 text-primary"></i>A) Catálogo (Stock)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link font-size-12 py-1 px-3 font-weight-bold" id="pills-dynamic-tab" data-toggle="pill" href="#subtab-dynamic" role="tab">
                                        <i class="fa-solid fa-box-open mr-1 text-warning"></i>B) Producto Libre / Servicio
                                    </a>
                                </li>
                                @endif
                                @if ($canGifts)
                                <li class="nav-item">
                                    <a class="nav-link {{ !$canProductos ? 'active' : '' }} font-size-12 py-1 px-3 font-weight-bold" id="pills-gift-tab" data-toggle="pill" href="#subtab-gift" role="tab">
                                        <i class="fa-solid fa-gift mr-1 text-success"></i>C) Regalos / Gifts ($0.00)
                                    </a>
                                </li>
                                @endif
                            </ul>

                            <div class="tab-content" id="pills-prod-content">
                                @if ($canProductos)
                                <!-- 4A: PRODUCTOS CON IMPACTO EN INVENTARIO -->
                                <div class="tab-pane fade show active" id="subtab-stock" role="tabpanel">
                                    <div class="form-group mb-2">
                                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">
                                            <i class="fa-solid fa-cart-arrow-down mr-1 text-primary"></i>Buscar Producto en Catálogo
                                        </label>
                                        <select id="posCatalogProductSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Escribe para buscar producto por nombre, código o IMEI --">
                                            <option value="">-- Escribe para buscar producto por nombre, código de barras o IMEI --</option>
                                            @foreach ($products as $prod)
                                                <option value="{{ $prod->id }}"
                                                    data-id="{{ $prod->id }}"
                                                    data-name="{{ $prod->product_name }}"
                                                    data-price="{{ $prod->selling_price }}"
                                                    data-stock="{{ $prod->stock_quantity }}"
                                                    data-code="{{ $prod->product_code }}"
                                                    data-imei="{{ $prod->imei }}"
                                                    {{ $prod->stock_quantity <= 0 ? 'disabled' : '' }}>
                                                    {{ $prod->product_name }} [{{ $prod->product_code }}] {{ $prod->imei ? 'IMEI: ' . $prod->imei : '' }} — ${{ number_format($prod->selling_price, 2) }} (Stock: {{ $prod->stock_quantity }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="input-group input-group-sm mb-2">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                                        <input type="text" id="posProductSearchInput" class="form-control" placeholder="Escanea código de barras, IMEI o escribe nombre...">
                                        <div class="input-group-append">
                                            <button type="button" id="btnSearchCatalogProduct" class="btn btn-primary font-weight-bold">
                                                Buscar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive border rounded" style="max-height: 220px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0 font-size-12">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Producto</th>
                                                    <th class="text-center">Stock</th>
                                                    <th class="text-right">Precio</th>
                                                    <th class="text-right">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="posCatalogProductsBody">
                                                @foreach ($products as $prod)
                                                    <tr>
                                                        <td><span class="badge badge-light border">{{ $prod->product_code }}</span></td>
                                                        <td>
                                                            <strong>{{ $prod->product_name }}</strong>
                                                            @if ($prod->imei)
                                                                <small class="d-block text-muted">IMEI: {{ $prod->imei }}</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-pill badge-{{ $prod->stock_quantity > 0 ? 'success' : 'danger' }}">
                                                                {{ $prod->stock_quantity }}
                                                            </span>
                                                        </td>
                                                        <td class="text-right font-weight-bold text-dark">${{ number_format($prod->selling_price, 2) }}</td>
                                                        <td class="text-right">
                                                            <button type="button"
                                                                class="btn btn-xs btn-primary btn-add-stock-product"
                                                                data-id="{{ $prod->id }}"
                                                                data-name="{{ $prod->product_name }}"
                                                                data-code="{{ $prod->product_code }}"
                                                                data-imei="{{ $prod->imei }}"
                                                                data-price="{{ $prod->selling_price }}"
                                                                data-stock="{{ $prod->stock_quantity }}"
                                                                {{ $prod->stock_quantity <= 0 ? 'disabled' : '' }}>
                                                                <i class="fa-solid fa-plus"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- 4B: PRODUCTO MANUAL / LIBRE (SIN IMPACTO EN INVENTARIO) -->
                                <div class="tab-pane fade" id="subtab-dynamic" role="tabpanel">
                                    <div class="p-3 rounded border bg-light">
                                        <h6 class="font-weight-bold text-dark mb-2 font-size-13">
                                            <i class="fa-solid fa-box-open text-warning mr-1"></i>Producto Libre / Mano de Obra / Accesorio Sin Código
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-5 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Nombre / Descripción *</label>
                                                <input type="text" id="dynProdName" class="form-control form-control-sm" placeholder="Ej. Mica Hidrogel / Reparación...">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Precio ($) *</label>
                                                <input type="number" step="0.01" min="0" id="dynProdPrice" class="form-control form-control-sm font-weight-bold" placeholder="0.00" value="0.00">
                                            </div>
                                            <div class="col-md-2 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Cantidad *</label>
                                                <input type="number" min="1" id="dynProdQty" class="form-control form-control-sm" value="1">
                                            </div>
                                            <div class="col-md-2 form-group mb-2">
                                                <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Estado</label>
                                                <select id="dynProdStatus" class="form-control form-control-sm">
                                                    <option value="Nuevo">Nuevo</option>
                                                    <option value="Seminuevo">Seminuevo</option>
                                                    <option value="Servicio">Servicio</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 text-muted text-uppercase mb-1">Marca</label>
                                                <input type="text" id="dynProdBrand" class="form-control form-control-sm" placeholder="Opcional...">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 text-muted text-uppercase mb-1">Modelo</label>
                                                <input type="text" id="dynProdModel" class="form-control form-control-sm" placeholder="Opcional...">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 text-muted text-uppercase mb-1">IMEI / Serie</label>
                                                <input type="text" id="dynProdImei" class="form-control form-control-sm" placeholder="Opcional...">
                                            </div>
                                            <div class="col-md-3 form-group mb-2">
                                                <label class="font-size-11 text-muted text-uppercase mb-1">Garantía</label>
                                                <input type="text" id="dynProdWarranty" class="form-control form-control-sm" placeholder="Ej. 30 días">
                                            </div>
                                            <div class="col-md-8 form-group mb-0">
                                                <input type="text" id="dynProdObs" class="form-control form-control-sm" placeholder="Observaciones o notas del producto libre...">
                                            </div>
                                            <div class="col-md-4 form-group mb-0">
                                                <button type="button" id="btnAddDynamicProductToTab" class="btn btn-warning btn-sm btn-block font-weight-bold shadow-sm">
                                                    <i class="fa-solid fa-plus mr-1"></i>Añadir Producto Libre
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if ($canGifts)
                                <!-- 4C: REGALOS / PROMOCIONALES DE PROVEEDOR ($0.00 CON DESCUENTO DE INVENTARIO POR SUCURSAL) -->
                                <div class="tab-pane fade {{ !$canProductos ? 'show active' : '' }}" id="subtab-gift" role="tabpanel">
                                    <div class="alert alert-light border py-2 px-3 mb-2 font-size-12">
                                        <i class="fa-solid fa-gift text-success mr-1"></i>
                                        Los regalos se agregan al ticket en <strong>$0.00</strong> y se descuentan automáticamente del inventario de esta sucursal al cerrar el cobro.
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
                                        <input type="text" id="posGiftSearchInput" class="form-control" placeholder="Buscar regalo por nombre, código, marca o proveedor...">
                                        <div class="input-group-append">
                                            <button type="button" id="btnSearchPosGift" class="btn btn-success font-weight-bold">
                                                Buscar Regalo
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive border rounded" style="max-height: 240px; overflow-y: auto;">
                                        <table class="table table-sm table-hover mb-0 font-size-12">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Regalo / Proveedor</th>
                                                    <th class="text-center">Stock Sucursal</th>
                                                    <th class="text-right">Ticket</th>
                                                    <th class="text-right">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="posGiftsTableBody">
                                                @forelse ($gifts as $g)
                                                    @php
                                                        $giftBranchStock = $g->stockInBranch($branchId);
                                                    @endphp
                                                    <tr>
                                                        <td><span class="badge badge-light border">{{ $g->code }}</span></td>
                                                        <td>
                                                            <strong>{{ $g->name }}</strong>
                                                            <small class="d-block text-muted">
                                                                {{ $g->brand ? $g->brand . ' • ' : '' }}Prov: {{ $g->supplier_display_name }}
                                                            </small>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-pill badge-success">{{ $giftBranchStock }}</span>
                                                        </td>
                                                        <td class="text-right font-weight-bold text-success">$0.00</td>
                                                        <td class="text-right">
                                                            <button type="button"
                                                                class="btn btn-xs btn-success btn-add-gift-item"
                                                                data-id="{{ $g->id }}"
                                                                data-name="{{ $g->name }}"
                                                                data-code="{{ $g->code }}"
                                                                data-supplier="{{ $g->supplier_display_name }}"
                                                                data-stock="{{ $giftBranchStock }}">
                                                                <i class="fa-solid fa-plus"></i> Agregar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">
                                                            No hay regalos disponibles con stock en esta sucursal.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if ($activeTab === 'none')
                        <div class="alert alert-warning text-center font-weight-bold py-4 mb-0" role="alert">
                            <i class="fa-solid fa-lock fa-2x d-block mb-2 text-warning"></i>
                            No tienes permisos asignados para operar en ninguna de las 4 secciones del Punto de Venta.
                            <br><small class="text-muted">Contacta a tu administrador para habilitar las secciones correspondientes (Financieras, Préstamos, Tandas o Productos).</small>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Carrito y Checkout de la Pestaña Activa -->
                <div class="col-lg-5 mt-3 mt-lg-0">
                    <div class="card cart-checkout-panel h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-primary font-size-12 px-2 py-1 mr-2" id="activeTabTitleBadge">Proceso #1</span>
                                <strong class="text-dark"><i class="las la-shopping-cart text-primary"></i> Cobro en Curso</strong>
                            </div>
                            <button type="button" id="btnClearCurrentTab" class="btn btn-xs btn-outline-danger font-weight-bold" title="Vaciar todos los artículos de esta pestaña">
                                <i class="fa-solid fa-trash-can mr-1"></i>Vaciar
                            </button>
                        </div>
                        <div class="card-body p-3">
                            <!-- Datos del Cliente para el Desglose y Ticket -->
                            <div class="card border mb-3 bg-light shadow-none">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap" style="gap: 4px;">
                                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-0">
                                            <i class="fa-solid fa-user-check text-primary mr-1"></i>Cliente para Ticket y Caja
                                        </label>
                                        <div class="d-flex align-items-center" style="gap: 4px;">
                                            @if (auth()->user()->isSuperAdmin() || auth()->user()->can('customer.create') || auth()->user()->can('customer.menu'))
                                                <a href="{{ route('customers.create') }}" target="_blank" id="btnPosNewCustomerLink" class="btn btn-xs btn-outline-primary font-weight-bold py-0 px-2" title="Dar de alta un nuevo cliente en catálogo">
                                                    <i class="fa-solid fa-user-plus mr-1"></i>Nuevo Cliente
                                                </a>
                                            @endif
                                            <button type="button" id="btnRefreshPosCustomers" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Recargar catálogo de clientes">
                                                <i class="fa-solid fa-rotate"></i>
                                            </button>
                                            @if ($canPosExternalCustomers)
                                                <div id="posExternalCustomerTriggerWrap" class="d-none">
                                                    <button type="button" id="btnTriggerExternalCustomer" class="btn btn-xs btn-warning text-dark font-weight-bold shadow-sm" disabled>
                                                        <i class="fa-solid fa-user-tag mr-1"></i>Cliente Externo
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($canPosExternalCustomers)
                                        <div id="posSelectedExternalBanner" class="alert alert-warning border-warning py-1 px-2 mb-2 d-none font-size-12">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge badge-dark mr-1">Externo</span>
                                                    <strong id="posSelectedExternalName"></strong>
                                                    <small class="text-muted ml-1" id="posSelectedExternalPhone"></small>
                                                </div>
                                                <div class="d-flex align-items-center" style="gap: 4px;">
                                                    <button type="button" id="btnChangeExternalCustomer" class="btn btn-xs btn-outline-dark py-0 px-1" title="Cambiar cliente externo">
                                                        Cambiar
                                                    </button>
                                                    <button type="button" id="btnClearExternalCustomer" class="btn btn-xs btn-outline-danger py-0 px-1" title="Quitar cliente externo">
                                                        &times;
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <select id="posCartCustomerSelect" class="form-control form-control-sm select2-autocomplete" data-placeholder="-- Buscar cliente registrado en catálogo --">
                                            <option value="">-- Buscar cliente registrado en catálogo --</option>
                                            @foreach ($customers as $c)
                                                <option value="{{ $c->id }}" data-name="{{ $c->tit_name }}" data-phone="{{ $c->tit_phone }}">
                                                    {{ $c->tit_name }} ({{ $c->tit_phone ?? 'Sin tel' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-7 pr-1">
                                            <input type="text" id="tabCustomerName" class="form-control form-control-sm" placeholder="O nombre mostrador...">
                                        </div>
                                        <div class="col-5 pl-1">
                                            <input type="text" id="tabCustomerPhone" class="form-control form-control-sm" placeholder="Teléfono...">
                                        </div>
                                    </div>
                                    <div id="posCustomerPendingNotesBox" class="mt-2 d-none"></div>
                                </div>
                            </div>

                            <!-- Resumen rápido y alertas de carrito -->
                            <div id="posCartSummaryBar" class="cart-summary-bar d-none mb-2"></div>
                            <div id="posCartFeedbackBanner" class="d-none mb-2"></div>

                            <!-- Lista organizada de Conceptos en Carrito -->
                            <div class="cart-items-scroll mb-3">
                                <div id="tabCartItemsBody" class="cart-items-list"></div>
                            </div>

                            <!-- Totales -->
                            <div class="cart-totals-card mb-2">
                                <div class="d-flex justify-content-between font-size-13 mb-1">
                                    <span class="text-muted">Subtotal Conceptos:</span>
                                    <strong id="tabSubtotalText" class="text-dark">$0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between font-size-13 mb-2">
                                    <span class="text-muted">Comisiones (Financieras / Apertura):</span>
                                    <strong class="text-info font-weight-bold" id="tabCommissionText">$0.00</strong>
                                </div>
                                <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="font-size-11 font-weight-bold text-uppercase text-muted d-block">Total de la Venta</span>
                                        <span class="badge badge-primary font-size-11">Caja Sucursal</span>
                                    </div>
                                    <div class="cart-grand-total" id="tabGrandTotalText">$0.00</div>
                                </div>
                            </div>

                            <!-- Método de Pago Visual Moderno (Detección automática de liquidación o saldo pendiente) -->
                            <div class="form-group mb-2">
                                <label class="font-size-11 font-weight-bold text-muted mb-1 text-uppercase">Método de Pago</label>
                                <div class="pay-methods-grid" id="paymentMethodBtnGroup">
                                    <div class="pay-method-btn active" data-method="efectivo">
                                        <i class="fa-solid fa-money-bill-wave text-success"></i>
                                        <span>Efectivo</span>
                                    </div>
                                    <div class="pay-method-btn" data-method="tarjeta">
                                        <i class="fa-solid fa-credit-card text-primary"></i>
                                        <span>Tarjeta</span>
                                    </div>
                                    <div class="pay-method-btn" data-method="transferencia">
                                        <i class="fa-solid fa-mobile-screen-button text-transfer"></i>
                                        <span>Transf.</span>
                                    </div>
                                    <div class="pay-method-btn" data-method="mixto">
                                        <i class="fa-solid fa-layer-group text-warning"></i>
                                        <span>Mixto</span>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DINÁMICA UNIFICADA POR MÉTODO DE PAGO -->

                            <!-- 1. Detalle para Efectivo (Campo único pre-cargado con el total) -->
                            <div id="posBoxEfectivo" class="p-2 rounded border mb-2 bg-light">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="posCashAmountInput" class="font-size-11 font-weight-bold text-success mb-0 text-uppercase">
                                            Efectivo Recibido / Monto a Pagar ($)
                                        </label>
                                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 font-size-10 font-weight-bold btn-pos-fill-total d-none" title="Restablecer al 100% del total">
                                            Liquidar 100%
                                        </button>
                                    </div>
                                    <input type="number" step="0.01" min="0" id="posCashAmountInput" class="form-control form-control-sm font-weight-bold text-success font-size-15" placeholder="0.00">
                                    <small class="text-muted font-size-10 d-block mt-1">
                                        Ingresa un monto mayor para calcular cambio o menor para dejar saldo pendiente.
                                    </small>
                                </div>
                                <div id="posCashChangeContainer">
                                    <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Cambio a Entregar</label>
                                    <input type="text" id="posChangeOutput" class="form-control form-control-sm cash-change-display" value="$0.00" readonly>
                                </div>
                            </div>

                            <!-- 2. Detalle para Tarjeta -->
                            <div id="posBoxTarjeta" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="row">
                                    <div class="col-7 pr-1">
                                        <div class="d-flex justify-content-between align-items-center mb-0">
                                            <label for="posCardAmountInput" class="font-size-11 font-weight-bold text-primary mb-0 text-uppercase">Monto Tarjeta ($)</label>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1 font-size-10 font-weight-bold btn-pos-fill-total d-none">100%</button>
                                        </div>
                                        <input type="number" step="0.01" min="0" id="posCardAmountInput" class="form-control form-control-sm font-weight-bold text-primary font-size-14 mt-1" placeholder="0.00">
                                    </div>
                                    <div class="col-5 pl-1">
                                        <label for="posCardRefInput" class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">No. Autoriz. / Voucher</label>
                                        <input type="text" id="posCardRefInput" class="form-control form-control-sm font-size-13 mt-1" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Detalle para Transferencia -->
                            <div id="posBoxTransferencia" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="row">
                                    <div class="col-7 pr-1">
                                        <div class="d-flex justify-content-between align-items-center mb-0">
                                            <label for="posTransferAmountInput" class="font-size-11 font-weight-bold text-transfer mb-0 text-uppercase">Monto Transf. ($)</label>
                                            <button type="button" class="btn btn-xs btn-outline-info py-0 px-1 font-size-10 font-weight-bold btn-pos-fill-total d-none">100%</button>
                                        </div>
                                        <input type="number" step="0.01" min="0" id="posTransferAmountInput" class="form-control form-control-sm font-weight-bold text-transfer font-size-14 mt-1" placeholder="0.00">
                                    </div>
                                    <div class="col-5 pl-1">
                                        <label for="posTransferRefInput" class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Folio SPEI / Rastreo</label>
                                        <input type="text" id="posTransferRefInput" class="form-control form-control-sm font-size-13 mt-1" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Detalle para Pago Mixto (soporta liquidación completa o abono inicial mixto + saldo pendiente) -->
                            <div id="posBoxMixto" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="font-weight-bold text-warning text-uppercase">
                                        <i class="fa-solid fa-sliders mr-1"></i>Captura de Pago Mixto:
                                    </small>
                                    <div class="d-flex align-items-center" style="gap: 6px;">
                                        <small class="text-muted font-size-11">Total: <strong id="lblMixTargetAmount" class="text-dark">$0.00</strong></small>
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0 px-1 font-size-10 font-weight-bold btn-pos-fill-total d-none">100%</button>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 pr-1">
                                        <label class="font-size-11 font-weight-bold text-success mb-0">Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" id="mixCashInput" class="form-control form-control-sm font-weight-bold text-success" value="0">
                                    </div>
                                    <div class="col-4 px-1">
                                        <label class="font-size-11 font-weight-bold text-primary mb-0">Tarjeta ($)</label>
                                        <input type="number" step="0.01" min="0" id="mixCardInput" class="form-control form-control-sm font-weight-bold text-primary" value="0">
                                    </div>
                                    <div class="col-4 pl-1">
                                        <label class="font-size-11 font-weight-bold text-transfer mb-0">Transf. ($)</label>
                                        <input type="number" step="0.01" min="0" id="mixTransferInput" class="form-control form-control-sm font-weight-bold text-transfer" value="0">
                                    </div>
                                </div>

                                <!-- Resumen en Tiempo Real de Pago Mixto -->
                                <div id="posMixFaltanteAlertContainer" class="mb-2"></div>

                                <!-- Efectivo Recibido y Cambio para la porción en efectivo (solo si cubre el total) -->
                                <div id="posMixCashDrawerRow" class="row mb-1 d-none">
                                    <div class="col-6 pr-1">
                                        <label class="font-size-11 font-weight-bold text-muted mb-0">Efectivo Recibido ($)</label>
                                        <input type="number" step="0.01" min="0" id="posMixCashReceivedInput" class="form-control form-control-sm font-weight-bold" placeholder="0.00">
                                    </div>
                                    <div class="col-6 pl-1">
                                        <label class="font-size-11 font-weight-bold text-muted mb-0">Cambio Efectivo</label>
                                        <input type="text" id="posMixChangeOutput" class="form-control form-control-sm cash-change-display" value="$0.00" readonly>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6 pr-1">
                                        <label class="font-size-11 text-muted mb-0">Ref. Tarjeta</label>
                                        <input type="text" id="mixCardRefInput" class="form-control form-control-sm" placeholder="Voucher/Auth">
                                    </div>
                                    <div class="col-6 pl-1">
                                        <label class="font-size-11 text-muted mb-0">Ref. SPEI</label>
                                        <input type="text" id="mixTransferRefInput" class="form-control form-control-sm" placeholder="Folio rastreo">
                                    </div>
                                </div>
                            </div>

                            <!-- Indicador Automático en Tiempo Real: Liquidación vs Saldo Pendiente Restante -->
                            <div id="posAutoPaymentStatusBox" class="mb-2 d-none"></div>

                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" id="posOpenDrawerCheck" checked>
                                <label class="custom-control-label font-size-12 font-weight-bold text-dark" for="posOpenDrawerCheck">
                                    <i class="fa-solid fa-cash-register text-success mr-1"></i>Abrir cajón físico automáticamente en cobros con efectivo
                                </label>
                            </div>
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="posAutoPrintCheck" checked>
                                <label class="custom-control-label font-size-12 font-weight-bold text-dark" for="posAutoPrintCheck">
                                    <i class="fa-solid fa-print text-primary mr-1"></i>Imprimir ticket automáticamente al cobrar (desmarcar si no hay papel/impresora)
                                </label>
                            </div>

                            <button type="button" id="btnCompleteUnifiedSale" class="btn btn-checkout-unified btn-block">
                                <i class="fa-solid fa-check-double mr-2"></i>Cerrar Cobro y Generar Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial de Tickets del Punto de Venta (Reimpresión / Vista Previa WhatsApp) -->
<div class="modal fade" id="posHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title font-weight-bold text-white mb-0">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>Historial de Tickets / Movimientos del POS
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Fecha</label>
                        <input type="date" id="posHistoryDateInput" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-1">Buscar Folio / Cliente / Teléfono</label>
                        <input type="text" id="posHistorySearchInput" class="form-control form-control-sm" placeholder="Ej. MOV-2026... o nombre del cliente">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="button" id="btnFilterPosHistory" class="btn btn-primary btn-sm btn-block font-weight-bold">
                            <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar Tickets
                        </button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 430px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0 font-size-12">
                        <thead class="bg-light">
                            <tr>
                                <th>Folio</th>
                                <th>Fecha / Hora</th>
                                <th>Cliente</th>
                                <th>Conceptos</th>
                                <th>Método</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="posHistoryTableBody">
                            @forelse ($recentTransactions as $rTx)
                                <tr>
                                    <td>
                                        <span class="badge badge-dark">{{ $rTx->transaction_code }}</span>
                                        @if($rTx->status === 'cancelado')
                                            <span class="badge badge-danger ml-1">Cancelado</span>
                                        @endif
                                    </td>
                                    <td>{{ $rTx->created_at ? $rTx->created_at->format('d/m/Y H:i') : '' }}</td>
                                    <td class="font-weight-bold">
                                        {{ $rTx->customer_name ?: 'Cliente Mostrador' }}
                                        @if ($rTx->external_customer_id)
                                            <span class="badge badge-warning text-dark font-size-10 ml-1">Externo</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($rTx->concepts_summary ?: '-', 60) }}</td>
                                    <td>{{ strtoupper((string) $rTx->payment_method) }}</td>
                                    <td class="text-right font-weight-bold">${{ number_format((float) $rTx->total_amount, 2) }}</td>
                                    <td class="text-right text-nowrap">
                                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold mr-1" onclick="window.openTicketPreviewModal('{{ route('central-pos.receipt', $rTx->id, false) }}', 'Ticket {{ $rTx->transaction_code }}')">
                                            <i class="fa-solid fa-eye mr-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-xs btn-dark font-weight-bold" onclick="window.printTicketDirectly('{{ route('central-pos.receipt', ['transaction' => $rTx->id, 'skip_drawer' => 1], false) }}')">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Sin movimientos recientes registrados hoy en esta sucursal.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canPosExternalCustomers)
<!-- Modal Captura / Selección de Cliente Externo (exclusivo para pagos de Financieras) -->
<div class="modal fade" id="posExternalCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning py-2">
                <h6 class="modal-title font-weight-bold text-dark mb-0">
                    <i class="fa-solid fa-user-tag mr-2"></i>Cliente Externo para Pago de Financiera
                </h6>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold py-1 px-3 font-size-13" data-toggle="pill" href="#posExtPaneNew" role="tab">
                            <i class="fa-solid fa-user-plus mr-1"></i>Nuevo Cliente Externo
                        </a>
                    </li>
                    <li class="nav-item ml-2">
                        <a class="nav-link font-weight-bold py-1 px-3 font-size-13" data-toggle="pill" href="#posExtPaneExisting" role="tab">
                            <i class="fa-solid fa-address-book mr-1"></i>Seleccionar Existente
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="posExtPaneNew" role="tabpanel">
                        <div id="posExtFormErrorBox" class="alert alert-danger py-2 px-3 d-none font-size-12"></div>
                        <form id="posExternalCustomerForm">
                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="tit_name" id="extTitName" class="form-control form-control-sm" required maxlength="100">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" name="tit_phone" id="extTitPhone" class="form-control form-control-sm" required maxlength="15">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-size-12 mb-1">Teléfono alterno</label>
                                    <input type="text" name="alternate_phone" id="extAlternatePhone" class="form-control form-control-sm" maxlength="15">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="tit_email" id="extTitEmail" class="form-control form-control-sm" required maxlength="100">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Facebook <span class="text-danger">*</span></label>
                                    <input type="text" name="tit_facebook" id="extTitFacebook" class="form-control form-control-sm" required maxlength="255">
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Status <span class="text-danger">*</span></label>
                                    <select name="tit_status" id="extTitStatus" class="form-control form-control-sm" required>
                                        @foreach ($extCustomerStatusOptions as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Ciudad <span class="text-danger">*</span></label>
                                    <input type="text" name="tit_city" id="extTitCity" class="form-control form-control-sm" required maxlength="120">
                                </div>
                                <div class="col-md-8 form-group mb-2">
                                    <label class="font-size-12 font-weight-bold mb-1">Dirección <span class="text-danger">*</span></label>
                                    <input type="text" name="tit_address" id="extTitAddress" class="form-control form-control-sm" required maxlength="999">
                                </div>
                                <div class="col-12 mt-1 mb-2">
                                    <a class="font-size-12 text-primary font-weight-bold" data-toggle="collapse" href="#posExtOptionalFields">
                                        <i class="fa-solid fa-sliders mr-1"></i>Campos adicionales opcionales (empleo, ubicación, notas)
                                    </a>
                                    <div class="collapse mt-2" id="posExtOptionalFields">
                                        <div class="row">
                                            <div class="col-md-4 form-group mb-2">
                                                <label class="font-size-12 mb-1">Lugar de trabajo</label>
                                                <input type="text" name="tit_work" class="form-control form-control-sm" maxlength="255">
                                            </div>
                                            <div class="col-md-4 form-group mb-2">
                                                <label class="font-size-12 mb-1">Puesto</label>
                                                <input type="text" name="position" class="form-control form-control-sm" maxlength="255">
                                            </div>
                                            <div class="col-md-4 form-group mb-2">
                                                <label class="font-size-12 mb-1">Ingreso mensual ($)</label>
                                                <input type="number" step="0.01" min="0" name="monthly_income" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-6 form-group mb-2">
                                                <label class="font-size-12 mb-1">Link de ubicación</label>
                                                <input type="text" name="tit_link_location" class="form-control form-control-sm" maxlength="999">
                                            </div>
                                            <div class="col-md-6 form-group mb-2">
                                                <label class="font-size-12 mb-1">Notas</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" maxlength="1000">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end border-top pt-2 mt-2" style="gap: 8px;">
                                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancelar</button>
                                <button type="submit" id="btnSaveExternalCustomerPos" class="btn btn-sm btn-warning text-dark font-weight-bold shadow-sm">
                                    <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar en Catálogo Externo y Asociar al Cobro
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="posExtPaneExisting" role="tabpanel">
                        <div class="form-group mb-3">
                            <label class="font-size-12 font-weight-bold">Seleccionar Cliente Externo Activo</label>
                            <select id="posModalExtCustomerSelect" class="form-control form-control-sm">
                                <option value="">-- Seleccionar del catálogo externo --</option>
                                @foreach ($externalCustomers as $ec)
                                    <option value="{{ $ec->id }}" data-name="{{ $ec->tit_name }}" data-phone="{{ $ec->tit_phone }}">
                                        {{ $ec->tit_name }} ({{ $ec->tit_phone }}) — {{ $ec->tit_city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end border-top pt-2" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancelar</button>
                            <button type="button" id="btnUseSelectedExternalCustomer" class="btn btn-sm btn-primary font-weight-bold">
                                <i class="fa-solid fa-check mr-1"></i>Usar Cliente Externo Seleccionado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
// Función global para el botón independiente de apertura física de cajón USB
window.triggerPhysicalCashDrawer = async function () {
    if (window.ZyaPosHardware) {
        await ZyaPosHardware.manualOpenUsbDrawer();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'zya_central_pos_tabs_v1_{{ auth()->id() ?? 0 }}_{{ $branchId ?? 0 }}';
    const CAN_POS_EXTERNAL_CUSTOMERS = @json((bool) $canPosExternalCustomers);
    const CAN_CREATE_CUSTOMERS = @json((bool) (auth()->user()->isSuperAdmin() || auth()->user()->can('customer.create') || auth()->user()->can('customer.menu')));
    const CUSTOMERS_CREATE_URL = @json(route('customers.create'));
    const CUSTOMERS_LOOKUP_URL = @json(route('central-pos.lookup-customers', [], false));

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.getAttribute('content')) ? meta.getAttribute('content') : '{{ csrf_token() }}';
    }

    function normalizePosUrl(rawUrl) {
        if (!rawUrl || typeof rawUrl !== 'string') return '';
        if (rawUrl.startsWith('/')) return rawUrl;
        try {
            const parsed = new URL(rawUrl, window.location.origin);
            if (parsed.pathname && parsed.pathname.startsWith('/central-pos')) {
                return parsed.pathname + parsed.search + parsed.hash;
            }
            if (parsed.origin === window.location.origin || parsed.hostname === window.location.hostname) {
                return parsed.pathname + parsed.search + parsed.hash;
            }
        } catch (e) {}
        return rawUrl;
    }

    function getTicketPaperMode() {
        if (window.zyaTicketStorage && typeof window.zyaTicketStorage.getPaperMode === 'function') {
            return window.zyaTicketStorage.getPaperMode();
        }
        try {
            const mode = localStorage.getItem('zya_ticket_paper_mode');
            if (mode === '80' || mode === '58' || mode === 'letter') return mode;
            return localStorage.getItem('zya_ticket_width') === '58' ? '58' : '80';
        } catch (e) {
            return '80';
        }
    }

    /**
     * Ejecuta peticiones JSON en el POS sobre el mismo origen con timeout y parseo seguro de errores HTTP/HTML.
     */
    function posFetchJson(rawUrl, options = {}) {
        const method = options.method || 'GET';
        const timeoutMs = options.timeoutMs || 20000;
        const headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, options.headers || {});

        if (method !== 'GET' && method !== 'HEAD') {
            headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            headers['X-CSRF-TOKEN'] = headers['X-CSRF-TOKEN'] || getCsrfToken();
        }

        const hasAbort = typeof AbortController !== 'undefined';
        const controller = hasAbort ? new AbortController() : null;
        const timerId = (controller && timeoutMs > 0)
            ? setTimeout(() => controller.abort(), timeoutMs)
            : null;

        const fetchOpts = {
            method,
            headers,
            credentials: 'same-origin',
        };
        if (options.json !== undefined) {
            fetchOpts.body = JSON.stringify(options.json);
        } else if (options.body !== undefined) {
            fetchOpts.body = options.body;
        }
        if (controller) {
            fetchOpts.signal = controller.signal;
        }

        return fetch(normalizePosUrl(rawUrl), fetchOpts)
            .then(async (r) => {
                if (r.redirected && r.url && r.url.includes('/login')) {
                    throw new Error('Tu sesión expiró. Recarga la página e inicia sesión nuevamente.');
                }

                let data = null;
                if (typeof r.text === 'function') {
                    const rawText = await r.text();
                    try {
                        data = rawText ? JSON.parse(rawText) : null;
                    } catch (_) {
                        data = null;
                    }
                } else if (typeof r.json === 'function') {
                    data = await r.json().catch(() => null);
                }

                if (!r.ok || !data) {
                    if (r.status === 419) {
                        throw new Error('La sesión o token de seguridad expiró (419). Recarga la página e intenta nuevamente.');
                    }
                    const validationList = (data && data.errors && typeof data.errors === 'object')
                        ? Object.values(data.errors).flat().filter(Boolean)
                        : [];
                    if (validationList.length) {
                        const err = new Error(validationList[0]);
                        err.validationMessages = validationList;
                        throw err;
                    }
                    if (r.ok && !data) {
                        throw new Error('El servidor devolvió una respuesta inesperada (no JSON). Si tu sesión expiró, recarga la página e intenta de nuevo.');
                    }
                    throw new Error(
                        (data && data.message)
                        || (r.status >= 500
                            ? 'Error interno del servidor al procesar la solicitud (' + r.status + '). Revisa el registro de errores o ejecuta las migraciones pendientes.'
                            : 'Error al comunicarse con el servidor (' + r.status + ').')
                    );
                }

                return data;
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') {
                    throw new Error('La solicitud tardó demasiado tiempo en responder. Verifica la conexión o consulta "Tickets Recientes" antes de reintentar.');
                }
                throw err;
            })
            .finally(() => {
                if (timerId !== null) {
                    clearTimeout(timerId);
                }
            });
    }

    let state = {
        activeTabId: 'tab-1',
        nextTabNumber: 2,
        tabs: [
            {
                id: 'tab-1',
                name: 'Proceso #1',
                customerId: '',
                externalCustomerId: '',
                customerName: '',
                customerPhone: '',
                paymentMethod: 'efectivo',
                cashAmount: '',
                cardAmount: '',
                transferAmount: '',
                cashTouched: false,
                cardTouched: false,
                transferTouched: false,
                mixTouched: false,
                mixCash: 0,
                mixCard: 0,
                mixTransfer: 0,
                mixCashReceived: '',
                items: []
            }
        ]
    };

    function normalizeTextKey(val) {
        return String(val ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
    }

    function findMatchingCartIndex(items, candidate) {
        if (!Array.isArray(items) || !candidate || !candidate.section) return -1;
        const sec = candidate.section;

        return items.findIndex((it) => {
            if (!it || it.section !== sec) return false;

            if (sec === 'producto_inventario') {
                return String(it.product_id || '') !== '' && String(it.product_id) === String(candidate.product_id || '');
            }
            if (sec === 'gift') {
                return String(it.gift_id || '') !== '' && String(it.gift_id) === String(candidate.gift_id || '');
            }
            if (sec === 'producto_dinamico') {
                const itImei = normalizeTextKey(it.imei);
                const candImei = normalizeTextKey(candidate.imei);
                if (candImei !== '' || itImei !== '') {
                    return candImei !== '' && itImei === candImei;
                }
                const itName = normalizeTextKey(it.name || it.title);
                const candName = normalizeTextKey(candidate.name || candidate.title);
                return itName !== ''
                    && itName === candName
                    && normalizeTextKey(it.category_status) === normalizeTextKey(candidate.category_status)
                    && normalizeTextKey(it.brand) === normalizeTextKey(candidate.brand)
                    && normalizeTextKey(it.model) === normalizeTextKey(candidate.model)
                    && Math.abs((parseFloat(it.amount) || 0) - (parseFloat(candidate.amount) || 0)) < 0.005;
            }
            if (sec === 'financiera_abono') {
                return String(it.sale_id || '') !== '' && String(it.sale_id) === String(candidate.sale_id || '');
            }
            if (sec === 'nota_abono') {
                return String(it.order_id || '') !== '' && String(it.order_id) === String(candidate.order_id || '');
            }
            if (sec === 'financiera_externa') {
                return String(it.financiera_id || '') === String(candidate.financiera_id || '')
                    && normalizeTextKey(it.tag_contrato) === normalizeTextKey(candidate.tag_contrato);
            }
            if (sec === 'prestamo_abono') {
                return String(it.installment_id || '') !== '' && String(it.installment_id) === String(candidate.installment_id || '');
            }
            if (sec === 'tanda_abono') {
                return String(it.tanda_id || '') === String(candidate.tanda_id || '')
                    && normalizeTextKey(it.participant_id || it.customer_name) === normalizeTextKey(candidate.participant_id || candidate.customer_name)
                    && String(it.period_number || '1') === String(candidate.period_number || '1');
            }
            return false;
        });
    }

    function consolidateTabItems(tab) {
        if (!tab || !Array.isArray(tab.items) || tab.items.length <= 1) return;
        const consolidated = [];
        tab.items.forEach((rawItem) => {
            if (!rawItem) return;
            const item = Object.assign({}, rawItem);
            const matchIdx = findMatchingCartIndex(consolidated, item);
            if (matchIdx === -1) {
                consolidated.push(item);
                return;
            }
            const existing = consolidated[matchIdx];
            if (item.section === 'producto_inventario' || item.section === 'gift' || (item.section === 'producto_dinamico' && !normalizeTextKey(item.imei))) {
                const addQty = Math.max(1, parseInt(item.quantity) || 1);
                let nextQty = Math.max(1, parseInt(existing.quantity) || 1) + addQty;
                const maxStockRaw = existing.max_stock !== undefined ? existing.max_stock : item.max_stock;
                const maxStock = (maxStockRaw !== undefined && maxStockRaw !== null && maxStockRaw !== '') ? parseInt(maxStockRaw) : null;
                if (maxStock !== null && !isNaN(maxStock) && maxStock > 0) {
                    nextQty = Math.min(nextQty, maxStock);
                }
                existing.quantity = nextQty;
                if (existing.max_stock === undefined && item.max_stock !== undefined) {
                    existing.max_stock = item.max_stock;
                }
            }
        });
        tab.items = consolidated;
    }

    function normalizeTabPaymentDefaults(t) {
        if (!t) return;
        if (!Array.isArray(t.items)) t.items = [];
        if (!t.paymentMethod) t.paymentMethod = 'efectivo';
        if (t.externalCustomerId === undefined) t.externalCustomerId = '';
        if (t.cashAmount === undefined) t.cashAmount = '';
        if (t.cardAmount === undefined) t.cardAmount = '';
        if (t.transferAmount === undefined) t.transferAmount = '';
        if (t.mixCashReceived === undefined) t.mixCashReceived = '';
        if (t.cashTouched === undefined) t.cashTouched = false;
        if (t.cardTouched === undefined) t.cardTouched = false;
        if (t.transferTouched === undefined) t.transferTouched = false;
        if (t.mixTouched === undefined) t.mixTouched = false;
        // Limpiar banderas heredadas del flujo anterior con toggle manual
        delete t.isPartial;
        delete t.paidToday;
        delete t.cashReceived;
    }

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed && Array.isArray(parsed.tabs) && parsed.tabs.length > 0) {
                parsed.tabs.forEach(t => {
                    normalizeTabPaymentDefaults(t);
                    consolidateTabItems(t);
                });
                state = parsed;
            }
        }
    } catch (e) {}

    function saveState() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function getActiveTab() {
        const tab = state.tabs.find(t => t.id === state.activeTabId) || state.tabs[0];
        if (tab) {
            normalizeTabPaymentDefaults(tab);
        }
        return tab;
    }

    function tabHasProductConcept(tab) {
        return Boolean(
            tab
            && Array.isArray(tab.items)
            && tab.items.some(it => it.section === 'producto_inventario' || it.section === 'producto_dinamico')
        );
    }

    function roundMoney(value) {
        const num = Number(value);
        if (!Number.isFinite(num)) return 0;
        return Math.round((num + Number.EPSILON) * 100) / 100;
    }

    function normalizeWalkinText(val) {
        return String(val ?? '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ');
    }

    function isGenericCustomerName(name) {
        const norm = normalizeWalkinText(name);
        if (!norm) return true;
        return [
            'cliente mostrador',
            'mostrador',
            'publico general',
            'publico en general',
            'venta al publico',
            'venta publico',
            'cliente general',
            'general',
            'sin nombre',
            'anonimo',
        ].includes(norm);
    }

    function hasRegisteredSpecificCustomer(tab) {
        if (!tab || !tab.customerId || tab.externalCustomerId) {
            return false;
        }
        const selectEl = document.getElementById('posCartCustomerSelect');
        let optName = tab.customerName || '';
        if (selectEl) {
            const opt = Array.from(selectEl.options).find(o => String(o.value) === String(tab.customerId));
            if (!opt) {
                return false;
            }
            optName = opt.dataset.name || opt.textContent || optName;
        }
        return !isGenericCustomerName(optName) && !isGenericCustomerName(tab.customerName);
    }

    function tabHasFinancieraConcept(tab) {
        return Boolean(
            tab
            && Array.isArray(tab.items)
            && tab.items.some(it => it.section === 'financiera_abono' || it.section === 'financiera_externa')
        );
    }

    function tabHasPendingNoteAbonoConcept(tab) {
        return Boolean(
            tab
            && Array.isArray(tab.items)
            && tab.items.some(it => it.section === 'nota_abono')
        );
    }

    function calculateTabTotals(tab) {
        let subtotal = 0;
        let commission = 0;
        tab.items.forEach(item => {
            const qty = Math.max(1, parseInt(item.quantity) || 1);
            const amt = parseFloat(item.amount) || 0;
            const comm = parseFloat(item.commission) || 0;
            if (item.section === 'prestamo_apertura' || item.section === 'gift') {
                commission += comm;
            } else {
                subtotal += (amt * qty);
                commission += comm;
            }
        });
        return {
            subtotal: subtotal,
            commission: commission,
            total: subtotal + commission
        };
    }

    function renderTabsBar() {
        const container = document.getElementById('posTabsContainer');
        container.innerHTML = '';

        state.tabs.forEach(tab => {
            const div = document.createElement('div');
            div.className = 'pos-tab-item ' + (tab.id === state.activeTabId ? 'active' : '');
            const totalUnits = tab.items.reduce((sum, it) => sum + Math.max(1, parseInt(it.quantity) || 1), 0);
            div.innerHTML = `
                <span class="tab-label"><i class="fa-solid fa-receipt mr-1 text-primary"></i>${escapeHtml(tab.name)}${tab.customerName ? ' (' + escapeHtml(tab.customerName.substring(0, 12)) + ')' : ''}</span>
                <span class="tab-badge" title="${tab.items.length} conceptos / ${totalUnits} pzas">${totalUnits}</span>
                ${state.tabs.length > 1 ? `<span class="tab-close" data-close-id="${tab.id}" title="Cerrar pestaña">&times;</span>` : ''}
            `;

            div.addEventListener('click', function (e) {
                const closeTarget = e.target.closest('[data-close-id]');
                if (closeTarget) {
                    e.stopPropagation();
                    removeTab(closeTarget.dataset.closeId);
                    return;
                }
                state.activeTabId = tab.id;
                saveState();
                renderAll();
            });

            container.appendChild(div);
        });

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-sm btn-primary btn-new-tab shadow-sm';
        addBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Nuevo Proceso';
        addBtn.addEventListener('click', addNewTab);
        container.appendChild(addBtn);
    }

    function addNewTab() {
        const num = state.nextTabNumber++;
        const newId = 'tab-' + Date.now();
        state.tabs.push({
            id: newId,
            name: 'Proceso #' + num,
            customerId: '',
            externalCustomerId: '',
            customerName: '',
            customerPhone: '',
            paymentMethod: 'efectivo',
            cashAmount: '',
            cardAmount: '',
            transferAmount: '',
            cashTouched: false,
            cardTouched: false,
            transferTouched: false,
            mixTouched: false,
            mixCash: 0,
            mixCard: 0,
            mixTransfer: 0,
            mixCashReceived: '',
            items: []
        });
        state.activeTabId = newId;
        saveState();
        renderAll();
    }

    function removeTab(tabId) {
        if (state.tabs.length <= 1) return;
        state.tabs = state.tabs.filter(t => t.id !== tabId);
        if (!state.tabs.some(t => t.id === state.activeTabId)) {
            state.activeTabId = state.tabs[0].id;
        }
        saveState();
        renderAll();
    }

    const pendingNotesBox = document.getElementById('posCustomerPendingNotesBox');
    let pendingNotesDebounceTimer = null;
    let pendingNotesRequestSeq = 0;
    let lastPendingNotesKey = '';
    let lastPendingNotesList = [];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncPendingNotesCartButtons(tab) {
        if (!pendingNotesBox || !tab || !Array.isArray(tab.items)) return;
        const inCartIds = new Set(
            tab.items
                .filter(it => it && it.section === 'nota_abono' && it.order_id)
                .map(it => String(it.order_id))
        );
        pendingNotesBox.querySelectorAll('.btn-add-pending-note-abono').forEach(btn => {
            const oid = String(btn.dataset.orderId || '');
            const dueFmt = btn.dataset.dueFmt || '0.00';
            const inCart = oid !== '' && inCartIds.has(oid);
            btn.classList.toggle('btn-success', inCart);
            btn.classList.toggle('btn-primary', !inCart);
            btn.title = inCart ? 'Este abono ya está en el carrito actual' : 'Agregar abono de lo pendiente al carrito';
            btn.innerHTML = inCart
                ? '<i class="fa-solid fa-check mr-1"></i>Abono en carrito'
                : `<i class="fa-solid fa-cart-plus mr-1"></i>Añadir abono ($${escapeHtml(dueFmt)})`;
        });
    }

    function renderCustomerPendingNotesBox(data, options = {}) {
        if (!pendingNotesBox) return;

        if (options.hidden) {
            lastPendingNotesList = [];
            pendingNotesBox.classList.add('d-none');
            pendingNotesBox.innerHTML = '';
            return;
        }

        pendingNotesBox.classList.remove('d-none');

        if (options.loading) {
            lastPendingNotesList = [];
            pendingNotesBox.innerHTML = `
                <div class="font-size-11 text-muted d-flex align-items-center py-1">
                    <i class="fa-solid fa-spinner fa-spin mr-1 text-primary"></i> Verificando notas pendientes del cliente...
                </div>
            `;
            return;
        }

        if (options.error) {
            lastPendingNotesList = [];
            pendingNotesBox.innerHTML = `
                <div class="font-size-11 text-muted py-1">
                    <i class="fa-solid fa-circle-info mr-1"></i>No se pudieron consultar las notas pendientes.
                </div>
            `;
            return;
        }

        if (!data || !data.searched) {
            lastPendingNotesList = [];
            pendingNotesBox.classList.add('d-none');
            pendingNotesBox.innerHTML = '';
            return;
        }

        if (!data.has_pending_notes || !Array.isArray(data.pending_notes) || data.pending_notes.length === 0) {
            lastPendingNotesList = [];
            pendingNotesBox.innerHTML = `
                <div class="d-flex justify-content-between align-items-center bg-white rounded border px-2 py-1">
                    <span class="font-size-11 text-success font-weight-bold">
                        <i class="fa-solid fa-circle-check mr-1"></i>Cliente sin notas pendientes con saldo.
                    </span>
                    <button type="button" class="btn btn-xs btn-link text-muted p-0 btn-refresh-pending-notes" title="Volver a verificar notas pendientes">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            `;
            return;
        }

        lastPendingNotesList = data.pending_notes.slice();
        const activeTab = getActiveTab();
        const inCartIds = new Set(
            (activeTab && Array.isArray(activeTab.items) ? activeTab.items : [])
                .filter(it => it && it.section === 'nota_abono' && it.order_id)
                .map(it => String(it.order_id))
        );

        const totalDueFormatted = Number(data.total_due || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rowsHtml = data.pending_notes.map(note => {
            const totalFmt = Number(note.total || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const payFmt = Number(note.pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dueFmt = Number(note.due || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const alreadyInCart = inCartIds.has(String(note.id));
            const actionsHtml = `
                <div class="mt-2 pt-1 border-top d-flex justify-content-end flex-wrap" style="gap: 6px;">
                    <button type="button"
                        class="btn btn-xs ${alreadyInCart ? 'btn-success' : 'btn-primary'} font-weight-bold py-1 px-2 font-size-11 btn-add-pending-note-abono"
                        data-order-id="${Number(note.id)}"
                        data-due-fmt="${escapeHtml(dueFmt)}"
                        title="${alreadyInCart ? 'Este abono ya está en el carrito actual' : 'Agregar abono de lo pendiente al carrito'}">
                        <i class="fa-solid ${alreadyInCart ? 'fa-check' : 'fa-cart-plus'} mr-1"></i>${alreadyInCart ? 'Abono en carrito' : `Añadir abono ($${dueFmt})`}
                    </button>
                    ${note.pending_due_url ? `<a href="${escapeHtml(note.pending_due_url)}" target="_blank" class="btn btn-xs btn-warning text-dark font-weight-bold py-1 px-2 font-size-11"><i class="fa-solid fa-up-right-from-square mr-1"></i>Ir a Saldos pendientes</a>` : ''}
                </div>
            `;

            return `
                <div class="bg-white rounded border p-2 mt-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="font-size-12">
                            <strong class="text-dark">${escapeHtml(note.invoice_no)}</strong>
                            <span class="text-muted mx-1">&middot;</span>
                            <span class="text-muted">${escapeHtml(note.order_date_formatted)}</span>
                            <span class="badge badge-light border text-dark ml-1">${escapeHtml(note.branch_name)}</span>
                        </div>
                        <span class="badge badge-danger font-size-11 px-2">Resta: $${dueFmt}</span>
                    </div>
                    <div class="font-size-11 text-dark mt-1 text-truncate" title="${escapeHtml(note.concepts)}">
                        ${escapeHtml(note.concepts)}
                    </div>
                    <div class="font-size-11 text-muted">
                        Total: <strong>$${totalFmt}</strong> &middot; Pagado: <strong class="text-success">$${payFmt}</strong>
                    </div>
                    ${actionsHtml}
                </div>
            `;
        }).join('');

        pendingNotesBox.innerHTML = `
            <div class="alert alert-warning d-block border-warning mb-0 p-2 shadow-none">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="font-size-12 font-weight-bold text-dark">
                        <i class="fa-solid fa-triangle-exclamation text-danger mr-1"></i>
                        ¡Tiene ${Number(data.pending_notes_count)} nota(s) pendiente(s)!
                    </div>
                    <div class="d-flex align-items-center" style="gap: 6px;">
                        <span class="badge badge-danger font-size-11">Debe: $${totalDueFormatted}</span>
                        <button type="button" class="btn btn-xs btn-link text-dark p-0 btn-refresh-pending-notes" title="Actualizar notas pendientes">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>
                <div style="max-height: 205px; overflow-y: auto;">
                    ${rowsHtml}
                </div>
            </div>
        `;
    }

    function syncCustomerPendingNotes(tab, forceRefresh = false) {
        if (!pendingNotesBox || !tab) return;

        const cid = String(tab.customerId || '').trim();
        const rawName = String(tab.customerName || '').trim();
        const cname = rawName.toLowerCase() === 'cliente mostrador' ? '' : rawName;
        const cphone = String(tab.customerPhone || '').trim();
        const phoneDigits = cphone.replace(/\D+/g, '');

        const hasSearchCriteria = cid !== '' || cname.length >= 3 || phoneDigits.length >= 4;
        if (!hasSearchCriteria) {
            lastPendingNotesKey = '';
            pendingNotesRequestSeq++;
            renderCustomerPendingNotesBox(null, { hidden: true });
            return;
        }

        const key = `${cid}|${cname.toLowerCase()}|${phoneDigits}`;
        if (!forceRefresh && key === lastPendingNotesKey) {
            return;
        }
        lastPendingNotesKey = key;

        const mySeq = ++pendingNotesRequestSeq;
        renderCustomerPendingNotesBox(null, { loading: true });

        const params = new URLSearchParams();
        if (cid !== '') params.set('customer_id', cid);
        if (cname !== '') params.set('customer_name', cname);
        if (cphone !== '') params.set('customer_phone', cphone);

        posFetchJson(`{{ route('central-pos.lookup-pending-notes', [], false) }}?${params.toString()}`, { timeoutMs: 12000 })
            .then(data => {
                if (mySeq !== pendingNotesRequestSeq) return;
                renderCustomerPendingNotesBox(data);
            })
            .catch(() => {
                if (mySeq !== pendingNotesRequestSeq) return;
                renderCustomerPendingNotesBox(null, { error: true });
            });
    }

    function scheduleCustomerPendingNotesCheck(tab) {
        clearTimeout(pendingNotesDebounceTimer);
        pendingNotesDebounceTimer = setTimeout(() => {
            syncCustomerPendingNotes(tab, true);
        }, 320);
    }

    if (pendingNotesBox) {
        pendingNotesBox.addEventListener('click', function (e) {
            if (e.target.closest('.btn-refresh-pending-notes')) {
                syncCustomerPendingNotes(getActiveTab(), true);
                return;
            }

            const addBtn = e.target.closest('.btn-add-pending-note-abono');
            if (addBtn) {
                const oid = Number(addBtn.dataset.orderId || 0);
                const note = lastPendingNotesList.find(n => Number(n.id) === oid);
                if (!note || roundMoney(note.due) <= 0) {
                    showCartFeedback('warning', 'La nota pendiente seleccionada no tiene saldo disponible para abonar.');
                    return;
                }

                const tab = getActiveTab();
                const dueAmt = roundMoney(note.due);
                const subParts = [];
                if (note.branch_name) subParts.push(`Suc. ${note.branch_name}`);
                if (note.concepts) subParts.push(note.concepts);

                addItemToCurrentTab({
                    section: 'nota_abono',
                    order_id: oid,
                    invoice_no: String(note.invoice_no || ''),
                    title: `Abono Nota ${note.invoice_no}`,
                    subtitle: subParts.join(' | '),
                    customer_id: note.customer_id || (tab ? tab.customerId : '') || '',
                    customer_name: (tab && tab.customerName) ? tab.customerName : (note.customer_name || ''),
                    original_due: dueAmt,
                    max_amount: dueAmt,
                    amount: dueAmt,
                    commission: 0,
                    quantity: 1,
                });
            }
        });

        window.addEventListener('focus', function () {
            const tab = getActiveTab();
            if (tab && (tab.customerId || (tab.customerName && tab.customerName.trim().length >= 3))) {
                syncCustomerPendingNotes(tab, true);
            }
        });
    }

    let highlightedCartIndex = -1;
    let cartFeedbackTimer = null;

    function showCartFeedback(type, message) {
        const banner = document.getElementById('posCartFeedbackBanner');
        if (!banner || !message) return;
        const palette = {
            added: 'alert-success border-success',
            merged: 'alert-info border-info',
            warning: 'alert-warning border-warning text-dark',
        };
        const icon = type === 'warning'
            ? 'fa-triangle-exclamation'
            : (type === 'merged' ? 'fa-layer-group' : 'fa-circle-check');

        banner.className = `alert ${palette[type] || palette.added} py-1 px-2 mb-2 font-size-11 font-weight-bold d-flex justify-content-between align-items-center shadow-none`;
        banner.innerHTML = `
            <span><i class="fa-solid ${icon} mr-1"></i>${escapeHtml(message)}</span>
            <button type="button" class="close font-size-14 ml-2 btn-dismiss-cart-feedback" aria-label="Cerrar" style="line-height: 1;">&times;</button>
        `;
        clearTimeout(cartFeedbackTimer);
        cartFeedbackTimer = setTimeout(() => {
            banner.className = 'd-none mb-2';
        }, 4000);
    }

    const cartFeedbackBannerEl = document.getElementById('posCartFeedbackBanner');
    if (cartFeedbackBannerEl) {
        cartFeedbackBannerEl.addEventListener('click', function (e) {
            if (e.target.closest('.btn-dismiss-cart-feedback')) {
                clearTimeout(cartFeedbackTimer);
                cartFeedbackBannerEl.className = 'd-none mb-2';
            }
        });
    }

    function formatCartMetaChips(item, commAmt, isEditableStock) {
        const chips = [];
        const seen = new Set();
        const pushChip = (label, extraClass = '') => {
            const text = String(label || '').trim();
            const norm = text.toLowerCase();
            if (!text || seen.has(norm)) return;
            seen.add(norm);
            chips.push(`<span class="cart-meta-chip ${extraClass}">${escapeHtml(text)}</span>`);
        };

        if (item.product_code) pushChip(`Cód: ${item.product_code}`);
        if (item.imei) pushChip(`IMEI: ${item.imei}`);
        if (item.brand) pushChip(`Marca: ${item.brand}`);
        if (item.model) pushChip(`Mod: ${item.model}`);
        if (item.warranty_time) pushChip(`Garantía: ${item.warranty_time}`);

        if (item.subtitle) {
            String(item.subtitle).split('|').forEach(part => {
                const clean = part.trim();
                const low = clean.toLowerCase();
                if (!clean || low === 'impacto real en inventario' || low === 'sin impacto en inventario') {
                    return;
                }
                pushChip(clean);
            });
        }

        if (isEditableStock && item.max_stock !== undefined && item.max_stock !== null && item.max_stock !== '') {
            pushChip(`Stock disp: ${item.max_stock}`, 'font-weight-bold text-primary');
        }
        if (item.section === 'nota_abono' && item.original_due !== undefined) {
            pushChip(`Saldo pendiente nota: $${roundMoney(item.original_due).toFixed(2)}`, 'font-weight-bold text-danger');
        }
        if (commAmt > 0) {
            pushChip(`+Comisión: $${commAmt.toFixed(2)}`, 'text-info font-weight-bold');
        }

        return chips.join('');
    }

    const CART_GROUPS = [
        { key: 'products_stock', sections: ['producto_inventario'], label: 'Productos de Catálogo', shortLabel: 'Catálogo', icon: 'fa-solid fa-box', color: '#2563eb' },
        { key: 'products_dynamic', sections: ['producto_dinamico'], label: 'Productos Libres y Servicios', shortLabel: 'Libres', icon: 'fa-solid fa-screwdriver-wrench', color: '#d97706' },
        { key: 'gifts', sections: ['gift'], label: 'Regalos / Cortesías ($0.00)', shortLabel: 'Regalos', icon: 'fa-solid fa-gift', color: '#059669' },
        { key: 'pending_notes', sections: ['nota_abono'], label: 'Abonos a Notas Pendientes', shortLabel: 'Notas Pend.', icon: 'fa-solid fa-file-invoice-dollar', color: '#dc2626' },
        { key: 'financieras', sections: ['financiera_abono', 'financiera_externa'], label: 'Abonos a Financieras', shortLabel: 'Financieras', icon: 'fa-solid fa-credit-card', color: '#4f46e5' },
        { key: 'loans', sections: ['prestamo_abono', 'prestamo_apertura'], label: 'Préstamos', shortLabel: 'Préstamos', icon: 'fa-solid fa-hand-holding-dollar', color: '#0d9488' },
        { key: 'tandas', sections: ['tanda_abono'], label: 'Tandas', shortLabel: 'Tandas', icon: 'fa-solid fa-people-group', color: '#7c3aed' },
    ];

    const SECTION_UI_META = {
        'financiera_abono': { badge: '<span class="badge badge-primary font-size-10">Financiera</span>', accent: '#4f46e5', group: 'financieras' },
        'financiera_externa': { badge: '<span class="badge badge-warning text-dark font-size-10">Fin. Externa</span>', accent: '#eab308', group: 'financieras' },
        'nota_abono': { badge: '<span class="badge badge-danger font-size-10">Abono Nota</span>', accent: '#dc2626', group: 'pending_notes' },
        'prestamo_abono': { badge: '<span class="badge badge-success font-size-10">Préstamo</span>', accent: '#0d9488', group: 'loans' },
        'prestamo_apertura': { badge: '<span class="badge badge-dark font-size-10">Apertura</span>', accent: '#0f766e', group: 'loans' },
        'tanda_abono': { badge: '<span class="badge text-white font-size-10" style="background:#8b5cf6;">Tanda</span>', accent: '#7c3aed', group: 'tandas' },
        'producto_inventario': { badge: '<span class="badge badge-primary font-size-10">Catálogo</span>', accent: '#2563eb', group: 'products_stock' },
        'producto_dinamico': { badge: '<span class="badge badge-warning text-dark font-size-10">Producto Libre</span>', accent: '#d97706', group: 'products_dynamic' },
        'gift': { badge: '<span class="badge badge-success font-size-10"><i class="fa-solid fa-gift mr-1"></i>Regalo ($0)</span>', accent: '#059669', group: 'gifts' },
    };

    function renderActiveCart() {
        const tab = getActiveTab();
        const hasFinItem = tabHasFinancieraConcept(tab);

        if ((!hasFinItem || !CAN_POS_EXTERNAL_CUSTOMERS) && tab.externalCustomerId) {
            tab.externalCustomerId = '';
            if (!hasFinItem) {
                tab.customerName = '';
                tab.customerPhone = '';
            }
            saveState();
        }

        document.getElementById('activeTabTitleBadge').textContent = tab.name;
        document.getElementById('tabCustomerName').value = tab.customerName || '';
        document.getElementById('tabCustomerPhone').value = tab.customerPhone || '';

        const extTriggerWrap = document.getElementById('posExternalCustomerTriggerWrap');
        const extTriggerBtn = document.getElementById('btnTriggerExternalCustomer');
        if (extTriggerWrap && extTriggerBtn) {
            extTriggerWrap.classList.toggle('d-none', !hasFinItem);
            extTriggerBtn.disabled = !hasFinItem;
        }

        const isUsingExtCustomer = Boolean(hasFinItem && tab.externalCustomerId);
        const extBanner = document.getElementById('posSelectedExternalBanner');
        const nameInput = document.getElementById('tabCustomerName');
        const phoneInput = document.getElementById('tabCustomerPhone');
        if (extBanner) {
            extBanner.classList.toggle('d-none', !isUsingExtCustomer);
            if (isUsingExtCustomer) {
                const extNameEl = document.getElementById('posSelectedExternalName');
                const extPhoneEl = document.getElementById('posSelectedExternalPhone');
                if (extNameEl) extNameEl.textContent = tab.customerName || 'Cliente Externo';
                if (extPhoneEl) extPhoneEl.textContent = tab.customerPhone ? `(${tab.customerPhone})` : '';
            }
        }
        if (nameInput) nameInput.readOnly = isUsingExtCustomer;
        if (phoneInput) phoneInput.readOnly = isUsingExtCustomer;

        const $custSelect = $('#posCartCustomerSelect');
        $custSelect.prop('disabled', isUsingExtCustomer);
        if (tab.customerId && !isUsingExtCustomer) {
            if ($custSelect.val() != tab.customerId) {
                $custSelect.val(tab.customerId).trigger('change.select2');
            }
        } else {
            if ($custSelect.val()) {
                $custSelect.val('').trigger('change.select2');
            }
        }

        syncCustomerPendingNotes(tab);

        const cartListEl = document.getElementById('tabCartItemsBody');
        const summaryBarEl = document.getElementById('posCartSummaryBar');
        cartListEl.innerHTML = '';

        if (tab.items.length === 0) {
            if (summaryBarEl) {
                summaryBarEl.classList.add('d-none');
                summaryBarEl.innerHTML = '';
            }
            cartListEl.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-basket-shopping fa-2x text-muted mb-2 d-block" style="opacity: 0.35;"></i>
                    <span class="font-weight-bold">No hay conceptos en este proceso</span>
                    <small class="d-block text-muted mt-1">Selecciona financieras, préstamos, tandas o productos del panel izquierdo.</small>
                </div>
            `;
        } else {
            const groupedBuckets = {};
            let totalUnitsCount = 0;

            tab.items.forEach((item, idx) => {
                const qty = Math.max(1, parseInt(item.quantity) || 1);
                totalUnitsCount += qty;
                const uiMeta = SECTION_UI_META[item.section] || { badge: '', accent: '#64748b', group: 'other' };
                const gKey = uiMeta.group;
                if (!groupedBuckets[gKey]) groupedBuckets[gKey] = [];
                groupedBuckets[gKey].push({ item, idx, uiMeta });
            });

            if (summaryBarEl) {
                const pillHtml = CART_GROUPS
                    .filter(g => groupedBuckets[g.key] && groupedBuckets[g.key].length > 0)
                    .map(g => {
                        const list = groupedBuckets[g.key];
                        const units = list.reduce((s, e) => s + Math.max(1, parseInt(e.item.quantity) || 1), 0);
                        return `<span class="cart-summary-pill"><span style="width:8px;height:8px;border-radius:50%;background:${g.color};display:inline-block;"></span>${g.shortLabel}: ${list.length}${units > list.length ? ' (' + units + 'u)' : ''}</span>`;
                    }).join('');

                summaryBarEl.classList.remove('d-none');
                summaryBarEl.innerHTML = `
                    <span class="font-weight-bold text-dark">
                        <i class="fa-solid fa-layer-group text-primary mr-1"></i>${tab.items.length} concepto${tab.items.length === 1 ? '' : 's'} &bull; ${totalUnitsCount} pza${totalUnitsCount === 1 ? '' : 's'}
                    </span>
                    <div class="cart-summary-pills">${pillHtml}</div>
                `;
            }

            let visualOrder = 0;
            const renderOrder = [...CART_GROUPS, { key: 'other', label: 'Otros Conceptos', icon: 'fa-solid fa-list', color: '#64748b' }];

            renderOrder.forEach(groupCfg => {
                const entries = groupedBuckets[groupCfg.key];
                if (!entries || entries.length === 0) return;

                const groupUnits = entries.reduce((s, e) => s + Math.max(1, parseInt(e.item.quantity) || 1), 0);
                const groupTotal = entries.reduce((s, e) => {
                    const q = Math.max(1, parseInt(e.item.quantity) || 1);
                    const isG = e.item.section === 'gift';
                    const u = isG ? 0 : (parseFloat(e.item.amount) || 0);
                    const l = (e.item.section === 'prestamo_apertura' || isG) ? 0 : (u * q);
                    return s + l + (parseFloat(e.item.commission) || 0);
                }, 0);

                const groupHeader = document.createElement('div');
                groupHeader.className = 'cart-group-header';
                groupHeader.style.borderLeft = `3px solid ${groupCfg.color}`;
                groupHeader.innerHTML = `
                    <span><i class="${groupCfg.icon} mr-1" style="color:${groupCfg.color};"></i>${escapeHtml(groupCfg.label)} (${groupUnits} pza${groupUnits === 1 ? '' : 's'})</span>
                    <span>$${groupTotal.toFixed(2)}</span>
                `;
                cartListEl.appendChild(groupHeader);

                entries.forEach(({ item, idx, uiMeta }) => {
                    visualOrder++;
                    const qty = Math.max(1, parseInt(item.quantity) || 1);
                    const isGift = (item.section === 'gift');
                    const unitAmt = isGift ? 0 : (parseFloat(item.amount) || 0);
                    const lineAmt = (item.section === 'prestamo_apertura' || isGift) ? 0 : (unitAmt * qty);
                    const commAmt = parseFloat(item.commission) || 0;
                    const lineTotalWithComm = lineAmt + commAmt;

                    const isProduct = (item.section === 'producto_inventario' || item.section === 'producto_dinamico');
                    const isFinanciera = (item.section === 'financiera_abono' || item.section === 'financiera_externa');
                    const isPendingNoteAbono = (item.section === 'nota_abono');
                    const isEditable = isProduct || isFinanciera || isPendingNoteAbono;
                    const isStockBounded = (item.section === 'gift' || item.section === 'producto_inventario');

                    const originalAmt = item.original_amount !== undefined ? parseFloat(item.original_amount) : unitAmt;
                    const hasDiscount = isProduct && (originalAmt > unitAmt);
                    const isFree = (isProduct && (unitAmt === 0)) || isGift;
                    const maxPendingDue = isPendingNoteAbono
                        ? roundMoney(item.original_due !== undefined ? item.original_due : (item.max_amount !== undefined ? item.max_amount : unitAmt))
                        : null;
                    const remainingNoteDue = isPendingNoteAbono && maxPendingDue !== null
                        ? Math.max(0, roundMoney(maxPendingDue - unitAmt))
                        : 0;

                    const maxStockNum = (isStockBounded && item.max_stock !== undefined && item.max_stock !== null && item.max_stock !== '')
                        ? parseInt(item.max_stock)
                        : null;
                    const atMaxStock = (maxStockNum !== null && !isNaN(maxStockNum) && qty >= maxStockNum);

                    const metaHtml = formatCartMetaChips(item, commAmt, isStockBounded);

                    let qtyControlHtml = '';
                    if (isProduct || isGift) {
                        qtyControlHtml = `
                            <div class="cart-qty-stepper" title="Ajustar cantidad">
                                <button type="button" class="btn-qty-minus" data-idx="${idx}" ${qty <= 1 ? 'disabled' : ''} title="Disminuir cantidad">&minus;</button>
                                <input type="number" min="1" ${maxStockNum ? `max="${maxStockNum}"` : ''} class="cart-item-qty-input" data-idx="${idx}" value="${qty}">
                                <button type="button" class="btn-qty-plus" data-idx="${idx}" ${atMaxStock ? 'disabled' : ''} title="${atMaxStock ? 'Stock máximo alcanzado' : 'Aumentar cantidad'}">+</button>
                            </div>
                        `;
                    } else {
                        qtyControlHtml = `<span class="badge badge-light border font-size-11 px-2 py-1">${qty} pza</span>`;
                    }

                    let priceInputHtml = '';
                    if (isGift) {
                        priceInputHtml = `<span class="badge badge-success font-size-11 px-2 py-1">Cortesía $0.00</span>`;
                    } else if (item.section === 'prestamo_apertura') {
                        priceInputHtml = `<span class="badge badge-light border font-size-11 px-2 py-1">${commAmt > 0 ? 'Solo comisión' : 'Sin cobro hoy'}</span>`;
                    } else if (isEditable) {
                        const minAttr = isPendingNoteAbono ? '0.01' : '0';
                        const maxAttr = (isPendingNoteAbono && maxPendingDue > 0) ? `max="${maxPendingDue.toFixed(2)}"` : '';
                        priceInputHtml = `
                            <div class="d-flex align-items-center" style="gap: 4px;">
                                <div class="input-group input-group-sm" style="width: 108px;">
                                    <div class="input-group-prepend"><span class="input-group-text p-1 font-size-11">$</span></div>
                                    <input type="number" step="0.01" min="${minAttr}" ${maxAttr} class="form-control form-control-sm text-right font-weight-bold cart-item-price-input" data-idx="${idx}" value="${unitAmt.toFixed(2)}" title="${isPendingNoteAbono ? 'Monto del abono a aplicar a esta nota' : 'Precio / Monto unitario editable'}">
                                </div>
                                <small class="text-muted font-size-10">${isProduct ? 'c/u' : 'abono'}</small>
                            </div>
                        `;
                    }

                    const statusBadge = isPendingNoteAbono
                        ? (remainingNoteDue < 0.01
                            ? '<span class="badge badge-success font-size-10 ml-1">Liquida nota</span>'
                            : `<span class="badge badge-warning text-dark font-size-10 ml-1">Restará en nota: $${remainingNoteDue.toFixed(2)}</span>`)
                        : (isFree && !isGift
                            ? '<span class="badge badge-success font-size-10 ml-1">Gratis ($0)</span>'
                            : (hasDiscount && !isFree ? `<span class="badge badge-soft-success border text-success font-size-10 ml-1">Desc: -$${(originalAmt - unitAmt).toFixed(2)}</span>` : ''));

                    const card = document.createElement('div');
                    card.className = 'cart-item-card' + (idx === highlightedCartIndex ? ' is-highlighted' : '');
                    card.style.setProperty('--cart-accent', uiMeta.accent);
                    card.innerHTML = `
                        <div class="cart-item-top">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex align-items-center flex-wrap mb-1" style="gap: 4px;">
                                    <span class="cart-item-index">#${visualOrder}</span>
                                    ${uiMeta.badge}
                                    ${statusBadge}
                                </div>
                                <div class="cart-item-title">${escapeHtml(item.title)}</div>
                                ${metaHtml ? `<div class="cart-item-meta">${metaHtml}</div>` : ''}
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item flex-shrink-0" data-idx="${idx}" title="Quitar concepto">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <div class="cart-item-controls">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                ${qtyControlHtml}
                                ${priceInputHtml}
                            </div>
                            <div class="cart-line-subtotal">
                                $${lineTotalWithComm.toFixed(2)}
                                ${qty > 1 && isProduct ? `<small class="d-block text-muted font-size-10 font-weight-normal">${qty} &times; $${unitAmt.toFixed(2)}</small>` : ''}
                            </div>
                        </div>
                    `;
                    cartListEl.appendChild(card);
                });
            });

            if (highlightedCartIndex >= 0) {
                const highlightedEl = cartListEl.querySelector('.cart-item-card.is-highlighted');
                if (highlightedEl && typeof highlightedEl.scrollIntoView === 'function') {
                    highlightedEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
                highlightedCartIndex = -1;
            }
        }

        const totals = calculateTabTotals(tab);
        const saleTotal = roundMoney(totals.total);
        document.getElementById('tabSubtotalText').textContent = '$' + roundMoney(totals.subtotal).toFixed(2);
        document.getElementById('tabCommissionText').textContent = '$' + roundMoney(totals.commission).toFixed(2);
        document.getElementById('tabGrandTotalText').textContent = '$' + saleTotal.toFixed(2);

        // Si el carrito queda en $0.00 o vacío, restablecer las banderas de edición manual de montos
        if (saleTotal <= 0.005) {
            tab.cashTouched = false;
            tab.cardTouched = false;
            tab.transferTouched = false;
            tab.mixTouched = false;
            tab.cashAmount = saleTotal > 0 ? saleTotal.toFixed(2) : '0.00';
            tab.cardAmount = saleTotal > 0 ? saleTotal.toFixed(2) : '0.00';
            tab.transferAmount = saleTotal > 0 ? saleTotal.toFixed(2) : '0.00';
            tab.mixCash = 0;
            tab.mixCard = 0;
            tab.mixTransfer = 0;
            tab.mixCashReceived = '';
        } else {
            const totalStr = saleTotal.toFixed(2);
            if (!tab.cashTouched) tab.cashAmount = totalStr;
            if (!tab.cardTouched) tab.cardAmount = totalStr;
            if (!tab.transferTouched) tab.transferAmount = totalStr;
            if (!tab.mixTouched) {
                tab.mixCash = saleTotal;
                tab.mixCard = 0;
                tab.mixTransfer = 0;
                tab.mixCashReceived = '';
            }
        }

        // Actualizar método de pago activo en la cuadrícula
        document.querySelectorAll('#paymentMethodBtnGroup [data-method]').forEach(lbl => {
            if (lbl.dataset.method === tab.paymentMethod) {
                lbl.classList.add('active');
            } else {
                lbl.classList.remove('active');
            }
        });

        // Mostrar u ocultar cajas según el método de pago activo y sincronizar valores pre-cargados
        $('#posBoxEfectivo, #posBoxTarjeta, #posBoxTransferencia, #posBoxMixto').addClass('d-none');

        if (tab.paymentMethod === 'efectivo') {
            $('#posBoxEfectivo').removeClass('d-none');
            const cashInput = document.getElementById('posCashAmountInput');
            if (cashInput && document.activeElement !== cashInput) {
                cashInput.value = (tab.cashAmount !== undefined && tab.cashAmount !== '') ? tab.cashAmount : saleTotal.toFixed(2);
            }
        } else if (tab.paymentMethod === 'tarjeta') {
            $('#posBoxTarjeta').removeClass('d-none');
            const cardInput = document.getElementById('posCardAmountInput');
            if (cardInput && document.activeElement !== cardInput) {
                cardInput.value = (tab.cardAmount !== undefined && tab.cardAmount !== '') ? tab.cardAmount : saleTotal.toFixed(2);
            }
            document.getElementById('posCardRefInput').value = tab.cardRef || '';
        } else if (tab.paymentMethod === 'transferencia') {
            $('#posBoxTransferencia').removeClass('d-none');
            const transInput = document.getElementById('posTransferAmountInput');
            if (transInput && document.activeElement !== transInput) {
                transInput.value = (tab.transferAmount !== undefined && tab.transferAmount !== '') ? tab.transferAmount : saleTotal.toFixed(2);
            }
            document.getElementById('posTransferRefInput').value = tab.transferRef || '';
        } else if (tab.paymentMethod === 'mixto') {
            $('#posBoxMixto').removeClass('d-none');
            document.getElementById('lblMixTargetAmount').textContent = '$' + saleTotal.toFixed(2);
            const mixCashEl = document.getElementById('mixCashInput');
            const mixCardEl = document.getElementById('mixCardInput');
            const mixTransferEl = document.getElementById('mixTransferInput');
            const mixRecEl = document.getElementById('posMixCashReceivedInput');
            if (mixCashEl && document.activeElement !== mixCashEl) mixCashEl.value = roundMoney(tab.mixCash);
            if (mixCardEl && document.activeElement !== mixCardEl) mixCardEl.value = roundMoney(tab.mixCard);
            if (mixTransferEl && document.activeElement !== mixTransferEl) mixTransferEl.value = roundMoney(tab.mixTransfer);
            if (mixRecEl && document.activeElement !== mixRecEl) mixRecEl.value = tab.mixCashReceived || '';
            document.getElementById('mixCardRefInput').value = tab.cardRef || '';
            document.getElementById('mixTransferRefInput').value = tab.transferRef || '';
        }

        refreshLivePaymentUI();
        syncPendingNotesCartButtons(tab);
        if (typeof renderOpenLoansList === 'function') {
            renderOpenLoansList();
        }
    }

    function evaluateTabPaymentState(tab) {
        const totals = calculateTabTotals(tab);
        const saleTotal = roundMoney(totals.total);
        const method = tab.paymentMethod || 'efectivo';
        const hasItems = Array.isArray(tab.items) && tab.items.length > 0;

        let rawEntered = 0;
        let paidToday = 0;
        let cashAmt = 0;
        let cardAmt = 0;
        let transAmt = 0;
        let cashReceived = 0;
        let changeAmt = 0;
        let isBlankInput = false;
        let isOverpaidNonCash = false;
        let overpayExcess = 0;

        if (method === 'efectivo') {
            const rawStr = String(tab.cashAmount ?? '').trim();
            isBlankInput = (saleTotal > 0 && rawStr === '');
            rawEntered = rawStr === '' ? 0 : Math.max(0, roundMoney(parseFloat(rawStr)));
            paidToday = saleTotal > 0 ? Math.min(saleTotal, rawEntered) : 0;
            cashAmt = paidToday;
            cashReceived = rawEntered;
            changeAmt = (saleTotal > 0 && rawEntered >= saleTotal)
                ? Math.max(0, roundMoney(rawEntered - saleTotal))
                : 0;
        } else if (method === 'tarjeta') {
            const rawStr = String(tab.cardAmount ?? '').trim();
            isBlankInput = (saleTotal > 0 && rawStr === '');
            rawEntered = rawStr === '' ? 0 : Math.max(0, roundMoney(parseFloat(rawStr)));
            if (rawEntered > saleTotal + 0.009) {
                isOverpaidNonCash = true;
                overpayExcess = roundMoney(rawEntered - saleTotal);
            }
            paidToday = Math.min(saleTotal, rawEntered);
            cardAmt = paidToday;
        } else if (method === 'transferencia') {
            const rawStr = String(tab.transferAmount ?? '').trim();
            isBlankInput = (saleTotal > 0 && rawStr === '');
            rawEntered = rawStr === '' ? 0 : Math.max(0, roundMoney(parseFloat(rawStr)));
            if (rawEntered > saleTotal + 0.009) {
                isOverpaidNonCash = true;
                overpayExcess = roundMoney(rawEntered - saleTotal);
            }
            paidToday = Math.min(saleTotal, rawEntered);
            transAmt = paidToday;
        } else {
            const c = Math.max(0, roundMoney(tab.mixCash));
            const k = Math.max(0, roundMoney(tab.mixCard));
            const t = Math.max(0, roundMoney(tab.mixTransfer));
            const sumMix = roundMoney(c + k + t);
            rawEntered = sumMix;
            if (sumMix > saleTotal + 0.009) {
                isOverpaidNonCash = true;
                overpayExcess = roundMoney(sumMix - saleTotal);
            }
            paidToday = Math.min(saleTotal, sumMix);
            cashAmt = c;
            cardAmt = k;
            transAmt = t;
            const mixCoversFull = (saleTotal <= 0 || sumMix >= saleTotal - 0.009);
            const rawMixRec = parseFloat(tab.mixCashReceived);
            cashReceived = (c > 0 && mixCoversFull && !isNaN(rawMixRec) && rawMixRec > c)
                ? roundMoney(rawMixRec)
                : c;
            changeAmt = (c > 0 && mixCoversFull)
                ? Math.max(0, roundMoney(cashReceived - c))
                : 0;
        }

        const rawDue = Math.max(0, roundMoney(saleTotal - paidToday));
        const dueAmount = rawDue < 0.01 ? 0 : rawDue;
        const isPendingMode = hasItems && saleTotal > 0 && dueAmount > 0;

        return {
            hasItems,
            saleTotal,
            method,
            rawEntered,
            paidToday,
            dueAmount,
            isPendingMode,
            cashAmt,
            cardAmt,
            transAmt,
            cashReceived,
            changeAmt,
            isBlankInput,
            isOverpaidNonCash,
            overpayExcess,
            hasPendingNoteAbono: tabHasPendingNoteAbonoConcept(tab),
            hasFinAbono: tabHasFinancieraConcept(tab),
            hasProductConcept: tabHasProductConcept(tab),
            hasSpecificCustomer: hasRegisteredSpecificCustomer(tab),
        };
    }

    function renderMixFaltanteAlert(payState) {
        const container = document.getElementById('posMixFaltanteAlertContainer');
        if (!container) return;

        if (!payState.hasItems || payState.saleTotal <= 0) {
            container.innerHTML = '';
            return;
        }

        const sum = roundMoney(payState.cashAmt + payState.cardAmt + payState.transAmt);
        const total = payState.saleTotal;

        if (payState.isOverpaidNonCash) {
            container.innerHTML = `
                <div class="alert alert-danger py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-triangle-exclamation mr-1"></i>Excedente sobre el Total:</span>
                    <span class="badge badge-danger font-size-13">+$${payState.overpayExcess.toFixed(2)}</span>
                </div>
                <small class="text-danger d-block font-size-11 font-weight-bold">
                    La suma asignada ($${sum.toFixed(2)}) supera el total de la venta ($${total.toFixed(2)}). Ajústala a máximo $${total.toFixed(2)}.
                </small>
            `;
            return;
        }

        if (!payState.isPendingMode) {
            container.innerHTML = `
                <div class="alert alert-success py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-circle-check mr-1"></i>¡Monto Cubierto al 100%!</span>
                    <span class="badge badge-success font-size-13">$${sum.toFixed(2)}</span>
                </div>
            `;
            return;
        }

        if (payState.hasPendingNoteAbono) {
            container.innerHTML = `
                <div class="alert alert-danger py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-ban mr-1"></i>Pago Mixto Incompleto:</span>
                    <span class="badge badge-danger font-size-13">Faltan $${payState.dueAmount.toFixed(2)}</span>
                </div>
                <small class="text-danger d-block font-size-11 font-weight-bold">
                    No se permite pago pendiente sobre pago pendiente. Cubre el 100% ($${total.toFixed(2)}) al incluir el abono de una nota pendiente.
                </small>
            `;
            return;
        }

        const parts = [];
        if (payState.cashAmt > 0) parts.push(`Efectivo $${payState.cashAmt.toFixed(2)}`);
        if (payState.cardAmt > 0) parts.push(`Tarjeta $${payState.cardAmt.toFixed(2)}`);
        if (payState.transAmt > 0) parts.push(`Transf. $${payState.transAmt.toFixed(2)}`);
        const breakdownStr = parts.length ? parts.join(' + ') : 'Sin abono inicial hoy';

        container.innerHTML = `
            <div class="alert alert-warning py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-clock-rotate-left mr-1"></i>Abono Mixto Hoy:</span>
                <span class="badge badge-warning text-dark font-size-13">$${sum.toFixed(2)} / $${total.toFixed(2)}</span>
            </div>
            <small class="text-dark d-block font-size-11 font-weight-bold">
                ${breakdownStr} &bull; Restará saldo pendiente de <strong class="text-danger">$${payState.dueAmount.toFixed(2)}</strong>.
            </small>
        `;
    }

    function refreshLivePaymentUI() {
        const tab = getActiveTab();
        const payState = evaluateTabPaymentState(tab);

        // 1. Cambio en efectivo (oculto / $0.00 cuando falta dinero por cubrir)
        const cashChangeWrap = document.getElementById('posCashChangeContainer');
        const cashChangeOut = document.getElementById('posChangeOutput');
        if (cashChangeWrap && cashChangeOut) {
            if (payState.method === 'efectivo' && !payState.isPendingMode && payState.saleTotal > 0) {
                cashChangeWrap.classList.remove('d-none');
                cashChangeOut.value = '$' + payState.changeAmt.toFixed(2);
            } else {
                cashChangeWrap.classList.add('d-none');
                cashChangeOut.value = '$0.00';
            }
        }

        // 2. Cambio de efectivo en Mixto (solo cuando cubre el 100% y hay porción en efectivo)
        const mixDrawerRow = document.getElementById('posMixCashDrawerRow');
        const mixChangeOut = document.getElementById('posMixChangeOutput');
        if (mixDrawerRow && mixChangeOut) {
            if (payState.method === 'mixto' && !payState.isPendingMode && !payState.isOverpaidNonCash && payState.cashAmt > 0 && payState.saleTotal > 0) {
                mixDrawerRow.classList.remove('d-none');
                mixChangeOut.value = '$' + payState.changeAmt.toFixed(2);
            } else {
                mixDrawerRow.classList.add('d-none');
                mixChangeOut.value = '$0.00';
            }
        }

        if (payState.method === 'mixto') {
            renderMixFaltanteAlert(payState);
        }

        // 3. Mostrar botón rápido "100%" solo cuando el monto difiere del total
        const showFillBtn = payState.hasItems && payState.saleTotal > 0 && (payState.isPendingMode || payState.isOverpaidNonCash || payState.changeAmt > 0);
        document.querySelectorAll('.btn-pos-fill-total').forEach(btn => {
            btn.classList.toggle('d-none', !showFillBtn);
        });

        // 4. Indicador dinámico de estado (Liquidación vs Saldo Pendiente Restante)
        const statusBox = document.getElementById('posAutoPaymentStatusBox');
        const checkoutBtn = document.getElementById('btnCompleteUnifiedSale');

        if (statusBox) {
            if (!payState.hasItems || payState.saleTotal <= 0) {
                statusBox.classList.add('d-none');
                statusBox.innerHTML = '';
            } else if (payState.isOverpaidNonCash) {
                statusBox.classList.remove('d-none');
                statusBox.innerHTML = `
                    <div class="alert alert-danger d-block py-2 px-3 mb-0 font-size-12 font-weight-bold">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i>
                        El monto capturado ($${payState.rawEntered.toFixed(2)}) excede el total de la venta ($${payState.saleTotal.toFixed(2)}). Ajusta el monto para continuar.
                    </div>
                `;
            } else if (payState.isPendingMode) {
                statusBox.classList.remove('d-none');
                if (payState.hasPendingNoteAbono) {
                    statusBox.innerHTML = `
                        <div class="alert alert-danger d-block py-2 px-3 mb-0 font-size-12 font-weight-bold">
                            <i class="fa-solid fa-ban mr-1"></i>
                            No se permite pago pendiente sobre pago pendiente desde el Punto de Venta: al incluir el abono de una nota pendiente debes cubrir el 100% del total (<strong>$${payState.saleTotal.toFixed(2)}</strong>). Faltan <strong>$${payState.dueAmount.toFixed(2)}</strong> <br><strong>Para realizar abono a nota pendinte favor de hacerlo desde el modulo de Órdenes y Ventas > Saldo Pendiente.</strong>
                        </div>
                    `;
                } else if (payState.hasFinAbono) {
                    statusBox.innerHTML = `
                        <div class="alert alert-danger d-block py-2 px-3 mb-0 font-size-12 font-weight-bold">
                            <i class="fa-solid fa-ban mr-1"></i>
                            Los abonos a financieras no permiten pago parcial ni saldo pendiente. Cubre los $${payState.saleTotal.toFixed(2)} o edita el importe directo en el carrito.
                        </div>
                    `;
                } else if (!payState.hasProductConcept) {
                    statusBox.innerHTML = `
                        <div class="alert alert-danger d-block py-2 px-3 mb-0 font-size-12 font-weight-bold">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            Para dejar saldo pendiente en Nota por Cobrar debe existir al menos un producto en el carrito. Para préstamos o tandas, edita directamente el importe en el concepto.
                        </div>
                    `;
                } else {
                    const custAlertHtml = payState.hasSpecificCustomer
                        ? `<div class="d-flex align-items-center text-success font-size-11 font-weight-bold mt-2 pt-2 border-top border-warning">
                                <i class="fa-solid fa-user-check mr-1"></i>
                                <span>Saldo pendiente asignado a: <strong>${escapeHtml(tab.customerName)}</strong></span>
                           </div>`
                        : `<div class="alert alert-danger d-block py-2 px-2 mt-2 mb-0 font-size-11 font-weight-bold">
                                <div class="mb-1">
                                    <i class="fa-solid fa-user-lock mr-1"></i>
                                    Cliente requerido: Selecciona un cliente específico registrado.
                                </div>
                           </div>`;

                    statusBox.innerHTML = `
                        <div class="pos-pending-alert-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-size-12 font-weight-bold text-dark">
                                    <i class="fa-solid fa-clock-rotate-left text-warning mr-1"></i>Saldo Pendiente Restante:
                                </span>
                                <span class="badge badge-danger font-size-15 px-2 py-1">$${payState.dueAmount.toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between font-size-11 text-muted mt-1">
                                <span>Total venta: <strong class="text-dark">$${payState.saleTotal.toFixed(2)}</strong></span>
                                <span>Pagado hoy: <strong class="text-success">$${payState.paidToday.toFixed(2)}</strong></span>
                            </div>
                            ${custAlertHtml}
                        </div>
                    `;
                }
            } else {
                statusBox.classList.remove('d-none');
                statusBox.innerHTML = `
                    <div class="alert alert-success py-2 px-3 mb-0 font-size-12 font-weight-bold d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-circle-check mr-1"></i>Pago completo ($${payState.saleTotal.toFixed(2)})</span>
                        ${payState.changeAmt > 0 ? `<span class="badge badge-success font-size-12">Cambio: $${payState.changeAmt.toFixed(2)}</span>` : '<span class="badge badge-success">Liquidado</span>'}
                    </div>
                `;
            }
        }

        // 5. Botón principal dinámico: Verde (Liquidado) vs Ámbar/Naranja (Saldo Pendiente)
        if (checkoutBtn && !isCheckoutInFlight) {
            const usePendingButton = payState.isPendingMode && !payState.hasPendingNoteAbono && !payState.hasFinAbono && payState.hasProductConcept && !payState.isOverpaidNonCash;
            checkoutBtn.classList.toggle('btn-checkout-pending', usePendingButton);
            if (usePendingButton) {
                checkoutBtn.innerHTML = '<i class="fa-solid fa-clock-rotate-left mr-2"></i>Pagar y Dejar Saldo Pendiente';
            } else {
                checkoutBtn.innerHTML = '<i class="fa-solid fa-check-double mr-2"></i>Cerrar Cobro y Generar Ticket';
            }
        }
    }

    function updateChangeCalculation() {
        refreshLivePaymentUI();
    }

    function renderAll() {
        renderTabsBar();
        renderActiveCart();
    }

    function addItemToCurrentTab(item) {
        const tab = getActiveTab();
        if (!tab.externalCustomerId && !tab.customerName && item.customer_name) {
            tab.customerName = item.customer_name;
        }
        if (!tab.externalCustomerId && !tab.customerId && item.customer_id) {
            tab.customerId = item.customer_id;
        }

        const matchIdx = findMatchingCartIndex(tab.items, item);
        if (matchIdx !== -1) {
            const existing = tab.items[matchIdx];
            const sec = item.section;

            if (sec === 'producto_inventario' || sec === 'gift' || (sec === 'producto_dinamico' && !normalizeTextKey(item.imei))) {
                const addQty = Math.max(1, parseInt(item.quantity) || 1);
                const curQty = Math.max(1, parseInt(existing.quantity) || 1);
                const nextQty = curQty + addQty;
                const maxStockRaw = existing.max_stock !== undefined ? existing.max_stock : item.max_stock;
                const maxStock = (maxStockRaw !== undefined && maxStockRaw !== null && maxStockRaw !== '') ? parseInt(maxStockRaw) : null;

                if (maxStock !== null && !isNaN(maxStock) && nextQty > maxStock) {
                    highlightedCartIndex = matchIdx;
                    renderAll();
                    showCartFeedback('warning', `Stock máximo alcanzado para "${existing.title}" (Disponible: ${maxStock}).`);
                    return;
                }

                existing.quantity = nextQty;
                if (existing.max_stock === undefined && item.max_stock !== undefined) {
                    existing.max_stock = item.max_stock;
                }
                if (!existing.product_code && item.product_code) existing.product_code = item.product_code;
                if (!existing.imei && item.imei) existing.imei = item.imei;

                highlightedCartIndex = matchIdx;
                saveState();
                renderAll();
                showCartFeedback('merged', `Cantidad actualizada: "${existing.title}" ahora tiene ${nextQty} pzas.`);
                return;
            }

            highlightedCartIndex = matchIdx;
            renderAll();
            showCartFeedback('warning', `"${existing.title}" ya está en el carrito. Modifica su monto o datos directamente en el concepto existente.`);
            return;
        }

        const initialQty = Math.max(1, parseInt(item.quantity) || 1);
        const maxStockInit = (item.max_stock !== undefined && item.max_stock !== null && item.max_stock !== '') ? parseInt(item.max_stock) : null;
        if (maxStockInit !== null && !isNaN(maxStockInit) && initialQty > maxStockInit) {
            showCartFeedback('warning', `Stock insuficiente para "${item.title}" (Disponible: ${maxStockInit}).`);
            return;
        }

        tab.items.push(item);
        highlightedCartIndex = tab.items.length - 1;
        saveState();
        renderAll();
        showCartFeedback('added', `Agregado: "${item.title}".`);
    }

    function updateCartItemQuantity(idx, newQty) {
        const tab = getActiveTab();
        if (!tab || !tab.items[idx]) return;
        const item = tab.items[idx];
        const qVal = parseInt(newQty);

        if (isNaN(qVal) || qVal < 1) {
            showCartFeedback('warning', 'La cantidad mínima es 1.');
            renderActiveCart();
            return;
        }

        const isStockBounded = (item.section === 'gift' || item.section === 'producto_inventario');
        const maxStockRaw = item.max_stock;
        const maxStock = (maxStockRaw !== undefined && maxStockRaw !== null && maxStockRaw !== '') ? parseInt(maxStockRaw) : null;
        if (isStockBounded && maxStock !== null && !isNaN(maxStock) && qVal > maxStock) {
            highlightedCartIndex = idx;
            renderActiveCart();
            showCartFeedback('warning', `Solo hay ${maxStock} unidad(es) disponibles de "${item.title}".`);
            return;
        }

        item.quantity = qVal;
        saveState();
        renderAll();
    }

    // Eventos del carrito
    document.getElementById('tabCartItemsBody').addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.btn-remove-item');
        if (removeBtn) {
            const idx = parseInt(removeBtn.dataset.idx);
            const tab = getActiveTab();
            tab.items.splice(idx, 1);
            saveState();
            renderAll();
            return;
        }

        const minusBtn = e.target.closest('.btn-qty-minus');
        if (minusBtn) {
            const idx = parseInt(minusBtn.dataset.idx);
            const tab = getActiveTab();
            if (tab && tab.items[idx]) {
                const cur = Math.max(1, parseInt(tab.items[idx].quantity) || 1);
                if (cur > 1) {
                    updateCartItemQuantity(idx, cur - 1);
                }
            }
            return;
        }

        const plusBtn = e.target.closest('.btn-qty-plus');
        if (plusBtn) {
            const idx = parseInt(plusBtn.dataset.idx);
            const tab = getActiveTab();
            if (tab && tab.items[idx]) {
                const cur = Math.max(1, parseInt(tab.items[idx].quantity) || 1);
                updateCartItemQuantity(idx, cur + 1);
            }
        }
    });

    document.getElementById('tabCartItemsBody').addEventListener('change', function (e) {
        const priceInput = e.target.closest('.cart-item-price-input');
        if (priceInput) {
            const idx = parseInt(priceInput.dataset.idx);
            const rawVal = priceInput.value.trim();
            const val = parseFloat(rawVal);
            if (rawVal === '' || isNaN(val) || val < 0) {
                alert('El precio no puede ser negativo ni quedar vacío. Si es gratis, ingresa 0.00.');
                renderActiveCart();
                return;
            }
            const tab = getActiveTab();
            if (tab && tab.items[idx]) {
                const targetItem = tab.items[idx];
                const nextAmt = Math.round(val * 100) / 100;
                if (targetItem.section === 'nota_abono') {
                    if (nextAmt <= 0) {
                        alert('⚠️ El abono a una nota pendiente debe ser mayor a $0.00.');
                        renderActiveCart();
                        return;
                    }
                    const maxDue = roundMoney(targetItem.original_due !== undefined ? targetItem.original_due : (targetItem.max_amount ?? nextAmt));
                    if (maxDue > 0 && nextAmt > maxDue + 0.01) {
                        alert(`⚠️ El abono ($${nextAmt.toFixed(2)}) no puede exceder el saldo pendiente de la nota ($${maxDue.toFixed(2)}).`);
                        targetItem.amount = maxDue;
                        saveState();
                        renderActiveCart();
                        return;
                    }
                }
                targetItem.amount = nextAmt;
                saveState();
                renderActiveCart();
            }
            return;
        }

        const qtyInput = e.target.closest('.cart-item-qty-input');
        if (qtyInput) {
            const idx = parseInt(qtyInput.dataset.idx);
            updateCartItemQuantity(idx, qtyInput.value);
        }
    });

    document.getElementById('btnClearCurrentTab').addEventListener('click', function () {
        const tab = getActiveTab();
        tab.items = [];
        tab.customerName = '';
        tab.customerPhone = '';
        tab.customerId = '';
        tab.externalCustomerId = '';
        tab.cashAmount = '';
        tab.cardAmount = '';
        tab.transferAmount = '';
        tab.cashTouched = false;
        tab.cardTouched = false;
        tab.transferTouched = false;
        tab.mixTouched = false;
        tab.mixCash = 0;
        tab.mixCard = 0;
        tab.mixTransfer = 0;
        tab.mixCashReceived = '';
        tab.cardRef = '';
        tab.transferRef = '';
        highlightedCartIndex = -1;
        const fb = document.getElementById('posCartFeedbackBanner');
        if (fb) fb.className = 'd-none mb-2';
        saveState();
        renderAll();
    });

    function refreshRegisteredCustomersList(showFeedback = false) {
        const selectEl = document.getElementById('posCartCustomerSelect');
        if (!selectEl || !CUSTOMERS_LOOKUP_URL) return;

        const btnRefresh = document.getElementById('btnRefreshPosCustomers');
        if (btnRefresh) btnRefresh.disabled = true;

        posFetchJson(CUSTOMERS_LOOKUP_URL, { timeoutMs: 12000 })
            .then(data => {
                if (!data || !Array.isArray(data.customers)) return;
                const currentVal = $(selectEl).val() || (getActiveTab()?.customerId ?? '');
                let optionsHtml = '<option value="">-- Buscar cliente registrado en catálogo --</option>';
                data.customers.forEach(c => {
                    const phone = c.tit_phone ? escapeHtml(c.tit_phone) : 'Sin tel';
                    const name = escapeHtml(c.tit_name || '');
                    const phoneAttr = escapeHtml(c.tit_phone || '');
                    optionsHtml += `<option value="${escapeHtml(c.id)}" data-name="${name}" data-phone="${phoneAttr}">${name} (${phone})</option>`;
                });
                selectEl.innerHTML = optionsHtml;
                if (currentVal && Array.from(selectEl.options).some(o => String(o.value) === String(currentVal))) {
                    $(selectEl).val(String(currentVal)).trigger('change.select2');
                } else {
                    $(selectEl).trigger('change.select2');
                }
                refreshLivePaymentUI();
                if (showFeedback && typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Catálogo actualizado (${data.customers.length} clientes)`,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            })
            .catch(err => console.warn('No se pudo actualizar el catálogo de clientes:', err))
            .finally(() => {
                if (btnRefresh) btnRefresh.disabled = false;
            });
    }

    const btnRefreshCustEl = document.getElementById('btnRefreshPosCustomers');
    if (btnRefreshCustEl) {
        btnRefreshCustEl.addEventListener('click', () => refreshRegisteredCustomersList(true));
    }

    let openedNewCustomerTab = false;
    const newCustomerLinkEl = document.getElementById('btnPosNewCustomerLink');
    if (newCustomerLinkEl) {
        newCustomerLinkEl.addEventListener('click', () => {
            openedNewCustomerTab = true;
        });
    }
    window.addEventListener('focus', function () {
        if (openedNewCustomerTab) {
            openedNewCustomerTab = false;
            refreshRegisteredCustomersList(false);
        }
    });

    const autoStatusContainer = document.getElementById('posAutoPaymentStatusBox');
    if (autoStatusContainer) {
        autoStatusContainer.addEventListener('click', function (e) {
            const focusBtn = e.target.closest('.btn-pos-focus-customer');
            if (focusBtn) {
                const selectEl = document.getElementById('posCartCustomerSelect');
                if (selectEl) {
                    selectEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    try {
                        $(selectEl).select2('open');
                    } catch (_) {
                        selectEl.focus();
                    }
                }
            }
        });
    }

    document.getElementById('tabCustomerName').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.customerName = this.value;
        const cartSelectEl = document.getElementById('posCartCustomerSelect');
        if (tab.customerId && cartSelectEl) {
            const selOpt = cartSelectEl.options[cartSelectEl.selectedIndex];
            if (!selOpt || (selOpt.dataset.name || '') !== this.value) {
                tab.customerId = '';
                $('#posCartCustomerSelect').val('').trigger('change.select2');
            }
        }
        saveState();
        renderTabsBar();
        refreshLivePaymentUI();
        scheduleCustomerPendingNotesCheck(tab);
    });

    document.getElementById('tabCustomerPhone').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.customerPhone = this.value;
        saveState();
        scheduleCustomerPendingNotesCheck(tab);
    });

    $('#posCartCustomerSelect').on('change', function () {
        const cid = $(this).val();
        const tab = getActiveTab();
        if (!cid) {
            tab.customerId = '';
            saveState();
            refreshLivePaymentUI();
            syncCustomerPendingNotes(tab, true);
            return;
        }
        const opt = this.options[this.selectedIndex];
        tab.customerId = cid;
        tab.externalCustomerId = '';
        tab.customerName = opt ? (opt.dataset.name || '') : '';
        tab.customerPhone = opt ? (opt.dataset.phone || '') : '';
        saveState();
        document.getElementById('tabCustomerName').value = tab.customerName;
        document.getElementById('tabCustomerPhone').value = tab.customerPhone;
        renderTabsBar();
        refreshLivePaymentUI();
        syncCustomerPendingNotes(tab, true);

        if ($('#posLoanCustomerSelect').length && $('#posLoanCustomerSelect').val() != cid) {
            $('#posLoanCustomerSelect').val(cid).trigger('change');
        }
    });

    $('#posCatalogProductSelect').on('change', function () {
        const prodId = $(this).val();
        if (!prodId) return;
        const opt = this.options[this.selectedIndex];
        if (!opt) return;

        addItemToCurrentTab({
            section: 'producto_inventario',
            product_id: opt.dataset.id,
            product_code: opt.dataset.code || '',
            imei: opt.dataset.imei || '',
            max_stock: parseInt(opt.dataset.stock) || 0,
            title: opt.dataset.name,
            subtitle: `Impacto real en Inventario | Código: ${opt.dataset.code || '-'}${opt.dataset.imei ? ' | IMEI: ' + opt.dataset.imei : ''}`,
            quantity: 1,
            amount: parseFloat(opt.dataset.price) || 0,
            original_amount: parseFloat(opt.dataset.price) || 0,
            commission: 0
        });

        $(this).val('').trigger('change.select2');
    });

    function filterCatalogTable() {
        const q = (document.getElementById('posProductSearchInput').value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#posCatalogProductsBody tr');
        rows.forEach(tr => {
            const text = tr.innerText.toLowerCase();
            tr.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }
    const searchInput = document.getElementById('posProductSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterCatalogTable);
        const searchBtn = document.getElementById('btnSearchCatalogProduct');
        if (searchBtn) searchBtn.addEventListener('click', filterCatalogTable);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterCatalogTable();
                const visibleRows = Array.from(document.querySelectorAll('#posCatalogProductsBody tr')).filter(r => r.style.display !== 'none');
                if (visibleRows.length === 1) {
                    const addBtn = visibleRows[0].querySelector('.btn-add-stock-product');
                    if (addBtn) addBtn.click();
                    this.value = '';
                    filterCatalogTable();
                }
            }
        });
    }

    function resetActiveMethodToSaleTotal(tab) {
        const saleTotal = roundMoney(calculateTabTotals(tab).total);
        const totalStr = saleTotal.toFixed(2);
        const method = tab.paymentMethod || 'efectivo';
        if (method === 'efectivo') {
            tab.cashTouched = false;
            tab.cashAmount = totalStr;
        } else if (method === 'tarjeta') {
            tab.cardTouched = false;
            tab.cardAmount = totalStr;
        } else if (method === 'transferencia') {
            tab.transferTouched = false;
            tab.transferAmount = totalStr;
        } else if (method === 'mixto') {
            tab.mixTouched = false;
            tab.mixCash = saleTotal;
            tab.mixCard = 0;
            tab.mixTransfer = 0;
            tab.mixCashReceived = '';
        }
    }

    document.querySelectorAll('#paymentMethodBtnGroup [data-method]').forEach(lbl => {
        lbl.addEventListener('click', function () {
            const tab = getActiveTab();
            tab.paymentMethod = this.dataset.method;
            resetActiveMethodToSaleTotal(tab);
            saveState();
            renderActiveCart();
        });
    });

    document.querySelectorAll('.btn-pos-fill-total').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = getActiveTab();
            resetActiveMethodToSaleTotal(tab);
            saveState();
            renderActiveCart();
        });
    });

    document.getElementById('posCashAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.cashAmount = this.value;
        tab.cashTouched = true;
        saveState();
        refreshLivePaymentUI();
    });

    document.getElementById('posCardAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.cardAmount = this.value;
        tab.cardTouched = true;
        saveState();
        refreshLivePaymentUI();
    });

    document.getElementById('posTransferAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.transferAmount = this.value;
        tab.transferTouched = true;
        saveState();
        refreshLivePaymentUI();
    });

    document.getElementById('posCardRefInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.cardRef = this.value;
        saveState();
    });

    document.getElementById('posTransferRefInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.transferRef = this.value;
        saveState();
    });

    document.getElementById('mixCardRefInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.cardRef = this.value;
        saveState();
    });

    document.getElementById('mixTransferRefInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.transferRef = this.value;
        saveState();
    });

    ['mixCashInput', 'mixCardInput', 'mixTransferInput'].forEach(id => {
        document.getElementById(id).addEventListener('input', function () {
            const tab = getActiveTab();
            tab.mixCash = parseFloat(document.getElementById('mixCashInput').value) || 0;
            tab.mixCard = parseFloat(document.getElementById('mixCardInput').value) || 0;
            tab.mixTransfer = parseFloat(document.getElementById('mixTransferInput').value) || 0;
            tab.mixTouched = true;
            saveState();
            refreshLivePaymentUI();
        });
    });

    document.getElementById('posMixCashReceivedInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.mixCashReceived = this.value;
        saveState();
        refreshLivePaymentUI();
    });

    // ==================== 1. BÚSQUEDA Y ABONO DE FINANCIERAS ====================
    function searchFinancieras() {
        const q = document.getElementById('finSearchInput').value.trim();
        const finId = document.getElementById('finCatalogSelect').value;
        const container = document.getElementById('finSearchResults');
        container.innerHTML = '<div class="text-muted font-size-12"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Buscando contratos por Device ID / TAG...</div>';

        posFetchJson(`{{ route('central-pos.lookup-financiera', [], false) }}?q=${encodeURIComponent(q)}&financiera_id=${encodeURIComponent(finId)}`, { timeoutMs: 15000 })
            .then(data => {
                if (!data.results || data.results.length === 0) {
                    container.innerHTML = '<div class="alert alert-light border py-2 font-size-12 mb-0">No se encontraron equipos con ese TAG / Device ID / IMEI.</div>';
                    return;
                }
                let rows = data.results.map(r => `
                    <tr>
                        <td><strong>${r.tag_contrato}</strong><small class="d-block text-muted">ID: #${r.device_id} | IMEI: ${r.imei}</small></td>
                        <td>${r.financiera_name}<small class="d-block text-muted">Suc: ${r.branch_name || 'General'}</small></td>
                        <td><strong>${r.customer_name}</strong><small class="d-block text-muted">${r.device_model}</small></td>
                        <td>$${r.abono_semanal.toFixed(2)}<small class="d-block text-success font-size-11">Abonado: $${r.total_abonos_paid.toFixed(2)}</small></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-primary btn-select-fin shadow-sm"
                                data-sale-id="${r.sale_id}"
                                data-fin="${r.financiera_name}"
                                data-branch="${r.branch_name || 'General'}"
                                data-branch-id="${r.branch_id || ''}"
                                data-customer="${r.customer_name}"
                                data-device="Device #${r.device_id} | IMEI: ${r.imei} | TAG: ${r.tag_contrato}"
                                data-tag="${r.tag_contrato}"
                                data-abono="${r.abono_semanal}">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                `).join('');

                container.innerHTML = `
                    <div class="table-responsive border rounded mt-2" style="max-height: 180px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0 font-size-12">
                            <thead class="bg-light">
                                <tr>
                                    <th>TAG / Device ID</th>
                                    <th>Financiera</th>
                                    <th>Cliente / Equipo</th>
                                    <th>Abono Semanal</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            })
            .catch(err => {
                container.innerHTML = `<div class="alert alert-danger py-2 font-size-12 mb-0">${err.message || 'No se pudo consultar el catálogo de financieras.'}</div>`;
            });
    }

    const btnSearchFin = document.getElementById('btnSearchFinanciera');
    if (btnSearchFin) btnSearchFin.addEventListener('click', searchFinancieras);

    const finSearchInp = document.getElementById('finSearchInput');
    if (finSearchInp) {
        finSearchInp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchFinancieras();
            }
        });
    }

    const finResultsEl = document.getElementById('finSearchResults');
    if (finResultsEl) {
        finResultsEl.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-select-fin');
            if (!btn) return;
            const selBox = document.getElementById('finSelectedContractBox');
            if (selBox) selBox.classList.remove('d-none');
            const saleIdEl = document.getElementById('finSelSaleId');
            if (saleIdEl) saleIdEl.value = btn.dataset.saleId;
            const finEl = document.getElementById('finSelFinanciera');
            if (finEl) finEl.textContent = btn.dataset.fin;
            const branchBadgeEl = document.getElementById('finSelBranchBadge');
            if (branchBadgeEl) {
                const originBranch = btn.dataset.branch || 'General';
                const currentBranch = '{{ $branchId }}';
                const isOtherBranch = btn.dataset.branchId && String(btn.dataset.branchId) !== String(currentBranch);
                branchBadgeEl.textContent = isOtherBranch
                    ? `Contrato: ${originBranch} (cobra en caja actual)`
                    : `Sucursal: ${originBranch}`;
                branchBadgeEl.className = isOtherBranch
                    ? 'badge badge-warning ml-1'
                    : 'badge badge-light border ml-1';
            }
            const custEl = document.getElementById('finSelCustomer');
            if (custEl) custEl.textContent = btn.dataset.customer;
            const custNameEl = document.getElementById('finSelCustomerName');
            if (custNameEl) custNameEl.value = btn.dataset.customer;
            const devEl = document.getElementById('finSelDevice');
            if (devEl) devEl.textContent = btn.dataset.device;
            const titleEl = document.getElementById('finSelTitle');
            if (titleEl) titleEl.value = `Abono Financiera (${btn.dataset.fin}) TAG: ${btn.dataset.tag}`;
            const abono = parseFloat(btn.dataset.abono);
            const suggested = isNaN(abono) ? 0 : Math.max(0, abono);
            const sugEl = document.getElementById('finSelSuggested');
            if (sugEl) sugEl.textContent = '$' + suggested.toFixed(2);
            const abonoInp = document.getElementById('finAbonoAmount');
            if (abonoInp) abonoInp.value = suggested.toFixed(2);
        });
    }

    const btnAddFin = document.getElementById('btnAddFinAbonoToTab');
    if (btnAddFin) {
        btnAddFin.addEventListener('click', function () {
            const saleId = document.getElementById('finSelSaleId')?.value;
            const amountVal = document.getElementById('finAbonoAmount')?.value?.trim() ?? '';
            const amount = parseFloat(amountVal);
            const comm = parseFloat(document.getElementById('finCommissionAmount')?.value) || 0;
            if (!saleId || amountVal === '' || isNaN(amount) || amount < 0) {
                alert('Selecciona un contrato y especifica un monto de abono válido (0.00 o mayor).');
                return;
            }
            if (comm < 0) {
                alert('La comisión no puede ser menor a 0.');
                return;
            }
            addItemToCurrentTab({
                section: 'financiera_abono',
                sale_id: saleId,
                title: document.getElementById('finSelTitle')?.value || 'Abono Financiera',
                subtitle: document.getElementById('finSelDevice')?.textContent || '',
                financiera_name: document.getElementById('finSelFinanciera')?.textContent || '',
                customer_name: document.getElementById('finSelCustomerName')?.value || '',
                quantity: 1,
                amount: amount,
                commission: comm
            });
        });
    }

    const btnAddExtFin = document.getElementById('btnAddExtFinToTab');
    if (btnAddExtFin) {
        btnAddExtFin.addEventListener('click', function () {
            const finSelect = document.getElementById('finExtFinancieraSelect');
            const finId = finSelect ? finSelect.value : '';
            const finName = (finSelect && finSelect.selectedIndex > 0) ? finSelect.options[finSelect.selectedIndex].text.trim() : '';
            const ref = (document.getElementById('finExtReference')?.value || '').trim();
            const imei = (document.getElementById('finExtImei')?.value || '').trim();
            const amountVal = document.getElementById('finExtAmount')?.value?.trim() ?? '';
            const amount = parseFloat(amountVal);
            const comm = parseFloat(document.getElementById('finExtCommission')?.value) || 0;

            if (!finId) {
                alert('Selecciona una financiera (PayJoy, Credimás, Creditienda, etc.).');
                return;
            }
            if (!ref) {
                alert('Ingresa una referencia, TAG o contrato del equipo externo.');
                document.getElementById('finExtReference')?.focus();
                return;
            }
            if (amountVal === '' || isNaN(amount) || amount < 0) {
                alert('Especifica un monto de abono válido (0.00 o mayor).');
                return;
            }
            if (comm < 0) {
                alert('La comisión no puede ser menor a 0.');
                return;
            }

            const subtitleParts = [`Equipo Externo`, `Ref/TAG: ${ref}`];
            if (imei) subtitleParts.push(`IMEI/Equipo: ${imei}`);

            addItemToCurrentTab({
                section: 'financiera_externa',
                financiera_id: parseInt(finId, 10),
                financiera_name: finName,
                tag_contrato: ref,
                imei: imei || null,
                title: `Abono Financiera Externa (${finName}) Ref: ${ref}`,
                subtitle: subtitleParts.join(' | '),
                quantity: 1,
                amount: amount,
                commission: comm
            });

            document.getElementById('finExtReference').value = '';
            document.getElementById('finExtImei').value = '';
            document.getElementById('finExtAmount').value = '';
            document.getElementById('finExtCommission').value = '0';
        });
    }

    // ==================== CLIENTES EXTERNOS EN POS ====================
    function applyExternalCustomerToTab(ext) {
        if (!ext || !ext.id) return;
        const tab = getActiveTab();
        if (!tabHasFinancieraConcept(tab)) {
            alert('La opción de Cliente Externo solo se habilita cuando existe un concepto de Financiera en el carrito.');
            return;
        }
        tab.externalCustomerId = String(ext.id);
        tab.customerId = '';
        tab.customerName = ext.tit_name || '';
        tab.customerPhone = ext.tit_phone || '';
        saveState();
        renderAll();
    }

    function openExternalCustomerModal() {
        const tab = getActiveTab();
        if (!tabHasFinancieraConcept(tab)) {
            alert('Agrega primero un concepto de pago de Financiera al carrito para habilitar Clientes Externos.');
            return;
        }
        const errBox = document.getElementById('posExtFormErrorBox');
        if (errBox) {
            errBox.classList.add('d-none');
            errBox.innerHTML = '';
        }
        $('#posExternalCustomerModal').modal('show');
    }

    const btnTriggerExt = document.getElementById('btnTriggerExternalCustomer');
    if (btnTriggerExt) {
        btnTriggerExt.addEventListener('click', openExternalCustomerModal);
    }
    const btnChangeExt = document.getElementById('btnChangeExternalCustomer');
    if (btnChangeExt) {
        btnChangeExt.addEventListener('click', openExternalCustomerModal);
    }
    const btnClearExt = document.getElementById('btnClearExternalCustomer');
    if (btnClearExt) {
        btnClearExt.addEventListener('click', function () {
            const tab = getActiveTab();
            tab.externalCustomerId = '';
            tab.customerName = '';
            tab.customerPhone = '';
            saveState();
            renderAll();
        });
    }

    const btnUseSelExt = document.getElementById('btnUseSelectedExternalCustomer');
    if (btnUseSelExt) {
        btnUseSelExt.addEventListener('click', function () {
            const sel = document.getElementById('posModalExtCustomerSelect');
            if (!sel || !sel.value) {
                alert('Selecciona un cliente externo de la lista.');
                return;
            }
            const opt = sel.options[sel.selectedIndex];
            applyExternalCustomerToTab({
                id: sel.value,
                tit_name: opt.dataset.name || '',
                tit_phone: opt.dataset.phone || ''
            });
            $('#posExternalCustomerModal').modal('hide');
        });
    }

    const extCustomerForm = document.getElementById('posExternalCustomerForm');
    if (extCustomerForm) {
        extCustomerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const errBox = document.getElementById('posExtFormErrorBox');
            const submitBtn = document.getElementById('btnSaveExternalCustomerPos');
            if (errBox) {
                errBox.classList.add('d-none');
                errBox.innerHTML = '';
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Guardando...';
            }

            const payload = Object.fromEntries(new FormData(extCustomerForm).entries());
            payload.branch_id = '{{ $branchId }}';

            posFetchJson("{{ route('central-pos.external-customers.store', [], false) }}", {
                method: 'POST',
                json: payload,
                timeoutMs: 20000
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'No se pudo guardar el cliente externo.');
                }
                const ext = data.external_customer;
                const sel = document.getElementById('posModalExtCustomerSelect');
                if (sel && ext) {
                    const opt = document.createElement('option');
                    opt.value = ext.id;
                    opt.dataset.name = ext.tit_name || '';
                    opt.dataset.phone = ext.tit_phone || '';
                    opt.textContent = `${ext.tit_name} (${ext.tit_phone}) — ${ext.tit_city || ''}`;
                    sel.appendChild(opt);
                    sel.value = String(ext.id);
                }

                extCustomerForm.reset();
                applyExternalCustomerToTab(ext);
                $('#posExternalCustomerModal').modal('hide');

                if (data.internal_match) {
                    alert(`Cliente externo guardado y asociado. Nota: el teléfono ya coincide con el cliente interno #${data.internal_match.id} (${data.internal_match.tit_name}).`);
                }
            })
            .catch(err => {
                if (errBox) {
                    errBox.innerHTML = err.validationMessages
                        ? err.validationMessages.join('<br>')
                        : (err.message || 'Error al registrar cliente externo.');
                    errBox.classList.remove('d-none');
                }
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i>Guardar en Catálogo Externo y Asociar al Cobro';
                }
            });
        });
    }

    // ==================== 2. PRÉSTAMOS EN CONSOLA CENTRAL ====================
    const posLoanCustomerSelect = document.getElementById('posLoanCustomerSelect');
    const posLoanAlertBanner = document.getElementById('posLoanAlertBanner');
    const posOpenLoansContainer = document.getElementById('posCustomerOpenLoansContainer');
    let currentCustomerLoansData = null;

    function renderOpenLoansList() {
        if (!currentCustomerLoansData) return;
        const data = currentCustomerLoansData;
        if (!data.has_open_loans || !data.open_loans || data.open_loans.length === 0) {
            posLoanAlertBanner.classList.add('d-none');
            posOpenLoansContainer.innerHTML = '<div class="alert alert-success py-2 font-size-12 mb-0"><i class="fa-solid fa-circle-check mr-1"></i>El cliente no tiene préstamos abiertos ni pagos pendientes.</div>';
            return;
        }

        posLoanAlertBanner.classList.remove('d-none');
        posLoanAlertBanner.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-danger mr-1"></i><strong>¡Alerta!</strong> El cliente tiene <strong>${data.open_loans_count} préstamo(s) abierto(s)</strong> con pagos pendientes.`;

        const tab = getActiveTab();
        let html = '';
        data.open_loans.forEach(loan => {
            let instRows = loan.pending_installments.map(inst => {
                const addedItem = tab.items.find(it => it.section === 'prestamo_abono' && String(it.installment_id) === String(inst.id));
                const isAdded = !!addedItem;
                return `
                    <tr class="${inst.is_overdue ? 'table-danger' : ''}">
                        <td>
                            <strong>Pago #${inst.installment_number}</strong>
                            ${inst.status === 'parcial' ? '<span class="badge badge-warning ml-1">Parcial</span>' : ''}
                        </td>
                        <td>${inst.scheduled_date_formatted}</td>
                        <td>
                            <strong class="text-dark">$${inst.remaining_amount.toFixed(2)}</strong>
                            ${inst.paid_amount > 0 ? `<small class="d-block text-success font-size-11">Abonado: $${inst.paid_amount.toFixed(2)}</small>` : ''}
                        </td>
                        <td style="width: 140px;">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text py-0 px-2 font-size-11">$</span></div>
                                <input type="number" step="0.01" min="0.01" max="${inst.remaining_amount}"
                                    class="form-control form-control-sm px-1 py-0 font-size-12 font-weight-bold text-right"
                                    id="loan_inst_amt_${inst.id}"
                                    value="${isAdded ? addedItem.amount.toFixed(2) : inst.remaining_amount.toFixed(2)}"
                                    ${isAdded ? 'disabled' : ''}>
                            </div>
                        </td>
                        <td class="text-right">
                            ${isAdded ? `
                                <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="Este período de pago ya fue agregado al proceso actual">
                                    <i class="fa-solid fa-check text-success mr-1"></i>En Carrito ($${addedItem.amount.toFixed(2)})
                                </button>
                            ` : `
                                <button type="button" class="btn btn-xs btn-success btn-add-loan-abono shadow-sm font-weight-bold"
                                    data-loan-id="${loan.id}"
                                    data-loan-code="${loan.loan_code}"
                                    data-inst-id="${inst.id}"
                                    data-inst-num="${inst.installment_number}"
                                    data-scheduled="${inst.scheduled_date_formatted}"
                                    data-max="${inst.remaining_amount}">
                                    + Abonar Pago #${inst.installment_number}
                                </button>
                            `}
                        </td>
                    </tr>
                `;
            }).join('');

            html += `
                <div class="card border mb-2 shadow-sm">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center font-size-12">
                        <span><strong>${loan.loan_code}</strong> (${loan.type_name}) — Saldo Pendiente: <strong class="text-danger">$${loan.balance_amount.toFixed(2)}</strong></span>
                        <a href="/loans/${loan.id}" target="_blank" class="font-weight-bold text-primary">Ver / Pagaré <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i></a>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm mb-0 font-size-12">
                            <thead>
                                <tr>
                                    <th>Cuota</th>
                                    <th>Día Programado</th>
                                    <th>Pendiente</th>
                                    <th>Monto a Abonar</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>${instRows}</tbody>
                        </table>
                    </div>
                </div>
            `;
        });
        posOpenLoansContainer.innerHTML = html;
    }

    $('#posLoanCustomerSelect').on('change', function () {
        const customerId = $(this).val();
        const opt = this.options[this.selectedIndex];
        if (!customerId) {
            currentCustomerLoansData = null;
            posLoanAlertBanner.classList.add('d-none');
            posOpenLoansContainer.innerHTML = '';
            return;
        }

        const tab = getActiveTab();
        tab.customerId = customerId;
        tab.customerName = opt ? (opt.dataset.name || '') : '';
        tab.customerPhone = opt ? (opt.dataset.phone || '') : '';
        saveState();
        renderActiveCart();

        posFetchJson(`{{ route('central-pos.lookup-loans', [], false) }}?customer_id=${encodeURIComponent(customerId)}`, { timeoutMs: 15000 })
            .then(data => {
                currentCustomerLoansData = data;
                renderOpenLoansList();
            })
            .catch(() => {
                currentCustomerLoansData = null;
                if (posOpenLoansContainer) {
                    posOpenLoansContainer.innerHTML = '<div class="alert alert-danger py-2 font-size-12 mb-0">No se pudieron cargar los préstamos del cliente.</div>';
                }
            });
    });

    if (posOpenLoansContainer) {
        posOpenLoansContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-add-loan-abono');
            if (!btn) return;

            const tab = getActiveTab();
            const instId = btn.dataset.instId;

            // Validar que no se duplique el mismo período de pago en la misma cuenta
            const alreadyInTab = tab.items.some(it => it.section === 'prestamo_abono' && String(it.installment_id) === String(instId));
            if (alreadyInTab) {
                alert(`El Pago #${btn.dataset.instNum} ya está agregado al proceso actual.`);
                return;
            }

            // Obtener el monto ingresado para este período
            const inputEl = document.getElementById(`loan_inst_amt_${instId}`);
            const maxVal = parseFloat(btn.dataset.max) || 0;
            let amount = inputEl ? parseFloat(inputEl.value) : maxVal;

            if (isNaN(amount) || amount <= 0) {
                alert('Ingresa un monto de abono válido mayor a $0.00.');
                if (inputEl) inputEl.focus();
                return;
            }

            const opt = posLoanCustomerSelect ? posLoanCustomerSelect.options[posLoanCustomerSelect.selectedIndex] : null;
            addItemToCurrentTab({
                section: 'prestamo_abono',
                loan_id: btn.dataset.loanId,
                installment_id: instId,
                title: `Abono Préstamo ${btn.dataset.loanCode} (Pago #${btn.dataset.instNum})`,
                subtitle: `Día Programado: ${btn.dataset.scheduled}`,
                customer_id: posLoanCustomerSelect ? posLoanCustomerSelect.value : null,
                customer_name: opt ? opt.dataset.name : '',
                quantity: 1,
                amount: amount,
                commission: 0
            });

            renderOpenLoansList();
        });
    }

    const posNewLoanType = document.getElementById('posNewLoanType');
    const posNewLoanPrincipal = document.getElementById('posNewLoanPrincipal');
    const posNewLoanInstallment = document.getElementById('posNewLoanInstallment');
    const posNewLoanPeriods = document.getElementById('posNewLoanPeriods');
    const posNewLoanFreq = document.getElementById('posNewLoanFreq');
    const posNewLoanRate = document.getElementById('posNewLoanRate');
    const posNewLoanComm = document.getElementById('posNewLoanComm');
    const posNewLoanTotalPreview = document.getElementById('posNewLoanTotalPreview');
    const posNewLoanInterestPreview = document.getElementById('posNewLoanInterestPreview');

    let posLoanLastAction = 'installment';

    function recalcPosNewLoanFromInstallment() {
        posLoanLastAction = 'installment';
        const principal = parseFloat(posNewLoanPrincipal?.value) || 0;
        const installment = parseFloat(posNewLoanInstallment?.value) || 0;
        const periods = Math.max(1, parseInt(posNewLoanPeriods?.value) || 1);

        const total = installment * periods;
        const interest = Math.max(0, total - principal);
        const rate = principal > 0 ? ((interest / principal) * 100) : 0;

        if (posNewLoanRate) posNewLoanRate.value = rate.toFixed(2);
        if (posNewLoanTotalPreview) posNewLoanTotalPreview.textContent = '$' + total.toFixed(2);
        if (posNewLoanInterestPreview) posNewLoanInterestPreview.textContent = '$' + interest.toFixed(2) + ' (' + rate.toFixed(2) + '%)';
    }

    function recalcPosNewLoanFromRate() {
        posLoanLastAction = 'rate';
        const principal = parseFloat(posNewLoanPrincipal?.value) || 0;
        const rate = parseFloat(posNewLoanRate?.value) || 0;
        const periods = Math.max(1, parseInt(posNewLoanPeriods?.value) || 1);

        const interest = principal * (rate / 100);
        const total = principal + interest;
        const installment = total / periods;

        if (posNewLoanInstallment) posNewLoanInstallment.value = installment.toFixed(2);
        if (posNewLoanTotalPreview) posNewLoanTotalPreview.textContent = '$' + total.toFixed(2);
        if (posNewLoanInterestPreview) posNewLoanInterestPreview.textContent = '$' + interest.toFixed(2) + ' (' + rate.toFixed(2) + '%)';
    }

    if (posNewLoanInstallment) {
        posNewLoanInstallment.addEventListener('input', recalcPosNewLoanFromInstallment);
        if (posNewLoanRate) posNewLoanRate.addEventListener('input', recalcPosNewLoanFromRate);
        if (posNewLoanPrincipal) {
            posNewLoanPrincipal.addEventListener('input', () => {
                if (posLoanLastAction === 'rate') recalcPosNewLoanFromRate();
                else recalcPosNewLoanFromInstallment();
            });
        }
        if (posNewLoanPeriods) {
            posNewLoanPeriods.addEventListener('input', () => {
                if (posLoanLastAction === 'rate') recalcPosNewLoanFromRate();
                else recalcPosNewLoanFromInstallment();
            });
        }

        $('#posNewLoanType').on('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                if (opt.dataset.rate && posNewLoanRate) posNewLoanRate.value = opt.dataset.rate;
                if (opt.dataset.fee && posNewLoanComm) posNewLoanComm.value = opt.dataset.fee;
                if (opt.dataset.freq && posNewLoanFreq) posNewLoanFreq.value = opt.dataset.freq;
                if (opt.dataset.periods && posNewLoanPeriods) posNewLoanPeriods.value = opt.dataset.periods;
                recalcPosNewLoanFromRate();
            } else {
                recalcPosNewLoanFromInstallment();
            }
        });

        recalcPosNewLoanFromInstallment();
    }

    const btnAddLoanEl = document.getElementById('btnAddNewLoanToTab');
    if (btnAddLoanEl) {
        btnAddLoanEl.addEventListener('click', function () {
            const customerId = posLoanCustomerSelect ? posLoanCustomerSelect.value : '';
            if (!customerId) {
                alert('Selecciona primero al cliente del préstamo.');
                return;
            }
            const opt = posLoanCustomerSelect.options[posLoanCustomerSelect.selectedIndex];
            const typeSelect = document.getElementById('posNewLoanType');
            const typeOpt = typeSelect ? typeSelect.options[typeSelect.selectedIndex] : null;
            const principal = parseFloat(document.getElementById('posNewLoanPrincipal')?.value) || 0;
            const installment = parseFloat(document.getElementById('posNewLoanInstallment')?.value) || 0;
            const rate = parseFloat(document.getElementById('posNewLoanRate')?.value) || 0;
            const freq = document.getElementById('posNewLoanFreq')?.value || 'semanal';
            const periods = parseInt(document.getElementById('posNewLoanPeriods')?.value) || 4;
            const comm = parseFloat(document.getElementById('posNewLoanComm')?.value) || 0;

            const freqLabels = {
                '12_26': '12 y 26',
                'quincenal': 'Quincenal (15 y 30)',
                'semanal': 'Semanal',
                'mensual': 'Mensual'
            };
            const freqLabel = freqLabels[freq] || freq;
            const typeName = typeOpt && typeOpt.value ? typeOpt.text.trim() : 'Directo';

            addItemToCurrentTab({
                section: 'prestamo_apertura',
                customer_id: customerId,
                customer_name: opt ? opt.dataset.name : '',
                loan_type_id: typeSelect ? typeSelect.value || null : null,
                principal_amount: principal,
                installment_amount: installment,
                interest_rate: rate,
                payment_frequency: freq,
                term_periods: periods,
                title: `Apertura Préstamo (${typeName})`,
                subtitle: `Capital $${principal.toFixed(2)} | ${periods} pagos de $${installment.toFixed(2)} (${freqLabel}) | Solo cobra Comisión en caja`,
                quantity: 1,
                amount: 0,
                commission: comm
            });
        });
    }

    // ==================== 3. TANDAS EN CONSOLA CENTRAL ====================
    const posTandaSelect = document.getElementById('posTandaSelect');
    const posTandaParticipantSelect = document.getElementById('posTandaParticipantSelect');
    const posTandaAmount = document.getElementById('posTandaAmount');

    $('#posTandaSelect').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (posTandaParticipantSelect) {
            posTandaParticipantSelect.innerHTML = '<option value="">-- Selecciona participante existente --</option>';
        }
        if (!opt || !opt.value) {
            $('#posTandaParticipantSelect').trigger('change.select2');
            return;
        }

        if (posTandaAmount) {
            posTandaAmount.value = parseFloat(opt.dataset.amount || 0).toFixed(2);
        }
        let participants = [];
        try {
            participants = JSON.parse(opt.dataset.participants || '[]');
        } catch (e) {}

        if (posTandaParticipantSelect) {
            participants.forEach(p => {
                const o = document.createElement('option');
                o.value = p.id;
                o.dataset.name = p.name;
                o.textContent = `#${p.position} - ${p.name} (${p.phone || '-'})`;
                posTandaParticipantSelect.appendChild(o);
            });
        }
        $('#posTandaParticipantSelect').trigger('change.select2');
    });

    const btnAddTandaEl = document.getElementById('btnAddTandaToTab');
    if (btnAddTandaEl) {
        btnAddTandaEl.addEventListener('click', function () {
            const tandaId = posTandaSelect ? posTandaSelect.value : '';
            if (!tandaId) {
                alert('Selecciona una tanda.');
                return;
            }
            const tandaOpt = posTandaSelect.options[posTandaSelect.selectedIndex];
            const participantId = posTandaParticipantSelect ? posTandaParticipantSelect.value : '';
            const newName = document.getElementById('posTandaNewName')?.value?.trim() || '';
            const newPhone = document.getElementById('posTandaNewPhone')?.value?.trim() || '';
            const newPos = document.getElementById('posTandaNewPos')?.value || '';
            const periodNo = parseInt(document.getElementById('posTandaPeriodNo')?.value) || 1;
            const amount = parseFloat(posTandaAmount?.value) || 0;

            if (!participantId && !newName) {
                alert('Selecciona un participante de la tanda o escribe el nombre del nuevo cliente.');
                return;
            }

            const pName = participantId && posTandaParticipantSelect
                ? posTandaParticipantSelect.options[posTandaParticipantSelect.selectedIndex].dataset.name
                : newName;

            addItemToCurrentTab({
                section: 'tanda_abono',
                tanda_id: tandaId,
                participant_id: participantId || null,
                customer_name: pName,
                customer_phone: newPhone,
                position: newPos,
                period_number: periodNo,
                title: `Tanda: ${tandaOpt?.dataset?.name || ''} (Periodo #${periodNo})`,
                subtitle: `Participante: ${pName}`,
                quantity: 1,
                amount: amount,
                commission: 0
            });
        });
    }

    // ==================== 4. PRODUCTOS (STOCK REAL + DINÁMICOS SIN STOCK) ====================
    const catalogBodyEl = document.getElementById('posCatalogProductsBody');
    if (catalogBodyEl) {
        catalogBodyEl.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-add-stock-product');
            if (!btn) return;
            const initialPrice = parseFloat(btn.dataset.price) || 0;
            addItemToCurrentTab({
                section: 'producto_inventario',
                product_id: btn.dataset.id,
                product_code: btn.dataset.code || '',
                imei: btn.dataset.imei || '',
                max_stock: parseInt(btn.dataset.stock) || 0,
                title: btn.dataset.name,
                subtitle: `Impacto real en Inventario${btn.dataset.code ? ' | Código: ' + btn.dataset.code : ''}${btn.dataset.imei ? ' | IMEI: ' + btn.dataset.imei : ''}`,
                quantity: 1,
                amount: initialPrice,
                original_amount: initialPrice,
                commission: 0
            });
        });
    }

    const btnAddDynEl = document.getElementById('btnAddDynamicProductToTab');
    if (btnAddDynEl) {
        btnAddDynEl.addEventListener('click', function () {
            const nameEl = document.getElementById('dynProdName');
            const priceEl = document.getElementById('dynProdPrice');
            const qtyEl = document.getElementById('dynProdQty');
            const name = nameEl ? nameEl.value.trim() : '';
            const priceVal = priceEl ? priceEl.value.trim() : '';
            const price = parseFloat(priceVal);
            const qty = Math.max(1, parseInt(qtyEl ? qtyEl.value : 1) || 1);
            if (!name || priceVal === '' || isNaN(price) || price < 0) {
                alert('Ingresa un nombre y un precio válido (0.00 o mayor) del producto manual.');
                return;
            }
            const brand = document.getElementById('dynProdBrand')?.value.trim() || '';
            const model = document.getElementById('dynProdModel')?.value.trim() || '';
            const imei = document.getElementById('dynProdImei')?.value.trim() || '';
            const status = document.getElementById('dynProdStatus')?.value || 'Nuevo';
            const warranty = document.getElementById('dynProdWarranty')?.value.trim() || '';
            const obs = document.getElementById('dynProdObs')?.value.trim() || '';

            addItemToCurrentTab({
                section: 'producto_dinamico',
                name: name,
                brand: brand,
                model: model,
                imei: imei,
                category_status: status,
                warranty_time: warranty,
                observations: obs,
                title: `${name} (${status})`,
                subtitle: `Sin impacto en inventario ${imei ? '| IMEI: ' + imei : ''}`,
                quantity: qty,
                amount: price,
                original_amount: price,
                commission: 0
            });

            if (nameEl) nameEl.value = '';
            if (priceEl) priceEl.value = '0.00';
            if (qtyEl) qtyEl.value = '1';
            if (document.getElementById('dynProdImei')) document.getElementById('dynProdImei').value = '';
            if (document.getElementById('dynProdObs')) document.getElementById('dynProdObs').value = '';
        });

        // Soporte tecla Enter en nombre y precio de producto libre
        const dynNameInp = document.getElementById('dynProdName');
        const dynPriceInp = document.getElementById('dynProdPrice');
        if (dynNameInp) {
            dynNameInp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (dynPriceInp) dynPriceInp.focus();
                }
            });
        }
        if (dynPriceInp) {
            dynPriceInp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnAddDynEl.click();
                }
            });
        }
    }

    // ==================== 4C. REGALOS / PROMOCIONALES ($0.00 DESCONTANDO INVENTARIO SUCURSAL) ====================
    const posGiftsBody = document.getElementById('posGiftsTableBody');
    if (posGiftsBody) {
        posGiftsBody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-add-gift-item');
            if (!btn) return;

            const giftId = btn.dataset.id;
            const maxStock = Math.max(0, parseInt(btn.dataset.stock) || 0);
            if (maxStock <= 0) {
                showCartFeedback('warning', `Sin stock disponible de "${btn.dataset.name}" en esta sucursal.`);
                return;
            }

            addItemToCurrentTab({
                section: 'gift',
                gift_id: giftId,
                title: `${btn.dataset.name} (Regalo)`,
                subtitle: btn.dataset.code ? `Código: ${btn.dataset.code}` : '',
                quantity: 1,
                amount: 0,
                original_amount: 0,
                commission: 0,
                max_stock: maxStock
            });
        });
    }

    function searchPosGifts(query) {
        if (!posGiftsBody) return;
        const url = `{{ route('central-pos.lookup-gifts', [], false) }}?branch_id={{ $branchId }}&q=${encodeURIComponent(query || '')}`;
        posFetchJson(url, { timeoutMs: 15000 })
            .then(data => {
                const gifts = data.gifts || [];
                posGiftsBody.innerHTML = '';
                if (!gifts.length) {
                    posGiftsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No se encontraron regalos con stock en esta sucursal.</td></tr>';
                    return;
                }
                gifts.forEach(g => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><span class="badge badge-light border">${g.code || '-'}</span></td>
                        <td>
                            <strong>${g.name}</strong>
                            <small class="d-block text-muted">${g.brand ? g.brand + ' • ' : ''}Prov: ${g.supplier_name || '-'}</small>
                        </td>
                        <td class="text-center"><span class="badge badge-pill badge-success">${g.stock_quantity}</span></td>
                        <td class="text-right font-weight-bold text-success">$0.00</td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-success btn-add-gift-item"
                                data-id="${g.id}" data-name="${g.name}" data-code="${g.code || ''}"
                                data-supplier="${g.supplier_name || ''}" data-stock="${g.stock_quantity}">
                                <i class="fa-solid fa-plus"></i> Agregar
                            </button>
                        </td>
                    `;
                    posGiftsBody.appendChild(tr);
                });
            })
            .catch(() => {});
    }

    const posGiftSearchInput = document.getElementById('posGiftSearchInput');
    const btnSearchPosGift = document.getElementById('btnSearchPosGift');
    if (btnSearchPosGift && posGiftSearchInput) {
        btnSearchPosGift.addEventListener('click', () => searchPosGifts(posGiftSearchInput.value.trim()));
        posGiftSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchPosGifts(posGiftSearchInput.value.trim());
            }
        });
    }

    // ==================== 5. CHECKOUT UNIFICADO Y APERTURA DE CAJÓN ====================
    const btnOpenPhysDrawer = document.getElementById('btnOpenPhysicalDrawer');
    if (btnOpenPhysDrawer) {
        btnOpenPhysDrawer.addEventListener('click', function () {
            window.triggerPhysicalCashDrawer();
        });
    }

    async function showPendingCustomerRequiredAlert(dueAmount) {
        const selectEl = document.getElementById('posCartCustomerSelect');
        if (typeof Swal !== 'undefined') {
            const swalResult = await Swal.fire({
                icon: 'warning',
                title: 'Cliente obligatorio para Saldo Pendiente',
                html: `
                    <p class="font-size-13 mb-2">Para registrar una nota con saldo pendiente de <strong class="text-danger">$${dueAmount.toFixed(2)}</strong>, debes seleccionar un <strong>cliente específico registrado</strong> en el catálogo.</p>
                    <p class="font-size-12 text-muted mb-0">No se permite dejar crédito a <em>Público General</em> ni a clientes sin registrar.</p>
                `,
                showCancelButton: CAN_CREATE_CUSTOMERS,
                confirmButtonText: '<i class="fa-solid fa-magnifying-glass mr-1"></i>Seleccionar Cliente',
                confirmButtonColor: '#d97706',
                cancelButtonText: '<i class="fa-solid fa-user-plus mr-1"></i>Dar de Alta Cliente',
                cancelButtonColor: '#2563eb',
                showCloseButton: true,
            });
            if (swalResult.dismiss === Swal.DismissReason.cancel && CAN_CREATE_CUSTOMERS) {
                openedNewCustomerTab = true;
                window.open(CUSTOMERS_CREATE_URL, '_blank');
                return;
            }
        } else {
            alert('⚠️ Para dejar Saldo Pendiente ($' + dueAmount.toFixed(2) + '), selecciona un cliente específico registrado (no se permite Público General ni cliente sin registrar).');
        }

        if (selectEl) {
            selectEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try {
                $(selectEl).select2('open');
            } catch (_) {
                selectEl.focus();
            }
        }
    }

    function describePayMethodForConfirm(payState) {
        if (payState.paidToday <= 0) {
            return 'Sin abono inicial hoy ($0.00)';
        }
        if (payState.method === 'mixto') {
            const parts = [];
            if (payState.cashAmt > 0) parts.push('Efectivo $' + payState.cashAmt.toFixed(2));
            if (payState.cardAmt > 0) parts.push('Tarjeta $' + payState.cardAmt.toFixed(2));
            if (payState.transAmt > 0) parts.push('Transferencia $' + payState.transAmt.toFixed(2));
            return 'Mixto (' + parts.join(' + ') + ')';
        }
        const labels = {
            efectivo: 'Efectivo',
            tarjeta: 'Tarjeta',
            transferencia: 'Transferencia',
        };
        return labels[payState.method] || payState.method.toUpperCase();
    }

    async function confirmPendingSaleDialog(tab, payState) {
        const methodDesc = describePayMethodForConfirm(payState);
        if (typeof Swal === 'undefined') {
            return window.confirm(
                `Confirmar Nota con Saldo Pendiente\n\n` +
                `Cliente: ${tab.customerName}\n` +
                `Total de Venta: $${payState.saleTotal.toFixed(2)}\n` +
                `Monto Pagado Hoy: $${payState.paidToday.toFixed(2)} (${methodDesc})\n` +
                `Saldo Pendiente a Cuenta: $${payState.dueAmount.toFixed(2)}\n\n` +
                `¿Deseas registrar esta venta y generar el ticket?`
            );
        }

        const result = await Swal.fire({
            icon: 'question',
            title: 'Confirmar Venta con Saldo Pendiente',
            html: `
                <div class="text-left border rounded p-3 bg-light font-size-13">
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-muted">Cliente:</span>
                        <strong class="text-dark">${escapeHtml(tab.customerName)}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total de Venta:</span>
                        <strong class="text-dark">$${payState.saleTotal.toFixed(2)}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Monto Pagado Hoy:</span>
                        <strong class="text-success">$${payState.paidToday.toFixed(2)}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Método:</span>
                        <span class="font-weight-bold text-dark">${escapeHtml(methodDesc)}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top text-danger" style="font-size: 15px;">
                        <strong>Saldo Pendiente a Cuenta:</strong>
                        <strong>$${payState.dueAmount.toFixed(2)}</strong>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-check mr-1"></i>Confirmar y Generar Ticket',
            cancelButtonText: 'Cancelar y Revisar',
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
        });

        return Boolean(result && result.isConfirmed);
    }

    const btnCompleteSaleEl = document.getElementById('btnCompleteUnifiedSale');
    let isCheckoutInFlight = false;
    if (btnCompleteSaleEl) {
        btnCompleteSaleEl.addEventListener('click', async function () {
            if (isCheckoutInFlight || this.disabled) {
                return;
            }

            const tab = getActiveTab();

            // Si el usuario capturó un Producto Libre pero olvidó dar click en "Añadir", agregarlo automáticamente
            if (tab.items.length === 0) {
                const dynNameEl = document.getElementById('dynProdName');
                const dynPriceEl = document.getElementById('dynProdPrice');
                const dynName = dynNameEl ? dynNameEl.value.trim() : '';
                const dynPrice = dynPriceEl ? parseFloat(dynPriceEl.value) : NaN;
                if (dynName && !isNaN(dynPrice) && dynPrice >= 0) {
                    const addDynBtn = document.getElementById('btnAddDynamicProductToTab');
                    if (addDynBtn) {
                        addDynBtn.click();
                    }
                }
            }

            if (tab.items.length === 0) {
                alert('Esta pestaña no tiene conceptos agregados. Agrega productos, préstamos, financieras o tandas antes de cerrar el cobro.');
                return;
            }

            const payState = evaluateTabPaymentState(tab);

            if (payState.isBlankInput) {
                alert('⚠️ Ingresa el monto a pagar hoy (o captura 0.00 si no habrá abono inicial).');
                const focusTargetId = payState.method === 'efectivo'
                    ? 'posCashAmountInput'
                    : (payState.method === 'tarjeta' ? 'posCardAmountInput' : 'posTransferAmountInput');
                document.getElementById(focusTargetId)?.focus();
                return;
            }

            if (payState.isOverpaidNonCash) {
                alert(`⚠️ El monto asignado ($${payState.rawEntered.toFixed(2)}) excede el total de la venta ($${payState.saleTotal.toFixed(2)}) por $${payState.overpayExcess.toFixed(2)}. Ajusta el monto para continuar.`);
                return;
            }

            if (payState.isPendingMode) {
                if (payState.hasPendingNoteAbono) {
                    alert('⚠️ No se permite pago pendiente sobre pago pendiente: al incluir el abono de una nota pendiente en el carrito debes cubrir el 100% del total ($' + payState.saleTotal.toFixed(2) + ').');
                    const focusId = payState.method === 'efectivo'
                        ? 'posCashAmountInput'
                        : (payState.method === 'tarjeta' ? 'posCardAmountInput' : (payState.method === 'transferencia' ? 'posTransferAmountInput' : 'mixCashInput'));
                    document.getElementById(focusId)?.focus();
                    return;
                }
                if (payState.hasFinAbono) {
                    alert('⚠️ Los abonos a financieras no permiten pago parcial ni dejar saldo pendiente. Cubre el total completo o edita directamente el monto del abono.');
                    return;
                }
                if (!payState.hasProductConcept) {
                    alert('⚠️ Para dejar saldo pendiente en Nota por Cobrar debe existir al menos un producto en el carrito. En préstamos o tandas, edita directamente el importe del concepto.');
                    return;
                }
                if (!payState.hasSpecificCustomer) {
                    await showPendingCustomerRequiredAlert(payState.dueAmount);
                    return;
                }

                const confirmed = await confirmPendingSaleDialog(tab, payState);
                if (!confirmed) {
                    return;
                }
            }

            const refs = {};
            if (payState.method === 'tarjeta' || payState.method === 'mixto') {
                refs.card_ref = tab.cardRef || '';
            }
            if (payState.method === 'transferencia' || payState.method === 'mixto') {
                refs.transfer_ref = tab.transferRef || '';
            }

            const shouldAutoOpenDrawer = !!document.getElementById('posOpenDrawerCheck')?.checked && (
                (payState.method === 'efectivo' && payState.cashAmt > 0) ||
                (payState.method === 'mixto' && payState.cashAmt > 0)
            );
            const shouldAutoPrint = !!document.getElementById('posAutoPrintCheck')?.checked;

            const btn = this;
            isCheckoutInFlight = true;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Procesando Cobro...';

            posFetchJson("{{ route('central-pos.checkout', [], false) }}", {
                method: 'POST',
                timeoutMs: 30000,
                json: {
                    branch_id: '{{ $branchId }}',
                    customer_id: (CAN_POS_EXTERNAL_CUSTOMERS && tab.externalCustomerId) ? null : (tab.customerId || null),
                    external_customer_id: (CAN_POS_EXTERNAL_CUSTOMERS && tabHasFinancieraConcept(tab) && tab.externalCustomerId) ? tab.externalCustomerId : null,
                    customer_name: tab.customerName || 'Cliente Mostrador',
                    customer_phone: tab.customerPhone || null,
                    payment_method: payState.method,
                    cash_amount: payState.cashAmt,
                    card_amount: payState.cardAmt,
                    transfer_amount: payState.transAmt,
                    payment_references: refs,
                    cash_received: payState.cashReceived,
                    paid_amount: payState.paidToday,
                    open_drawer: document.getElementById('posOpenDrawerCheck')?.checked ? 1 : 0,
                    items: tab.items
                }
            })
            .then(async res => {
                if (!res.success) {
                    alert(res.message || 'No se pudo procesar el cobro.');
                    return;
                }

                // Limpiar el proceso actual tras cerrarlo exitosamente
                tab.items = [];
                tab.customerName = '';
                tab.customerPhone = '';
                tab.customerId = '';
                tab.externalCustomerId = '';
                tab.cashAmount = '';
                tab.cardAmount = '';
                tab.transferAmount = '';
                tab.cashTouched = false;
                tab.cardTouched = false;
                tab.transferTouched = false;
                tab.mixTouched = false;
                tab.cardRef = '';
                tab.transferRef = '';
                tab.mixCash = 0;
                tab.mixCard = 0;
                tab.mixTransfer = 0;
                tab.mixCashReceived = '';
                saveState();
                renderAll();

                // Apertura AUTOMÁTICA del cajón por USB esperando cierre limpio del puerto antes de mandar imprimir al spooler
                if (shouldAutoOpenDrawer && window.ZyaPosHardware) {
                    try {
                        if (typeof window.ZyaPosHardware.autoOpenUsbDrawerBounded === 'function') {
                            await window.ZyaPosHardware.autoOpenUsbDrawerBounded(700);
                        } else if (typeof window.ZyaPosHardware.autoOpenUsbDrawer === 'function') {
                            await window.ZyaPosHardware.autoOpenUsbDrawer();
                        }
                    } catch (hwErr) {
                        console.warn('Error al abrir cajón USB:', hwErr);
                    }
                }

                const normalizedReceiptUrl = normalizePosUrl(res.receipt_url);
                if (normalizedReceiptUrl) {
                    showSaleSuccessBanner(res.transaction_code, normalizedReceiptUrl);
                    const isTouch = Boolean(window._zyaTicketBrowser && window._zyaTicketBrowser.isTouchMobile());

                    if (shouldAutoPrint && !isTouch) {
                        const sep = normalizedReceiptUrl.includes('?') ? '&' : '?';
                        let previewOpened = false;
                        const openPreviewAfterPrint = function () {
                            if (previewOpened) return;
                            previewOpened = true;
                            if (window.openTicketPreviewModal) {
                                window.openTicketPreviewModal(
                                    normalizedReceiptUrl,
                                    'Ticket ' + res.transaction_code
                                );
                            }
                        };
                        const previewFallbackTimer = setTimeout(openPreviewAfterPrint, 1800);
                        printTicketDirectly(
                            normalizedReceiptUrl + sep + 'skip_drawer=1&paper=' + getTicketPaperMode(),
                            {
                                userInitiated: false,
                                skipModalFallback: true,
                                title: 'Ticket ' + res.transaction_code,
                                onPrintTriggered: function () {
                                    clearTimeout(previewFallbackTimer);
                                    openPreviewAfterPrint();
                                }
                            }
                        );
                    } else if (window.openTicketPreviewModal) {
                        window.openTicketPreviewModal(
                            normalizedReceiptUrl,
                            'Ticket ' + res.transaction_code
                        );
                    }
                }
            })
            .catch(err => {
                console.error('Error al procesar el cobro:', err);
                alert(err.message || 'Error de red o comunicación al procesar el movimiento.');
            })
            .finally(() => {
                isCheckoutInFlight = false;
                btn.disabled = false;
                refreshLivePaymentUI();
            });
        });
    }

    function printTicketDirectly(url, options) {
        if (typeof window.printTicketDirectly === 'function') {
            window.printTicketDirectly(url, options);
        }
    }

    function showSaleSuccessBanner(txCode, receiptUrl) {
        const alertEl = document.getElementById('posSaleSuccessAlert');
        const codeEl = document.getElementById('posSuccessTxCode');
        const reBtn = document.getElementById('posBtnReimprimir');
        const viewBtn = document.getElementById('posBtnVerTicket');
        if (alertEl && codeEl) {
            codeEl.textContent = txCode;
            if (reBtn) {
                reBtn.onclick = function() {
                    const paperMode = getTicketPaperMode();
                    const sep = receiptUrl.includes('?') ? '&' : '?';
                    printTicketDirectly(receiptUrl + sep + 'skip_drawer=1&paper=' + paperMode, {
                        userInitiated: true,
                        title: 'Ticket ' + txCode
                    });
                };
            }
            if (viewBtn) {
                viewBtn.onclick = function() {
                    if (window.openTicketPreviewModal) {
                        window.openTicketPreviewModal(receiptUrl, 'Ticket ' + txCode);
                    }
                };
            }
            alertEl.classList.remove('d-none');
            alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function loadPosTicketHistory() {
        const tbody = document.getElementById('posHistoryTableBody');
        if (!tbody) return;
        const dateVal = document.getElementById('posHistoryDateInput')?.value || '';
        const searchVal = document.getElementById('posHistorySearchInput')?.value.trim() || '';
        const url = `{{ route('central-pos.recent-transactions', [], false) }}?branch_id={{ $branchId }}&date=${encodeURIComponent(dateVal)}&search=${encodeURIComponent(searchVal)}`;

        posFetchJson(url, { timeoutMs: 15000 })
            .then(data => {
                const list = data.transactions || [];
                tbody.innerHTML = '';
                if (!list.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Sin tickets encontrados para el filtro seleccionado.</td></tr>';
                    return;
                }
                list.forEach(tx => {
                    const tr = document.createElement('tr');
                    const statusBadge = tx.status === 'cancelado'
                        ? '<span class="badge badge-danger ml-1">Cancelado</span>'
                        : '';
                    const extBadge = tx.is_external_customer
                        ? '<span class="badge badge-warning text-dark font-size-10 ml-1">Externo</span>'
                        : '';
                    tr.innerHTML = `
                        <td><span class="badge badge-dark">${tx.transaction_code}</span>${statusBadge}</td>
                        <td>${tx.created_at}</td>
                        <td class="font-weight-bold">${tx.customer_name}${extBadge}</td>
                        <td class="text-muted">${tx.concepts || '-'}</td>
                        <td>${tx.payment_method}</td>
                        <td class="text-right font-weight-bold">$${tx.total_amount}</td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold mr-1 btn-hist-preview">
                                <i class="fa-solid fa-eye mr-1"></i>Ver
                            </button>
                            <button type="button" class="btn btn-xs btn-dark font-weight-bold btn-hist-print">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </td>
                    `;
                    const txReceiptUrl = normalizePosUrl(tx.receipt_url);
                    tr.querySelector('.btn-hist-preview').addEventListener('click', () => {
                        if (window.openTicketPreviewModal && txReceiptUrl) {
                            window.openTicketPreviewModal(txReceiptUrl, 'Ticket ' + tx.transaction_code);
                        }
                    });
                    tr.querySelector('.btn-hist-print').addEventListener('click', () => {
                        if (!txReceiptUrl) return;
                        const paperMode = getTicketPaperMode();
                        const sep = txReceiptUrl.includes('?') ? '&' : '?';
                        printTicketDirectly(txReceiptUrl + sep + 'skip_drawer=1&paper=' + paperMode, {
                            userInitiated: true,
                            title: 'Ticket ' + tx.transaction_code
                        });
                    });
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">${err?.message || 'No se pudo cargar el historial de tickets.'}</td></tr>`;
            });
    }

    const btnFilterHistory = document.getElementById('btnFilterPosHistory');
    const histSearchInp = document.getElementById('posHistorySearchInput');
    if (btnFilterHistory) {
        btnFilterHistory.addEventListener('click', loadPosTicketHistory);
    }
    if (histSearchInp) {
        histSearchInp.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadPosTicketHistory();
            }
        });
    }
    $('#posHistoryModal').on('shown.bs.modal', loadPosTicketHistory);

    renderAll();
});
</script>
@endsection
