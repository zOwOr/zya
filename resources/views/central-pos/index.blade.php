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
    .cart-items-scroll {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
    }
    .cart-items-scroll::-webkit-scrollbar {
        width: 5px;
    }
    .cart-items-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
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
                <button type="button" id="btnOpenPhysicalDrawer" class="btn btn-warning btn-sm mr-2 shadow-sm font-weight-bold">
                    <i class="las la-lock-open mr-1"></i> Abrir Cajón Físico
                </button>
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
                                                    <tr>
                                                        <td><span class="badge badge-light border">{{ $g->code }}</span></td>
                                                        <td>
                                                            <strong>{{ $g->name }}</strong>
                                                            <small class="d-block text-muted">
                                                                {{ $g->brand ? $g->brand . ' • ' : '' }}Prov: {{ $g->supplier_display_name }}
                                                            </small>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-pill badge-success">{{ $g->stock_quantity }}</span>
                                                        </td>
                                                        <td class="text-right font-weight-bold text-success">$0.00</td>
                                                        <td class="text-right">
                                                            <button type="button"
                                                                class="btn btn-xs btn-success btn-add-gift-item"
                                                                data-id="{{ $g->id }}"
                                                                data-name="{{ $g->name }}"
                                                                data-code="{{ $g->code }}"
                                                                data-supplier="{{ $g->supplier_display_name }}"
                                                                data-stock="{{ $g->stock_quantity }}">
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
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="font-size-11 font-weight-bold text-muted text-uppercase mb-0">
                                            <i class="fa-solid fa-user-check text-primary mr-1"></i>Cliente para Ticket y Caja
                                        </label>
                                        @if ($canPosExternalCustomers)
                                            <div id="posExternalCustomerTriggerWrap" class="d-none">
                                                <button type="button" id="btnTriggerExternalCustomer" class="btn btn-xs btn-warning text-dark font-weight-bold shadow-sm" disabled>
                                                    <i class="fa-solid fa-user-tag mr-1"></i>Cliente Externo
                                                </button>
                                            </div>
                                        @endif
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

                            <!-- Lista de Conceptos en Carrito -->
                            <div class="table-responsive cart-items-scroll mb-3">
                                <table class="table table-sm table-hover mb-0 font-size-12">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Concepto</th>
                                            <th class="text-center" style="width: 52px;">Cant.</th>
                                            <th class="text-right" style="width: 110px;">Importe</th>
                                            <th class="text-right" style="width: 70px;">Comisión</th>
                                            <th style="width: 35px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabCartItemsBody"></tbody>
                                </table>
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

                            <!-- Saldo Pendiente / Pago Parcial (Queda debiendo) -->
                            <div class="card border border-warning bg-light p-2 mb-2 rounded shadow-none">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="font-size-12 font-weight-bold text-dark">
                                        <i class="fa-solid fa-clock-rotate-left text-warning mr-1"></i>¿Pago Parcial o Saldo Pendiente?
                                    </span>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="posPartialPayCheck">
                                        <label class="custom-control-label font-size-11 font-weight-bold text-primary" for="posPartialPayCheck">Activar</label>
                                    </div>
                                </div>
                                <div id="posPartialPayDetails" class="d-none mt-2 pt-2 border-top">
                                    <div class="row">
                                        <div class="col-6 pr-1">
                                            <label class="font-size-11 font-weight-bold text-success mb-0">Abonar / Cobrar Hoy ($)</label>
                                            <input type="number" step="0.01" min="0" id="posPaidTodayInput" class="form-control form-control-sm font-weight-bold text-success font-size-13" placeholder="0.00">
                                        </div>
                                        <div class="col-6 pl-1">
                                            <label class="font-size-11 font-weight-bold text-danger mb-0">Saldo Pendiente ($)</label>
                                            <input type="text" id="posDueBalanceText" class="form-control form-control-sm font-weight-bold text-danger font-size-13 bg-white" readonly value="$0.00">
                                        </div>
                                    </div>
                                    <small class="d-block text-danger mt-1 font-size-11 font-weight-bold">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>El cliente quedará registrado con este saldo pendiente en "Notas con Saldo Pendiente".
                                    </small>
                                </div>
                            </div>

                            <!-- Método de Pago Visual Moderno -->
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

                            <!-- SECCIÓN DINÁMICA DE DETALLE Y CAPTURA POR MÉTODO DE PAGO -->
                            
                            <!-- 1. Detalle para Efectivo -->
                            <div id="posBoxEfectivo" class="p-2 rounded border mb-2 bg-light">
                                <div class="row mb-2">
                                    <div class="col-6 pr-1">
                                        <label class="font-size-11 font-weight-bold text-success mb-0 text-uppercase">Monto Efectivo ($)</label>
                                        <input type="number" step="0.01" min="0" id="posCashAmountInput" class="form-control form-control-sm font-weight-bold text-success font-size-14" placeholder="0.00">
                                    </div>
                                    <div class="col-6 pl-1">
                                        <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Efectivo Recibido ($)</label>
                                        <input type="number" step="0.01" min="0" id="posCashReceivedInput" class="form-control form-control-sm font-weight-bold font-size-14" placeholder="0.00">
                                    </div>
                                </div>
                                <div>
                                    <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Cambio a Entregar</label>
                                    <input type="text" id="posChangeOutput" class="form-control form-control-sm cash-change-display" value="$0.00" readonly>
                                </div>
                            </div>

                            <!-- 2. Detalle para Tarjeta -->
                            <div id="posBoxTarjeta" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="row">
                                    <div class="col-6 pr-1">
                                        <label class="font-size-11 font-weight-bold text-primary mb-0 text-uppercase">Monto Tarjeta ($)</label>
                                        <input type="number" step="0.01" min="0" id="posCardAmountInput" class="form-control form-control-sm font-weight-bold text-primary font-size-14" placeholder="0.00">
                                    </div>
                                    <div class="col-6 pl-1">
                                        <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">No. Autoriz. / Voucher</label>
                                        <input type="text" id="posCardRefInput" class="form-control form-control-sm font-size-13" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Detalle para Transferencia -->
                            <div id="posBoxTransferencia" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="row">
                                    <div class="col-6 pr-1">
                                        <label class="font-size-11 font-weight-bold text-transfer mb-0 text-uppercase">Monto Transf. ($)</label>
                                        <input type="number" step="0.01" min="0" id="posTransferAmountInput" class="form-control form-control-sm font-weight-bold text-transfer font-size-14" placeholder="0.00">
                                    </div>
                                    <div class="col-6 pl-1">
                                        <label class="font-size-11 font-weight-bold text-muted mb-0 text-uppercase">Folio SPEI / Rastreo</label>
                                        <input type="text" id="posTransferRefInput" class="form-control form-control-sm font-size-13" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Detalle para Pago Mixto con Notificación de Cantidad Faltante -->
                            <div id="posBoxMixto" class="p-2 rounded border mb-2 bg-light d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="font-weight-bold text-warning text-uppercase">
                                        <i class="fa-solid fa-sliders mr-1"></i>Captura de Pago Mixto:
                                    </small>
                                    <small class="text-muted font-size-11">Total: <strong id="lblMixTargetAmount" class="text-dark">$0.00</strong></small>
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

                                <!-- Notificación en Tiempo Real de Cantidad Faltante en Pago Mixto -->
                                <div id="posMixFaltanteAlertContainer" class="mb-2">
                                    <!-- Render dinámico por JS -->
                                </div>

                                <!-- Efectivo Recibido y Cambio para la porción en efectivo de Pago Mixto -->
                                <div id="posMixCashDrawerRow" class="row mb-1">
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
                                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold mr-1" onclick="window.openTicketPreviewModal('{{ route('central-pos.receipt', $rTx->id) }}', 'Ticket {{ $rTx->transaction_code }}')">
                                            <i class="fa-solid fa-eye mr-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-xs btn-dark font-weight-bold" onclick="window.printTicketDirectly('{{ route('central-pos.receipt', $rTx->id) }}')">
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
                mixCash: 0,
                mixCard: 0,
                mixTransfer: 0,
                cashReceived: '',
                items: []
            }
        ]
    };

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed && Array.isArray(parsed.tabs) && parsed.tabs.length > 0) {
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
        return state.tabs.find(t => t.id === state.activeTabId) || state.tabs[0];
    }

    function tabHasFinancieraConcept(tab) {
        return Boolean(
            tab
            && Array.isArray(tab.items)
            && tab.items.some(it => it.section === 'financiera_abono' || it.section === 'financiera_externa')
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
            const count = tab.items.length;
            div.innerHTML = `
                <span class="tab-label"><i class="fa-solid fa-receipt mr-1 text-primary"></i>${tab.name}${tab.customerName ? ' (' + tab.customerName.substring(0, 12) + ')' : ''}</span>
                <span class="tab-badge">${count}</span>
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
            mixCash: 0,
            mixCard: 0,
            mixTransfer: 0,
            cashReceived: '',
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

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderCustomerPendingNotesBox(data, options = {}) {
        if (!pendingNotesBox) return;

        if (options.hidden) {
            pendingNotesBox.classList.add('d-none');
            pendingNotesBox.innerHTML = '';
            return;
        }

        pendingNotesBox.classList.remove('d-none');

        if (options.loading) {
            pendingNotesBox.innerHTML = `
                <div class="font-size-11 text-muted d-flex align-items-center py-1">
                    <i class="fa-solid fa-spinner fa-spin mr-1 text-primary"></i> Verificando notas pendientes del cliente...
                </div>
            `;
            return;
        }

        if (options.error) {
            pendingNotesBox.innerHTML = `
                <div class="font-size-11 text-muted py-1">
                    <i class="fa-solid fa-circle-info mr-1"></i>No se pudieron consultar las notas pendientes.
                </div>
            `;
            return;
        }

        if (!data || !data.searched) {
            pendingNotesBox.classList.add('d-none');
            pendingNotesBox.innerHTML = '';
            return;
        }

        if (!data.has_pending_notes || !Array.isArray(data.pending_notes) || data.pending_notes.length === 0) {
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

        const totalDueFormatted = Number(data.total_due || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rowsHtml = data.pending_notes.map(note => {
            const totalFmt = Number(note.total || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const payFmt = Number(note.pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dueFmt = Number(note.due || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const linksHtml = (note.detail_url || note.pending_due_url) ? `
                <div class="mt-1 d-flex justify-content-end" style="gap: 6px;">
                    ${note.detail_url ? `<a href="${escapeHtml(note.detail_url)}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2 font-size-11"><i class="fa-solid fa-eye mr-1"></i>Ver nota</a>` : ''}
                    ${note.pending_due_url ? `<a href="${escapeHtml(note.pending_due_url)}" target="_blank" class="btn btn-xs btn-warning text-dark font-weight-bold py-0 px-2 font-size-11"><i class="fa-solid fa-hand-holding-dollar mr-1"></i>Abonar / Ver en Notas</a>` : ''}
                </div>
            ` : '';

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
                    ${linksHtml}
                </div>
            `;
        }).join('');

        pendingNotesBox.innerHTML = `
            <div class="alert alert-warning border-warning mb-0 p-2 shadow-none">
                <div class="d-flex justify-content-between align-items-center">
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
                <div style="max-height: 185px; overflow-y: auto;">
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

        fetch(`{{ route('central-pos.lookup-pending-notes') }}?${params.toString()}`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
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
            }
        });

        window.addEventListener('focus', function () {
            const tab = getActiveTab();
            if (tab && (tab.customerId || (tab.customerName && tab.customerName.trim().length >= 3))) {
                syncCustomerPendingNotes(tab, true);
            }
        });
    }

    function renderActiveCart() {
        const tab = getActiveTab();
        const hasFinItem = tabHasFinancieraConcept(tab);

        if (!hasFinItem && tab.externalCustomerId) {
            tab.externalCustomerId = '';
            tab.customerName = '';
            tab.customerPhone = '';
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

        const tbody = document.getElementById('tabCartItemsBody');
        tbody.innerHTML = '';

        if (tab.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-basket-shopping fa-2x text-muted mb-2 d-block" style="opacity: 0.35;"></i>
                        <span class="font-weight-bold">No hay conceptos en este proceso</span>
                        <small class="d-block text-muted mt-1">Selecciona financieras, préstamos, tandas o productos del panel izquierdo.</small>
                    </td>
                </tr>
            `;
        } else {
            tab.items.forEach((item, idx) => {
                const badgeMap = {
                    'financiera_abono': '<span class="badge badge-primary">Financiera</span>',
                    'financiera_externa': '<span class="badge badge-warning text-dark">Fin. Externa</span>',
                    'prestamo_abono': '<span class="badge badge-success">Préstamo</span>',
                    'prestamo_apertura': '<span class="badge badge-dark">Apertura</span>',
                    'tanda_abono': '<span class="badge text-white" style="background:#8b5cf6;">Tanda</span>',
                    'producto_inventario': '<span class="badge badge-info">Stock</span>',
                    'producto_dinamico': '<span class="badge badge-warning">Libre</span>',
                    'gift': '<span class="badge badge-success"><i class="fa-solid fa-gift mr-1"></i>Regalo ($0)</span>',
                };
                const tr = document.createElement('tr');
                const qty = Math.max(1, parseInt(item.quantity) || 1);
                const isGift = (item.section === 'gift');
                const unitAmt = isGift ? 0 : (parseFloat(item.amount) || 0);
                const lineAmt = (item.section === 'prestamo_apertura' || isGift) ? 0 : (unitAmt * qty);
                const commAmt = parseFloat(item.commission) || 0;
                const isProduct = (item.section === 'producto_inventario' || item.section === 'producto_dinamico');
                const isFinanciera = (item.section === 'financiera_abono' || item.section === 'financiera_externa');
                const isEditable = isProduct || isFinanciera;

                const originalAmt = item.original_amount !== undefined ? parseFloat(item.original_amount) : unitAmt;
                const hasDiscount = isProduct && (originalAmt > unitAmt);
                const isFree = (isProduct && (unitAmt === 0)) || isGift;

                let amountCellHtml = '';
                if (isGift) {
                    amountCellHtml = `<span class="badge badge-success font-size-11 px-2 py-1">$0.00 (Regalo)</span>`;
                } else if (isEditable) {
                    amountCellHtml = `
                        <div class="input-group input-group-sm ml-auto" style="max-width: 105px;">
                            <div class="input-group-prepend"><span class="input-group-text p-1 font-size-11">$</span></div>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-right font-weight-bold cart-item-price-input" data-idx="${idx}" value="${unitAmt.toFixed(2)}" title="Precio / Monto editable (0.00 o mayor)">
                        </div>
                        ${isFree ? '<span class="badge badge-success font-size-10 mt-1 d-block text-right">Gratis ($0)</span>' : ''}
                        ${hasDiscount && !isFree ? `<small class="text-success font-size-11 d-block text-right font-weight-bold">Desc: -$${(originalAmt - unitAmt).toFixed(2)}</small>` : ''}
                    `;
                } else {
                    amountCellHtml = `<span class="font-weight-bold text-dark">$${lineAmt.toFixed(2)}</span>`;
                }

                let qtyCellHtml = '';
                if (isProduct || isGift) {
                    qtyCellHtml = `
                        <input type="number" min="1" class="form-control form-control-sm text-center cart-item-qty-input font-weight-bold p-1" data-idx="${idx}" value="${qty}" style="max-width: 48px; margin: 0 auto;">
                    `;
                } else {
                    qtyCellHtml = `${qty}`;
                }

                tr.innerHTML = `
                    <td>
                        <div class="mb-1">${badgeMap[item.section] || ''}</div>
                        <div class="font-weight-bold text-dark">${item.title}</div>
                        ${item.subtitle ? `<small class="text-muted d-block font-size-11">${item.subtitle}</small>` : ''}
                    </td>
                    <td class="text-center font-weight-bold align-middle">${qtyCellHtml}</td>
                    <td class="text-right align-middle">${amountCellHtml}</td>
                    <td class="text-right text-info font-weight-bold align-middle">${commAmt > 0 ? '$' + commAmt.toFixed(2) : '-'}</td>
                    <td class="text-right align-middle">
                        <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item" data-idx="${idx}" title="Quitar concepto">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        const totals = calculateTabTotals(tab);
        document.getElementById('tabSubtotalText').textContent = '$' + totals.subtotal.toFixed(2);
        document.getElementById('tabCommissionText').textContent = '$' + totals.commission.toFixed(2);
        document.getElementById('tabGrandTotalText').textContent = '$' + totals.total.toFixed(2);

        // Control de pago parcial / saldo pendiente (deshabilitado cuando hay abono a financiera)
        const partialCheck = document.getElementById('posPartialPayCheck');
        const partialDetails = document.getElementById('posPartialPayDetails');
        const paidTodayInput = document.getElementById('posPaidTodayInput');
        const dueText = document.getElementById('posDueBalanceText');
        const hasFinAbonoItem = tabHasFinancieraConcept(tab);

        if (hasFinAbonoItem && tab.isPartial) {
            tab.isPartial = false;
            tab.paidToday = totals.total;
            saveState();
        }
        partialCheck.disabled = hasFinAbonoItem;

        if (tab.isPartial && !hasFinAbonoItem) {
            partialCheck.checked = true;
            partialDetails.classList.remove('d-none');
            const paid = (tab.paidToday !== undefined && tab.paidToday !== '') ? parseFloat(tab.paidToday) : 0;
            paidTodayInput.value = (tab.paidToday !== undefined && tab.paidToday !== '') ? tab.paidToday : '';
            const due = Math.max(0, totals.total - paid);
            dueText.value = '$' + due.toFixed(2);
        } else {
            partialCheck.checked = false;
            partialDetails.classList.add('d-none');
            paidTodayInput.value = totals.total.toFixed(2);
            dueText.value = '$0.00';
        }

        // Actualizar método de pago activo en la cuadrícula
        document.querySelectorAll('#paymentMethodBtnGroup [data-method]').forEach(lbl => {
            if (lbl.dataset.method === tab.paymentMethod) {
                lbl.classList.add('active');
            } else {
                lbl.classList.remove('active');
            }
        });

        // Mostrar u ocultar cajas según el método de pago activo
        $('#posBoxEfectivo, #posBoxTarjeta, #posBoxTransferencia, #posBoxMixto').addClass('d-none');
        const targetTotal = tab.isPartial ? (parseFloat(tab.paidToday) || 0) : totals.total;

        if (tab.paymentMethod === 'efectivo') {
            $('#posBoxEfectivo').removeClass('d-none');
            const cashVal = (tab.cashAmount !== undefined && tab.cashAmount !== '') ? tab.cashAmount : targetTotal.toFixed(2);
            document.getElementById('posCashAmountInput').value = cashVal;
            document.getElementById('posCashReceivedInput').value = tab.cashReceived || '';
        } else if (tab.paymentMethod === 'tarjeta') {
            $('#posBoxTarjeta').removeClass('d-none');
            const cardVal = (tab.cardAmount !== undefined && tab.cardAmount !== '') ? tab.cardAmount : targetTotal.toFixed(2);
            document.getElementById('posCardAmountInput').value = cardVal;
            document.getElementById('posCardRefInput').value = tab.cardRef || '';
        } else if (tab.paymentMethod === 'transferencia') {
            $('#posBoxTransferencia').removeClass('d-none');
            const transVal = (tab.transferAmount !== undefined && tab.transferAmount !== '') ? tab.transferAmount : targetTotal.toFixed(2);
            document.getElementById('posTransferAmountInput').value = transVal;
            document.getElementById('posTransferRefInput').value = tab.transferRef || '';
        } else if (tab.paymentMethod === 'mixto') {
            $('#posBoxMixto').removeClass('d-none');
            document.getElementById('lblMixTargetAmount').textContent = '$' + targetTotal.toFixed(2);
            document.getElementById('mixCashInput').value = tab.mixCash !== undefined ? tab.mixCash : 0;
            document.getElementById('mixCardInput').value = tab.mixCard !== undefined ? tab.mixCard : 0;
            document.getElementById('mixTransferInput').value = tab.mixTransfer !== undefined ? tab.mixTransfer : 0;
            document.getElementById('posMixCashReceivedInput').value = tab.mixCashReceived || '';
            document.getElementById('mixCardRefInput').value = tab.cardRef || '';
            document.getElementById('mixTransferRefInput').value = tab.transferRef || '';
            renderMixFaltanteAlert(targetTotal);
        }

        updateChangeCalculation();
        if (typeof renderOpenLoansList === 'function') {
            renderOpenLoansList();
        }
    }

    function renderMixFaltanteAlert(targetTotal) {
        const tab = getActiveTab();
        const container = document.getElementById('posMixFaltanteAlertContainer');
        if (!container) return;

        const cash = parseFloat(tab.mixCash) || 0;
        const card = parseFloat(tab.mixCard) || 0;
        const transfer = parseFloat(tab.mixTransfer) || 0;
        const sum = Math.round((cash + card + transfer) * 100) / 100;
        const diff = Math.round((targetTotal - sum) * 100) / 100;

        if (diff > 0.005) {
            container.innerHTML = `
                <div class="alert alert-danger py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-circle-exclamation mr-1"></i>Cantidad Faltante:</span>
                    <span class="badge badge-danger font-size-14 px-2 py-1">$${diff.toFixed(2)}</span>
                </div>
                <small class="text-danger d-block font-size-11 font-weight-bold">
                    Asignado: $${sum.toFixed(2)} / Total: $${targetTotal.toFixed(2)}. Falta cubrir <strong>$${diff.toFixed(2)}</strong>.
                </small>
            `;
        } else if (Math.abs(diff) <= 0.005) {
            container.innerHTML = `
                <div class="alert alert-success py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-circle-check mr-1"></i>¡Monto Cubierto al 100%!</span>
                    <span class="badge badge-success font-size-13">$${sum.toFixed(2)}</span>
                </div>
            `;
        } else {
            const extra = Math.abs(diff);
            container.innerHTML = `
                <div class="alert alert-warning py-2 px-3 mb-1 font-size-12 font-weight-bold shadow-sm d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-triangle-exclamation mr-1"></i>Excedente sobre el Total:</span>
                    <span class="badge badge-warning font-size-13 text-dark">+$${extra.toFixed(2)}</span>
                </div>
                <small class="text-warning d-block font-size-11 font-weight-bold">
                    La suma asignada ($${sum.toFixed(2)}) supera el total por $${extra.toFixed(2)}.
                </small>
            `;
        }
    }

    function updateChangeCalculation() {
        const tab = getActiveTab();
        const totals = calculateTabTotals(tab);
        const targetTotal = tab.isPartial ? (parseFloat(tab.paidToday) || 0) : totals.total;

        if (tab.paymentMethod === 'efectivo') {
            const cashToPay = parseFloat(document.getElementById('posCashAmountInput').value) || targetTotal;
            const received = parseFloat(document.getElementById('posCashReceivedInput').value) || 0;
            const change = Math.max(0, received - cashToPay);
            document.getElementById('posChangeOutput').value = '$' + change.toFixed(2);
        } else if (tab.paymentMethod === 'mixto') {
            const cashPortion = parseFloat(tab.mixCash) || 0;
            const received = parseFloat(document.getElementById('posMixCashReceivedInput').value) || 0;
            const change = Math.max(0, received - cashPortion);
            document.getElementById('posMixChangeOutput').value = '$' + change.toFixed(2);
            renderMixFaltanteAlert(targetTotal);
        }
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
        tab.items.push(item);
        saveState();
        renderAll();
    }

    // Eventos del carrito
    document.getElementById('tabCartItemsBody').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-item');
        if (btn) {
            const idx = parseInt(btn.dataset.idx);
            const tab = getActiveTab();
            tab.items.splice(idx, 1);
            saveState();
            renderAll();
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
                tab.items[idx].amount = Math.round(val * 100) / 100;
                saveState();
                renderActiveCart();
            }
            return;
        }

        const qtyInput = e.target.closest('.cart-item-qty-input');
        if (qtyInput) {
            const idx = parseInt(qtyInput.dataset.idx);
            const qVal = parseInt(qtyInput.value);
            if (isNaN(qVal) || qVal < 1) {
                alert('La cantidad mínima es 1.');
                renderActiveCart();
                return;
            }
            const tab = getActiveTab();
            if (tab && tab.items[idx]) {
                if (tab.items[idx].section === 'gift' && tab.items[idx].max_stock !== undefined && qVal > parseInt(tab.items[idx].max_stock)) {
                    alert(`Solo hay ${tab.items[idx].max_stock} unidades disponibles de este regalo en sucursal.`);
                    renderActiveCart();
                    return;
                }
                tab.items[idx].quantity = qVal;
                saveState();
                renderActiveCart();
            }
            return;
        }
    });

    document.getElementById('btnClearCurrentTab').addEventListener('click', function () {
        const tab = getActiveTab();
        tab.items = [];
        tab.customerName = '';
        tab.customerPhone = '';
        tab.customerId = '';
        tab.externalCustomerId = '';
        saveState();
        renderAll();
    });

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

    document.querySelectorAll('#paymentMethodBtnGroup [data-method]').forEach(lbl => {
        lbl.addEventListener('click', function () {
            const tab = getActiveTab();
            tab.paymentMethod = this.dataset.method;
            if (tab.paymentMethod === 'mixto') {
                const totals = calculateTabTotals(tab);
                const base = tab.isPartial ? (parseFloat(tab.paidToday) || 0) : totals.total;
                tab.mixCash = base;
                tab.mixCard = 0;
                tab.mixTransfer = 0;
            }
            saveState();
            renderActiveCart();
        });
    });

    document.getElementById('posPartialPayCheck').addEventListener('change', function () {
        const tab = getActiveTab();
        if (this.checked && tabHasFinancieraConcept(tab)) {
            this.checked = false;
            tab.isPartial = false;
            alert('⚠️ Los abonos a financieras no permiten pago parcial ni saldo pendiente en nota. Ajusta directamente el monto del abono en el carrito si es menor.');
            saveState();
            renderActiveCart();
            return;
        }
        const totals = calculateTabTotals(tab);
        tab.isPartial = this.checked;
        if (tab.isPartial) {
            if (tab.paidToday === undefined || tab.paidToday === '') {
                tab.paidToday = 0;
            }
        }
        saveState();
        renderActiveCart();
    });

    document.getElementById('posPaidTodayInput').addEventListener('input', function () {
        const tab = getActiveTab();
        const totals = calculateTabTotals(tab);
        let val = parseFloat(this.value);
        if (isNaN(val) || val < 0) val = 0;
        tab.paidToday = this.value;
        const due = Math.max(0, totals.total - val);
        document.getElementById('posDueBalanceText').value = '$' + due.toFixed(2);
        if (tab.paymentMethod === 'efectivo') {
            tab.cashAmount = val;
            document.getElementById('posCashAmountInput').value = val;
        } else if (tab.paymentMethod === 'tarjeta') {
            tab.cardAmount = val;
            document.getElementById('posCardAmountInput').value = val;
        } else if (tab.paymentMethod === 'transferencia') {
            tab.transferAmount = val;
            document.getElementById('posTransferAmountInput').value = val;
        } else if (tab.paymentMethod === 'mixto') {
            tab.mixCash = val;
            tab.mixCard = 0;
            tab.mixTransfer = 0;
            document.getElementById('mixCashInput').value = val;
            document.getElementById('mixCardInput').value = 0;
            document.getElementById('mixTransferInput').value = 0;
            document.getElementById('lblMixTargetAmount').textContent = '$' + val.toFixed(2);
            renderMixFaltanteAlert(val);
        }
        saveState();
        updateChangeCalculation();
    });

    document.getElementById('posCashAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        const totals = calculateTabTotals(tab);
        const val = parseFloat(this.value);
        tab.cashAmount = this.value;
        if (!isNaN(val) && val < totals.total && val >= 0) {
            tab.isPartial = true;
            tab.paidToday = val;
            document.getElementById('posPartialPayCheck').checked = true;
            $('#posPartialPayDetails').removeClass('d-none');
            document.getElementById('posPaidTodayInput').value = val;
            const due = totals.total - val;
            document.getElementById('posDueBalanceText').value = '$' + due.toFixed(2);
        } else if (!isNaN(val) && val >= totals.total) {
            tab.isPartial = false;
            tab.paidToday = totals.total;
            document.getElementById('posPartialPayCheck').checked = false;
            $('#posPartialPayDetails').addClass('d-none');
            document.getElementById('posDueBalanceText').value = '$0.00';
        }
        saveState();
        updateChangeCalculation();
    });

    document.getElementById('posCardAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        const totals = calculateTabTotals(tab);
        const val = parseFloat(this.value);
        tab.cardAmount = this.value;
        if (!isNaN(val) && val < totals.total && val >= 0) {
            tab.isPartial = true;
            tab.paidToday = val;
            document.getElementById('posPartialPayCheck').checked = true;
            $('#posPartialPayDetails').removeClass('d-none');
            document.getElementById('posPaidTodayInput').value = val;
            const due = totals.total - val;
            document.getElementById('posDueBalanceText').value = '$' + due.toFixed(2);
        } else if (!isNaN(val) && val >= totals.total) {
            tab.isPartial = false;
            tab.paidToday = totals.total;
            document.getElementById('posPartialPayCheck').checked = false;
            $('#posPartialPayDetails').addClass('d-none');
            document.getElementById('posDueBalanceText').value = '$0.00';
        }
        saveState();
    });

    document.getElementById('posTransferAmountInput').addEventListener('input', function () {
        const tab = getActiveTab();
        const totals = calculateTabTotals(tab);
        const val = parseFloat(this.value);
        tab.transferAmount = this.value;
        if (!isNaN(val) && val < totals.total && val >= 0) {
            tab.isPartial = true;
            tab.paidToday = val;
            document.getElementById('posPartialPayCheck').checked = true;
            $('#posPartialPayDetails').removeClass('d-none');
            document.getElementById('posPaidTodayInput').value = val;
            const due = totals.total - val;
            document.getElementById('posDueBalanceText').value = '$' + due.toFixed(2);
        } else if (!isNaN(val) && val >= totals.total) {
            tab.isPartial = false;
            tab.paidToday = totals.total;
            document.getElementById('posPartialPayCheck').checked = false;
            $('#posPartialPayDetails').addClass('d-none');
            document.getElementById('posDueBalanceText').value = '$0.00';
        }
        saveState();
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
            saveState();
            updateChangeCalculation();
        });
    });

    document.getElementById('posMixCashReceivedInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.mixCashReceived = this.value;
        saveState();
        updateChangeCalculation();
    });

    document.getElementById('posCashReceivedInput').addEventListener('input', function () {
        const tab = getActiveTab();
        tab.cashReceived = this.value;
        saveState();
        updateChangeCalculation();
        if (typeof renderOpenLoansList === 'function') {
            renderOpenLoansList();
        }
    });

    // ==================== 1. BÚSQUEDA Y ABONO DE FINANCIERAS ====================
    function searchFinancieras() {
        const q = document.getElementById('finSearchInput').value.trim();
        const finId = document.getElementById('finCatalogSelect').value;
        const container = document.getElementById('finSearchResults');
        container.innerHTML = '<div class="text-muted font-size-12"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Buscando contratos por Device ID / TAG...</div>';

        fetch(`{{ route('central-pos.lookup-financiera') }}?q=${encodeURIComponent(q)}&financiera_id=${encodeURIComponent(finId)}`)
            .then(r => r.json())
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

            fetch("{{ route('central-pos.external-customers.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    const errors = data.errors ? Object.values(data.errors).flat() : [];
                    throw new Error(errors.length ? errors.join('<br>') : (data.message || 'No se pudo guardar el cliente externo.'));
                }
                return data;
            })
            .then(data => {
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
                    errBox.innerHTML = err.message || 'Error al registrar cliente externo.';
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

        fetch(`{{ route('central-pos.lookup-loans') }}?customer_id=${encodeURIComponent(customerId)}`)
            .then(r => r.json())
            .then(data => {
                currentCustomerLoansData = data;
                renderOpenLoansList();
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
                title: btn.dataset.name,
                subtitle: 'Impacto real en Inventario',
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
            const tab = getActiveTab();
            const existingQty = tab.items
                .filter(i => i.section === 'gift' && String(i.gift_id) === String(giftId))
                .reduce((acc, i) => acc + Math.max(1, parseInt(i.quantity) || 1), 0);

            if (maxStock <= 0 || existingQty >= maxStock) {
                alert(`Stock insuficiente para entregar más unidades del regalo "${btn.dataset.name}" (Disponible en sucursal: ${maxStock}).`);
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
        const url = `{{ route('central-pos.lookup-gifts') }}?branch_id={{ $branchId }}&q=${encodeURIComponent(query || '')}`;
        fetch(url)
            .then(r => r.json())
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

    const btnCompleteSaleEl = document.getElementById('btnCompleteUnifiedSale');
    if (btnCompleteSaleEl) {
        btnCompleteSaleEl.addEventListener('click', function () {
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

            const totals = calculateTabTotals(tab);
            if (tab.items.length === 0) {
                alert('Esta pestaña no tiene conceptos agregados. Agrega productos, préstamos, financieras o tandas antes de cerrar el cobro.');
                return;
            }

            const effectivePaidAmount = tab.isPartial ? (parseFloat(tab.paidToday) || 0) : totals.total;
            const effectiveDueAmount = Math.max(0, totals.total - effectivePaidAmount);
            const hasFinAbono = tabHasFinancieraConcept(tab);

            if (hasFinAbono && effectiveDueAmount > 0.01) {
                alert('⚠️ Los abonos a financieras no permiten pago parcial ni dejar saldo pendiente. Cubre el total completo o edita directamente el monto del abono.');
                return;
            }

            if (effectiveDueAmount > 0) {
                const cName = (tab.customerName || '').trim();
                if (!cName || cName.toLowerCase() === 'cliente mostrador') {
                    alert('⚠️ Para registrar una Nota con Saldo Pendiente (el cliente queda debiendo $' + effectiveDueAmount.toFixed(2) + '), es obligatorio escribir o seleccionar el Nombre del Cliente.');
                    document.getElementById('tabCustomerName').focus();
                    return;
                }
            }

            let cashAmt = 0;
            let cardAmt = 0;
            let transAmt = 0;
            let refs = {};

            tab.paymentMethod = tab.paymentMethod || 'efectivo';

            if (tab.paymentMethod === 'efectivo') {
                cashAmt = effectivePaidAmount;
            } else if (tab.paymentMethod === 'tarjeta') {
                cardAmt = effectivePaidAmount;
                refs.card_ref = tab.cardRef || '';
            } else if (tab.paymentMethod === 'transferencia') {
                transAmt = effectivePaidAmount;
                refs.transfer_ref = tab.transferRef || '';
            } else if (tab.paymentMethod === 'mixto') {
                cashAmt = parseFloat(tab.mixCash) || 0;
                cardAmt = parseFloat(tab.mixCard) || 0;
                transAmt = parseFloat(tab.mixTransfer) || 0;
                refs.card_ref = tab.cardRef || '';
                refs.transfer_ref = tab.transferRef || '';

                const sumMix = Math.round((cashAmt + cardAmt + transAmt) * 100) / 100;
                const diffMix = Math.round((effectivePaidAmount - sumMix) * 100) / 100;

                if (diffMix > 0.05) {
                    alert(`⚠️ En Pago Mixto, falta cubrir $${diffMix.toFixed(2)}.\n\nTotal a cobrar: $${effectivePaidAmount.toFixed(2)}\nSuma asignada: $${sumMix.toFixed(2)}\nCantidad Faltante: $${diffMix.toFixed(2)}`);
                    return;
                } else if (diffMix < -0.05) {
                    alert(`⚠️ En Pago Mixto, la suma asignada ($${sumMix.toFixed(2)}) excede el total a cobrar ($${effectivePaidAmount.toFixed(2)}) por $${Math.abs(diffMix).toFixed(2)}.`);
                    return;
                }
            }

            const shouldAutoOpenDrawer = !!document.getElementById('posOpenDrawerCheck')?.checked && (
                (tab.paymentMethod === 'efectivo' && cashAmt > 0) ||
                (tab.paymentMethod === 'mixto' && cashAmt > 0)
            );
            const shouldAutoPrint = !!document.getElementById('posAutoPrintCheck')?.checked;

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Procesando Cobro...';

            fetch("{{ route('central-pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    branch_id: '{{ $branchId }}',
                    customer_id: tab.externalCustomerId ? null : (tab.customerId || null),
                    external_customer_id: (tabHasFinancieraConcept(tab) && tab.externalCustomerId) ? tab.externalCustomerId : null,
                    customer_name: tab.customerName || 'Cliente Mostrador',
                    customer_phone: tab.customerPhone || null,
                    payment_method: tab.paymentMethod,
                    cash_amount: cashAmt,
                    card_amount: cardAmt,
                    transfer_amount: transAmt,
                    payment_references: refs,
                    cash_received: parseFloat(tab.paymentMethod === 'mixto' ? (tab.mixCashReceived || tab.cashReceived) : tab.cashReceived) || 0,
                    paid_amount: tab.isPartial ? effectivePaidAmount : null,
                    open_drawer: document.getElementById('posOpenDrawerCheck')?.checked ? 1 : 0,
                    items: tab.items
                })
            })
            .then(async (r) => {
                const data = await r.json().catch(() => null);
                if (!r.ok || !data) {
                    throw new Error(data?.message || 'Error en el servidor al procesar el cobro (' + r.status + ')');
                }
                return data;
            })
            .then(res => {
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
                tab.cashReceived = '';
                tab.isPartial = false;
                tab.paidToday = '';
                tab.cashAmount = '';
                tab.cardAmount = '';
                tab.transferAmount = '';
                tab.cardRef = '';
                tab.transferRef = '';
                tab.mixCash = 0;
                tab.mixCard = 0;
                tab.mixTransfer = 0;
                tab.mixCashReceived = '';
                saveState();
                renderAll();

                // Apertura AUTOMÁTICA del cajón por USB únicamente cuando el cobro involucra efectivo
                if (shouldAutoOpenDrawer && window.ZyaPosHardware) {
                    ZyaPosHardware.autoOpenUsbDrawer();
                }

                if (res.receipt_url) {
                    if (shouldAutoPrint) {
                        const sep = res.receipt_url.includes('?') ? '&' : '?';
                        const paperMode = localStorage.getItem('zya_ticket_paper_mode') || (localStorage.getItem('zya_ticket_width') === '58' ? '58' : '80');
                        printTicketDirectly(res.receipt_url + sep + 'skip_drawer=1&paper=' + paperMode);
                    }
                    showSaleSuccessBanner(res.transaction_code, res.receipt_url);
                    if (window.openTicketPreviewModal) {
                        window.openTicketPreviewModal(res.receipt_url, 'Ticket ' + res.transaction_code);
                    }
                }
            })
            .catch(err => {
                console.error('Error al procesar el cobro:', err);
                alert(err.message || 'Error de red o comunicación al procesar el movimiento.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double mr-2"></i>Cerrar Cobro y Generar Ticket';
            });
        });
    }

    function printTicketDirectly(url) {
        if (typeof window.printTicketDirectly === 'function') {
            window.printTicketDirectly(url);
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
                    const paperMode = localStorage.getItem('zya_ticket_paper_mode') || (localStorage.getItem('zya_ticket_width') === '58' ? '58' : '80');
                    const sep = receiptUrl.includes('?') ? '&' : '?';
                    printTicketDirectly(receiptUrl + sep + 'skip_drawer=1&paper=' + paperMode);
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
        const url = `{{ route('central-pos.recent-transactions') }}?branch_id={{ $branchId }}&date=${encodeURIComponent(dateVal)}&search=${encodeURIComponent(searchVal)}`;

        fetch(url)
            .then(r => r.json())
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
                    tr.querySelector('.btn-hist-preview').addEventListener('click', () => {
                        if (window.openTicketPreviewModal) {
                            window.openTicketPreviewModal(tx.receipt_url, 'Ticket ' + tx.transaction_code);
                        }
                    });
                    tr.querySelector('.btn-hist-print').addEventListener('click', () => {
                        const paperMode = localStorage.getItem('zya_ticket_paper_mode') || (localStorage.getItem('zya_ticket_width') === '58' ? '58' : '80');
                        const sep = tx.receipt_url.includes('?') ? '&' : '?';
                        printTicketDirectly(tx.receipt_url + sep + 'skip_drawer=1&paper=' + paperMode);
                    });
                    tbody.appendChild(tr);
                });
            })
            .catch(() => {});
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
