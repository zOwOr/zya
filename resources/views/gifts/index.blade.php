@extends('dashboard.body.main')

@php
    $activeTab = request()->has('transfer_page')
        ? 'transfers'
        : ((request()->has('mov_page') || filled($movementTypeFilter)) ? 'movements' : 'catalog');

    $giftInventoryCatalogMap = $masterCatalog->mapWithKeys(function ($g) {
        $branchStocks = $g->stocks->mapWithKeys(fn ($s) => [(string) $s->branch_id => (int) $s->stock_quantity])->all();
        return [
            (string) $g->id => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'brand' => $g->brand,
                'supplier' => $g->supplier_display_name,
                'central_stock' => (int) $g->stock_quantity,
                'branch_stocks' => $branchStocks,
            ],
        ];
    })->all();
@endphp

@section('specificpagestyles')
<style>
    .gift-kpi-card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .gift-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,.06);
    }
    .branch-stock-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        margin: 2px;
    }
    .branch-stock-pill.is-highlighted {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }
    .gift-tab-nav .nav-link {
        font-weight: 600;
        border-radius: 8px 8px 0 0;
    }
    .gift-tab-nav .nav-link.active {
        background-color: #fff;
        border-bottom-color: #fff;
        color: #1d4ed8;
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    {{-- Encabezado principal --}}
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="mb-1 font-weight-bold text-primary">
                            <i class="fa-solid fa-gift mr-2"></i>Catálogo Maestro de Gifts y Traspasos entre Sucursales
                        </h4>
                        <p class="text-muted mb-0 small">
                            Cada regalo se registra una única vez en el <strong>Catálogo Maestro (Inventario General)</strong> y se distribuye por traspasos hacia las sucursales sin duplicar nombres.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if(auth()->user()->can('gifts.create'))
                            <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm mr-1 mb-1" data-toggle="modal" data-target="#modalCreateGift">
                                <i class="fa-solid fa-plus mr-1"></i> Nuevo Gift (Catálogo Maestro)
                            </button>
                            <button type="button" class="btn btn-info btn-sm font-weight-bold shadow-sm mr-1 mb-1" data-toggle="modal" data-target="#modalEntryGift">
                                <i class="fa-solid fa-warehouse mr-1"></i> Entrada a Inventario General
                            </button>
                        @endif
                        @if(auth()->user()->can('gifts.transfer'))
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold shadow-sm mr-1 mb-1" data-toggle="modal" data-target="#modalTransferGift">
                                <i class="fa-solid fa-right-left mr-1"></i> Traspasar a Sucursal
                            </button>
                        @endif
                        @if(auth()->user()->can('gifts.deliver'))
                            <button type="button" class="btn btn-success btn-sm font-weight-bold shadow-sm mb-1" data-toggle="modal" data-target="#modalDeliverGift">
                                <i class="fa-solid fa-hand-holding-heart mr-1"></i> Entregar a Cliente
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas de sesión y validación --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <strong><i class="fa-solid fa-circle-exclamation mr-1"></i>Revisa los datos enviados:</strong>
            <ul class="mb-0 mt-1 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- Tarjetas KPI --}}
    <div class="row mb-3">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">Catálogo Maestro Activo</span>
                    <h4 class="mb-0 font-weight-bold text-primary">{{ number_format($stats['total_catalog']) }}</h4>
                    <small class="text-muted">artículos únicos</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">Inventario General</span>
                    <h4 class="mb-0 font-weight-bold text-info">{{ number_format($stats['central_stock']) }}</h4>
                    <small class="text-muted">pzas en almacén matriz</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">
                        {{ $selectedBranchObj ? 'Stock ' . $selectedBranchObj->name : 'Stock en Sucursales' }}
                    </span>
                    <h4 class="mb-0 font-weight-bold text-success">{{ number_format($stats['branch_stock']) }}</h4>
                    <small class="text-muted">pzas disponibles tienda</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">Entradas del Mes</span>
                    <h4 class="mb-0 font-weight-bold text-dark">+{{ number_format($stats['entries_month']) }}</h4>
                    <small class="text-muted">pzas recibidas matriz</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">Traspasos del Mes</span>
                    <h4 class="mb-0 font-weight-bold text-warning">{{ number_format($stats['transfers_month']) }}</h4>
                    <small class="text-muted">pzas enviadas</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card gift-kpi-card h-100">
                <div class="card-body py-3">
                    <span class="text-muted small d-block">Entregas del Mes</span>
                    <h4 class="mb-0 font-weight-bold text-danger">-{{ number_format($stats['deliveries_month']) }}</h4>
                    <small class="text-muted">pzas a clientes</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('gifts.index') }}" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Buscar regalo o referencia</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Nombre, código, marca o folio...">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Enfoque de almacén / sucursal</label>
                    @if($canAllBranches)
                        <select name="branch_id" class="form-control form-control-sm">
                            <option value="all" {{ $branchScope === 'all' ? 'selected' : '' }}>Todos (Inventario General + Todas las Sucursales)</option>
                            <option value="general" {{ $branchScope === 'general' ? 'selected' : '' }}>Solo Inventario General (Almacén Central)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) $branchScope === (string) $b->id ? 'selected' : '' }}>
                                    Sucursal: {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <div class="form-control form-control-sm bg-light">
                            <i class="fa-solid fa-store mr-1 text-primary"></i>
                            {{ $selectedBranchObj->name ?? 'Sucursal asignada' }}
                        </div>
                    @endif
                </div>

                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Proveedor</label>
                    <select name="supplier_filter" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach($finSuppliers as $fs)
                            <option value="fin:{{ $fs->id }}" {{ $supplierFilter === 'fin:' . $fs->id ? 'selected' : '' }}>
                                [Fin] {{ $fs->name }}
                            </option>
                        @endforeach
                        @foreach($suppliers as $s)
                            <option value="sup:{{ $s->id }}" {{ $supplierFilter === 'sup:' . $s->id ? 'selected' : '' }}>
                                [Prov] {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="small font-weight-bold mb-1">Estado</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="activo" {{ $statusFilter === 'activo' ? 'selected' : '' }}>Activos</option>
                        <option value="inactivo" {{ $statusFilter === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <div class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input" id="onlyWithStockCheck" name="only_with_stock" value="1" {{ $onlyWithStock ? 'checked' : '' }}>
                        <label class="custom-control-label small" for="onlyWithStockCheck">Solo con existencia</label>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm mr-1">
                            <i class="fa-solid fa-filter mr-1"></i>Filtrar
                        </button>
                        <a href="{{ route('gifts.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Pestañas de Catálogo Maestro, Historial de Traspasos y Kardex --}}
    <ul class="nav nav-tabs gift-tab-nav" id="giftModuleTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'catalog' ? 'active' : '' }}" id="tab-catalog-link" data-toggle="tab" href="#tab-catalog" role="tab">
                <i class="fa-solid fa-boxes-stacked mr-1"></i>Catálogo Maestro y Existencias
                <span class="badge badge-primary ml-1">{{ $gifts->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'transfers' ? 'active' : '' }}" id="tab-transfers-link" data-toggle="tab" href="#tab-transfers" role="tab">
                <i class="fa-solid fa-right-left mr-1"></i>Historial de Traspasos
                <span class="badge badge-warning ml-1">{{ $transfers->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'movements' ? 'active' : '' }}" id="tab-movements-link" data-toggle="tab" href="#tab-movements" role="tab">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i>Kardex de Movimientos
                <span class="badge badge-secondary ml-1">{{ $movements->total() }}</span>
            </a>
        </li>
    </ul>

    <div class="card shadow-sm border-0 border-top-0" style="border-radius: 0 0 10px 10px;">
        <div class="tab-content" id="giftModuleTabsContent">
            {{-- TAB 1: Catálogo Maestro Único --}}
            <div class="tab-pane fade {{ $activeTab === 'catalog' ? 'show active' : '' }}" id="tab-catalog" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código</th>
                                <th>Regalo / Promocional (Maestro)</th>
                                <th>Proveedor</th>
                                <th class="text-center">Inventario General</th>
                                <th>Existencia por Sucursales</th>
                                <th class="text-center">Total Global</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gifts as $gift)
                                @php
                                    $centralQty = (int) $gift->stock_quantity;
                                    $branchStocks = $gift->stocks->filter(fn ($s) => (int) $s->stock_quantity > 0)->sortBy(fn ($s) => $s->branch?->name ?? '');
                                    $activeBranchQty = $activeBranchId ? $gift->stockInBranch($activeBranchId) : null;
                                @endphp
                                <tr class="{{ $gift->status !== 'activo' ? 'table-secondary text-muted' : '' }}">
                                    <td>
                                        <span class="badge badge-light border font-weight-bold">{{ $gift->code }}</span>
                                        @if($gift->status !== 'activo')
                                            <span class="badge badge-secondary d-block mt-1">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $gift->name }}</div>
                                        <small class="text-muted">
                                            {{ $gift->brand ? 'Marca: ' . $gift->brand . ' • ' : '' }}
                                            Costo ref: ${{ number_format((float) $gift->unit_cost, 2) }} • Mín: {{ $gift->min_stock }}
                                        </small>
                                        @if($gift->description)
                                            <div class="small text-muted text-truncate" style="max-width: 280px;" title="{{ $gift->description }}">
                                                {{ $gift->description }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info border px-2 py-1">{{ $gift->supplier_display_name }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $centralBadge = $centralQty <= 0 ? 'badge-secondary' : ($centralQty <= $gift->min_stock ? 'badge-warning' : 'badge-info');
                                        @endphp
                                        <span class="badge badge-pill {{ $centralBadge }} px-3 py-1" style="font-size: .88rem;">
                                            {{ $centralQty }} pzas
                                        </span>
                                    </td>
                                    <td>
                                        @if($activeBranchId !== null)
                                            <span class="branch-stock-pill is-highlighted">
                                                <i class="fa-solid fa-store"></i>
                                                {{ $selectedBranchObj->name ?? 'Sucursal' }}: <strong>{{ $activeBranchQty }}</strong>
                                            </span>
                                            @if($canAllBranches && $branchStocks->count() > 1)
                                                <div class="mt-1">
                                                    @foreach($branchStocks as $st)
                                                        @if((int) $st->branch_id !== (int) $activeBranchId)
                                                            <span class="branch-stock-pill">
                                                                {{ $st->branch?->name ?? ('#' . $st->branch_id) }}: <strong>{{ $st->stock_quantity }}</strong>
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            @forelse($branchStocks as $st)
                                                <span class="branch-stock-pill">
                                                    <i class="fa-solid fa-store text-muted"></i>
                                                    {{ $st->branch?->name ?? ('Sucursal #' . $st->branch_id) }}: <strong>{{ $st->stock_quantity }}</strong>
                                                </span>
                                            @empty
                                                <span class="text-muted small">Sin stock asignado en sucursales</span>
                                            @endforelse
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="font-weight-bold text-dark">{{ $gift->total_global_stock }}</span>
                                        <small class="d-block text-muted">pzas</small>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @if(auth()->user()->can('gifts.create') && $gift->status === 'activo')
                                            <button type="button" class="btn btn-xs btn-outline-info btn-open-entry"
                                                data-gift-id="{{ $gift->id }}" title="Entrada a Inventario General">
                                                <i class="fa-solid fa-plus"></i> Entrada
                                            </button>
                                        @endif
                                        @if(auth()->user()->can('gifts.transfer') && $gift->status === 'activo')
                                            <button type="button" class="btn btn-xs btn-outline-warning btn-open-transfer"
                                                data-gift-id="{{ $gift->id }}"
                                                data-from-branch="{{ $onlyGeneral ? '' : ($activeBranchId && !$canAllBranches ? '' : '') }}"
                                                title="Traspasar a Sucursal">
                                                <i class="fa-solid fa-right-left"></i> Traspasar
                                            </button>
                                        @endif
                                        @if(auth()->user()->can('gifts.deliver') && $gift->status === 'activo')
                                            <button type="button" class="btn btn-xs btn-outline-success btn-open-deliver"
                                                data-gift-id="{{ $gift->id }}"
                                                data-branch-id="{{ $activeBranchId ?: ($userBranchId ?: '') }}"
                                                title="Entregar a Cliente">
                                                <i class="fa-solid fa-gift"></i> Entregar
                                            </button>
                                        @endif
                                        @if(auth()->user()->can('gifts.edit'))
                                            <button type="button" class="btn btn-xs btn-outline-secondary btn-open-edit"
                                                data-id="{{ $gift->id }}"
                                                data-code="{{ $gift->code }}"
                                                data-name="{{ $gift->name }}"
                                                data-brand="{{ $gift->brand }}"
                                                data-description="{{ $gift->description }}"
                                                data-supplier-id="{{ $gift->supplier_id }}"
                                                data-fin-supplier-id="{{ $gift->fin_supplier_id }}"
                                                data-supplier-name="{{ $gift->supplier_name }}"
                                                data-min-stock="{{ $gift->min_stock }}"
                                                data-unit-cost="{{ $gift->unit_cost }}"
                                                data-status="{{ $gift->status }}"
                                                title="Editar ficha maestra">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        @endif
                                        @if(auth()->user()->can('gifts.delete'))
                                            <form action="{{ route('gifts.destroy', $gift->id) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('¿Eliminar este regalo del Catálogo Maestro? Solo es posible si no tiene stock ni movimientos.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-gift fa-2x mb-2 d-block"></i>
                                        No se encontraron regalos en el Catálogo Maestro con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($gifts->hasPages())
                    <div class="card-footer bg-white d-flex justify-content-end">
                        {{ $gifts->links() }}
                    </div>
                @endif
            </div>

            {{-- TAB 2: Historial de Traspasos --}}
            <div class="tab-pane fade {{ $activeTab === 'transfers' ? 'show active' : '' }}" id="tab-transfers" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Folio / Fecha</th>
                                <th>Gift (Catálogo Maestro)</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th class="text-center">Cantidad</th>
                                <th>Usuario y Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $tr)
                                <tr>
                                    <td>
                                        <span class="badge badge-light border font-weight-bold">{{ $tr->transfer_code }}</span>
                                        <small class="d-block text-muted">{{ $tr->transferred_at?->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $tr->gift?->name ?? 'Regalo eliminado' }}</strong>
                                        <small class="d-block text-muted">ID #{{ $tr->gift_id }} • {{ $tr->gift?->code }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $tr->origin_label }}</span>
                                        <small class="d-block text-muted">Stock: {{ $tr->from_previous_stock }} &rarr; {{ $tr->from_new_stock }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">{{ $tr->destination_label }}</span>
                                        <small class="d-block text-muted">Stock: {{ $tr->to_previous_stock }} &rarr; {{ $tr->to_new_stock }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-pill badge-warning px-3 py-1 font-weight-bold">
                                            {{ $tr->quantity }} pzas
                                        </span>
                                    </td>
                                    <td>
                                        <small class="d-block font-weight-bold">{{ $tr->user?->name ?? 'Sistema' }}</small>
                                        <small class="text-muted">{{ $tr->notes }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Aún no se han registrado traspasos de gifts hacia sucursales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transfers->hasPages())
                    <div class="card-footer bg-white d-flex justify-content-end">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>

            {{-- TAB 3: Kardex de Movimientos --}}
            <div class="tab-pane fade {{ $activeTab === 'movements' ? 'show active' : '' }}" id="tab-movements" role="tabpanel">
                <div class="p-3 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small font-weight-bold text-muted">
                        Bitácora auditable de entradas a matriz, traspasos entre almacenes y entregas a clientes.
                    </span>
                    <form method="GET" action="{{ route('gifts.index') }}" class="form-inline">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="branch_id" value="{{ $branchScope }}">
                        <input type="hidden" name="mov_page" value="1">
                        <select name="movement_type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">Todos los movimientos</option>
                            <option value="entrada_proveedor" {{ $movementTypeFilter === 'entrada_proveedor' ? 'selected' : '' }}>Entradas a Inventario General</option>
                            <option value="traspaso_salida" {{ $movementTypeFilter === 'traspaso_salida' ? 'selected' : '' }}>Salida por Traspaso</option>
                            <option value="traspaso_entrada" {{ $movementTypeFilter === 'traspaso_entrada' ? 'selected' : '' }}>Entrada por Traspaso</option>
                            <option value="entrega_cliente" {{ $movementTypeFilter === 'entrega_cliente' ? 'selected' : '' }}>Entrega a Cliente</option>
                            <option value="ajuste" {{ $movementTypeFilter === 'ajuste' ? 'selected' : '' }}>Ajustes</option>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Movimiento</th>
                                <th>Almacén / Sucursal</th>
                                <th>Regalo</th>
                                <th class="text-center">Cantidad / Stock</th>
                                <th>Cliente / Referencia / Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $mv)
                                @php
                                    $typeMeta = match ($mv->movement_type) {
                                        'entrada_proveedor' => ['badge' => 'badge-info', 'label' => 'Entrada Matriz', 'sign' => '+'],
                                        'traspaso_salida' => ['badge' => 'badge-warning', 'label' => 'Traspaso (Salida)', 'sign' => '-'],
                                        'traspaso_entrada' => ['badge' => 'badge-primary', 'label' => 'Traspaso (Entrada)', 'sign' => '+'],
                                        'entrega_cliente' => ['badge' => 'badge-success', 'label' => 'Entrega Cliente', 'sign' => '-'],
                                        default => ['badge' => 'badge-secondary', 'label' => ucfirst((string) $mv->movement_type), 'sign' => ''],
                                    };
                                @endphp
                                <tr>
                                    <td class="text-nowrap"><small>{{ $mv->movement_date?->format('d/m/Y H:i') }}</small></td>
                                    <td><span class="badge {{ $typeMeta['badge'] }}">{{ $typeMeta['label'] }}</span></td>
                                    <td>
                                        @if($mv->branch)
                                            <span class="badge badge-light border">{{ $mv->branch->name }}</span>
                                        @else
                                            <span class="badge badge-soft-info border">Inventario General (Matriz)</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $mv->gift?->name ?? 'Gift #' . $mv->gift_id }}</strong>
                                        <small class="d-block text-muted">{{ $mv->gift?->code }}</small>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <strong>{{ $typeMeta['sign'] }}{{ $mv->quantity }}</strong>
                                        <small class="d-block text-muted">{{ $mv->previous_stock }} &rarr; {{ $mv->new_stock }}</small>
                                    </td>
                                    <td>
                                        @if($mv->customer_name)
                                            <div class="small font-weight-bold text-dark">
                                                Cliente: {{ $mv->customer_name }} {{ $mv->customer_phone ? '(' . $mv->customer_phone . ')' : '' }}
                                            </div>
                                        @endif
                                        @if($mv->reference_code)
                                            <small class="badge badge-light border">Ref: {{ $mv->reference_code }}</small>
                                        @endif
                                        <small class="text-muted d-block">{{ $mv->user?->name ?? 'Sistema' }} — {{ $mv->notes }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Sin movimientos registrados con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($movements->hasPages())
                    <div class="card-footer bg-white d-flex justify-content-end">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: Alta Única en Catálogo Maestro (Inventario General) --}}
<div class="modal fade" id="modalCreateGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('gifts.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa-solid fa-plus-circle mr-2"></i>Nuevo Gift — Catálogo Maestro Único
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    El regalo se dará de alta una sola vez en el <strong>Inventario General (Almacén Central)</strong>. No se permiten nombres duplicados entre sucursales.
                </div>
                <div class="row">
                    <div class="col-md-8 form-group">
                        <label class="font-weight-bold small">Nombre del Gift / Promocional *</label>
                        <input type="text" name="name" class="form-control" required maxlength="160" placeholder="Ej. Gorra PayJoy">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Código Único (opcional)</label>
                        <input type="text" name="code" class="form-control" maxlength="50" placeholder="Auto: GFT-XXXXXX">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Marca</label>
                        <input type="text" name="brand" class="form-control" maxlength="100" placeholder="Ej. PayJoy, KrediYA...">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Proveedor Financiera</label>
                        <select name="fin_supplier_id" class="form-control select2-autocomplete" data-placeholder="-- Opcional --">
                            <option value="">-- Ninguno --</option>
                            @foreach($finSuppliers as $fs)
                                <option value="{{ $fs->id }}">{{ $fs->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Proveedor General</label>
                        <select name="supplier_id" class="form-control select2-autocomplete" data-placeholder="-- Opcional --">
                            <option value="">-- Ninguno --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Nombre libre proveedor</label>
                        <input type="text" name="supplier_name" class="form-control" maxlength="120" placeholder="Si no está en lista">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold small">Stock Inicial (Matriz)</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="font-weight-bold small">Stock Mín.</label>
                        <input type="number" name="min_stock" class="form-control" min="0" value="3">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold small">Costo Ref. ($)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" value="0">
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="font-weight-bold small">Descripción / Especificaciones</label>
                        <input type="text" name="description" class="form-control" maxlength="500" placeholder="Color, material, promoción aplicable...">
                    </div>
                    <div class="col-md-12 form-group mb-0">
                        <label class="font-weight-bold small">Notas de alta inicial</label>
                        <input type="text" name="notes" class="form-control" maxlength="500" placeholder="Ej. Remisión #123 entregada en almacén matriz">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                    <i class="fa-solid fa-save mr-1"></i>Guardar en Catálogo Maestro
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Entrada / Recepción de Piezas al Inventario General --}}
<div class="modal fade" id="modalEntryGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('gifts.entry') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa-solid fa-warehouse mr-2"></i>Recepción a Inventario General (Matriz)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold small">Regalo del Catálogo Maestro *</label>
                    <select name="gift_id" id="entryGiftSelect" class="form-control select2-autocomplete" required data-placeholder="-- Buscar regalo --">
                        <option value="">-- Selecciona un regalo --</option>
                        @foreach($masterCatalog as $mg)
                            <option value="{{ $mg->id }}">
                                [{{ $mg->code }}] {{ $mg->name }} (Matriz actual: {{ (int) $mg->stock_quantity }} pzas)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="alert alert-light border py-2 small d-flex justify-content-between align-items-center">
                    <span>Stock actual en Inventario General:</span>
                    <strong id="entryCurrentCentralStock" class="badge badge-info px-2 py-1">—</strong>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Cantidad recibida *</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Actualizar costo ref. (opc.)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" placeholder="Dejar igual">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Folio / Factura / Remisión proveedor</label>
                    <input type="text" name="reference_code" class="form-control" maxlength="60" placeholder="Ej. REM-2026-014">
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold small">Notas</label>
                    <textarea name="notes" rows="2" class="form-control" maxlength="500" placeholder="Observaciones de recepción en almacén central..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-info btn-sm font-weight-bold">
                    <i class="fa-solid fa-circle-plus mr-1"></i>Sumar a Inventario General
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 3: Traspaso de Inventario General (o Sucursal) hacia Sucursal Destino --}}
<div class="modal fade" id="modalTransferGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('gifts.transfer') }}" method="POST" id="formTransferGift" class="modal-content">
            @csrf
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa-solid fa-right-left mr-2"></i>Traspasar Regalo a Sucursal
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border py-2 small mb-3">
                    <i class="fa-solid fa-lock mr-1 text-warning"></i>
                    Mantiene el mismo <strong>ID y nombre maestro</strong> en todas las sucursales y valida estrictamente no exceder la existencia del almacén origen.
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Buscar y seleccionar Gift del Catálogo Maestro *</label>
                    <select name="gift_id" id="transferGiftSelect" class="form-control select2-autocomplete" required data-placeholder="-- Escribe nombre o código del regalo --">
                        <option value="">-- Selecciona un regalo --</option>
                        @foreach($masterCatalog as $mg)
                            <option value="{{ $mg->id }}">
                                [{{ $mg->code }}] {{ $mg->name }} — General: {{ (int) $mg->stock_quantity }} pzas
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Almacén Origen *</label>
                        <select name="from_branch_id" id="transferFromBranchSelect" class="form-control">
                            <option value="">Inventario General (Almacén Central)</option>
                            @if($canAllBranches)
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">Sucursal: {{ $b->name }}</option>
                                @endforeach
                            @elseif($userBranchId)
                                @php $ownB = $branches->firstWhere('id', $userBranchId); @endphp
                                @if($ownB)
                                    <option value="{{ $ownB->id }}">Sucursal: {{ $ownB->name }}</option>
                                @endif
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold small">Sucursal Destino *</label>
                        <select name="to_branch_id" id="transferToBranchSelect" class="form-control select2-autocomplete" required data-placeholder="-- Seleccionar destino --">
                            <option value="">-- Selecciona sucursal destino --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row align-items-center bg-light border rounded p-2 mb-3 mx-0">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Disponible en origen seleccionado:</small>
                        <strong id="transferOriginStockLabel" class="text-primary" style="font-size: 1rem;">Selecciona un regalo</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Existencia actual en sucursal destino:</small>
                        <strong id="transferDestStockLabel" class="text-success" style="font-size: 1rem;">—</strong>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Cantidad a traspasar *</label>
                        <input type="number" name="quantity" id="transferQuantityInput" class="form-control" min="1" value="1" required>
                        <small id="transferQtyError" class="text-danger d-none"></small>
                    </div>
                    <div class="col-md-8 form-group mb-0">
                        <label class="font-weight-bold small">Notas / Motivo del traspaso</label>
                        <input type="text" name="notes" class="form-control" maxlength="500" placeholder="Ej. Dotación semanal para promoción de sucursal">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSubmitTransferGift" class="btn btn-warning btn-sm font-weight-bold">
                    <i class="fa-solid fa-paper-plane mr-1"></i>Confirmar Traspaso
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 4: Entrega / Consumo Exclusivo de Sucursal --}}
<div class="modal fade" id="modalDeliverGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('gifts.deliver') }}" method="POST" id="formDeliverGift" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa-solid fa-gift mr-2"></i>Entregar Regalo a Cliente (Stock de Sucursal)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold small">Sucursal que entrega el regalo *</label>
                    @if($canAllBranches)
                        <select name="branch_id" id="deliverBranchSelect" class="form-control" required>
                            @foreach($branches as $b)
                                @php $defaultDeliverBranch = $activeBranchId ?: ($userBranchId ?: $branches->first()?->id); @endphp
                                <option value="{{ $b->id }}" {{ (int) $b->id === (int) $defaultDeliverBranch ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="branch_id" id="deliverBranchSelect" value="{{ $userBranchId }}">
                        <div class="form-control bg-light font-weight-bold">
                            <i class="fa-solid fa-store text-success mr-1"></i>
                            {{ $selectedBranchObj->name ?? 'Tu sucursal asignada' }}
                        </div>
                    @endif
                    <small class="text-muted">Solo se descontará del stock asignado a esta sucursal.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Regalo disponible en la sucursal *</label>
                    <select name="gift_id" id="deliverGiftSelect" class="form-control select2-autocomplete" required data-placeholder="-- Seleccionar regalo con existencia --">
                        <option value="">-- Selecciona un regalo --</option>
                    </select>
                    <small id="deliverBranchStockHelp" class="d-block mt-1 font-weight-bold text-success"></small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Cantidad a entregar *</label>
                    <input type="number" name="quantity" id="deliverQuantityInput" class="form-control" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Cliente (buscar o escribir nombre libre)</label>
                    <select id="deliverCustomerSelect" class="form-control select2-autocomplete" data-tags="true" data-placeholder="-- Buscar cliente o escribir nombre --">
                        <option value="Cliente General">Cliente General</option>
                        @foreach($customers as $c)
                            <option value="cust:{{ $c->id }}" data-customer-id="{{ $c->id }}" data-name="{{ $c->tit_name }}" data-phone="{{ $c->tit_phone }}">
                                {{ $c->tit_name }}{{ $c->tit_phone ? ' (' . $c->tit_phone . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="customer_id" id="deliverCustomerIdInput" value="">
                    <input type="hidden" name="customer_name" id="deliverCustomerNameInput" value="Cliente General">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Teléfono del cliente</label>
                    <input type="text" name="customer_phone" id="deliverCustomerPhoneInput" class="form-control" maxlength="40" placeholder="Opcional">
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold small">Notas de entrega</label>
                    <input type="text" name="notes" class="form-control" maxlength="500" placeholder="Motivo de entrega, venta o campaña...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSubmitDeliverGift" class="btn btn-success btn-sm font-weight-bold">
                    <i class="fa-solid fa-check mr-1"></i>Confirmar Entrega en Sucursal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 5: Editar Ficha del Catálogo Maestro --}}
<div class="modal fade" id="modalEditGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="#" method="POST" id="formEditGift" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa-solid fa-pen-to-square mr-2"></i>Editar Regalo — Catálogo Maestro
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8 form-group">
                        <label class="font-weight-bold small">Nombre del Gift *</label>
                        <input type="text" name="name" id="editGiftName" class="form-control" required maxlength="160">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Código *</label>
                        <input type="text" name="code" id="editGiftCode" class="form-control" required maxlength="50">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Marca</label>
                        <input type="text" name="brand" id="editGiftBrand" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Proveedor Financiera</label>
                        <select name="fin_supplier_id" id="editGiftFinSupplierId" class="form-control">
                            <option value="">-- Ninguno --</option>
                            @foreach($finSuppliers as $fs)
                                <option value="{{ $fs->id }}">{{ $fs->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Proveedor General</label>
                        <select name="supplier_id" id="editGiftSupplierId" class="form-control">
                            <option value="">-- Ninguno --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold small">Nombre libre proveedor</label>
                        <input type="text" name="supplier_name" id="editGiftSupplierName" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold small">Stock Mínimo</label>
                        <input type="number" name="min_stock" id="editGiftMinStock" class="form-control" min="0">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold small">Costo Ref. ($)</label>
                        <input type="number" step="0.01" name="unit_cost" id="editGiftUnitCost" class="form-control" min="0">
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="font-weight-bold small">Estado</label>
                        <select name="status" id="editGiftStatus" class="form-control">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group mb-0">
                        <label class="font-weight-bold small">Descripción</label>
                        <input type="text" name="description" id="editGiftDescription" class="form-control" maxlength="500">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                    <i class="fa-solid fa-save mr-1"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('specificpagescripts')
<script>
(function() {
    const giftCatalogMap = @json($giftInventoryCatalogMap);
    const giftUpdateUrlTemplate = @json(route('gifts.update', ['gift' => '__ID__']));

    function getOriginStock(giftId, fromBranchId) {
        const g = giftCatalogMap[String(giftId)];
        if (!g) return 0;
        if (!fromBranchId) {
            return parseInt(g.central_stock || 0, 10);
        }
        return parseInt((g.branch_stocks && g.branch_stocks[String(fromBranchId)]) || 0, 10);
    }

    // 1. Entrada a Inventario General
    function syncEntryModalInfo() {
        const giftId = $('#entryGiftSelect').val();
        const g = giftCatalogMap[String(giftId)];
        $('#entryCurrentCentralStock').text(g ? (g.central_stock + ' pzas') : '—');
    }

    $('#entryGiftSelect').on('change', syncEntryModalInfo);

    $(document).on('click', '.btn-open-entry', function() {
        const giftId = $(this).data('gift-id');
        $('#modalEntryGift').modal('show');
        $('#entryGiftSelect').val(String(giftId)).trigger('change');
        syncEntryModalInfo();
    });

    // 2. Traspaso a Sucursal con validación en vivo de stock de origen
    function syncTransferModalInfo() {
        const giftId = $('#transferGiftSelect').val();
        const fromBranchId = $('#transferFromBranchSelect').val() || '';
        const toBranchId = $('#transferToBranchSelect').val() || '';

        const $qtyInput = $('#transferQuantityInput');
        const $qtyError = $('#transferQtyError');
        const $submitBtn = $('#btnSubmitTransferGift');

        if (!giftId) {
            $('#transferOriginStockLabel').text('Selecciona un regalo');
            $('#transferDestStockLabel').text('—');
            $qtyInput.removeAttr('max');
            $qtyError.addClass('d-none').text('');
            $submitBtn.prop('disabled', false);
            return;
        }

        const originAvail = getOriginStock(giftId, fromBranchId);
        const destCurr = toBranchId ? getOriginStock(giftId, toBranchId) : null;

        $('#transferOriginStockLabel').text(originAvail + ' pzas')
            .toggleClass('text-danger', originAvail <= 0)
            .toggleClass('text-primary', originAvail > 0);
        $('#transferDestStockLabel').text(destCurr === null ? 'Selecciona destino' : (destCurr + ' pzas'));

        const reqQty = parseInt($qtyInput.val() || 0, 10);
        $qtyInput.attr('max', Math.max(originAvail, 1));

        let errMsg = '';
        if (originAvail <= 0) {
            errMsg = 'No hay piezas disponibles en el almacén origen seleccionado.';
        } else if (toBranchId && String(fromBranchId) === String(toBranchId)) {
            errMsg = 'La sucursal destino debe ser distinta al origen.';
        } else if (reqQty > originAvail) {
            errMsg = 'No puedes traspasar más de ' + originAvail + ' pzas disponibles en origen.';
        }

        if (errMsg) {
            $qtyError.removeClass('d-none').text(errMsg);
            $submitBtn.prop('disabled', true);
        } else {
            $qtyError.addClass('d-none').text('');
            $submitBtn.prop('disabled', false);
        }
    }

    $('#transferGiftSelect, #transferFromBranchSelect, #transferToBranchSelect').on('change', syncTransferModalInfo);
    $('#transferQuantityInput').on('input change', syncTransferModalInfo);

    $(document).on('click', '.btn-open-transfer', function() {
        const giftId = $(this).data('gift-id');
        const fromBranch = $(this).data('from-branch') ?? '';
        $('#modalTransferGift').modal('show');
        $('#transferFromBranchSelect').val(String(fromBranch));
        $('#transferGiftSelect').val(String(giftId)).trigger('change');
        syncTransferModalInfo();
    });

    // 3. Entrega exclusiva por sucursal
    function populateDeliveryGiftOptions(preselectGiftId) {
        const branchId = String($('#deliverBranchSelect').val() || '');
        const $select = $('#deliverGiftSelect');
        const $help = $('#deliverBranchStockHelp');
        const $qty = $('#deliverQuantityInput');
        const $btn = $('#btnSubmitDeliverGift');

        const currentVal = preselectGiftId ? String(preselectGiftId) : String($select.val() || '');
        $select.empty().append('<option value="">-- Selecciona un regalo disponible --</option>');

        let availableCount = 0;
        let hasPreselected = false;
        Object.keys(giftCatalogMap).forEach(function(id) {
            const g = giftCatalogMap[id];
            const branchStock = branchId ? parseInt((g.branch_stocks && g.branch_stocks[branchId]) || 0, 10) : 0;
            if (branchStock > 0) {
                availableCount++;
                const isSelected = id === currentVal;
                if (isSelected) {
                    hasPreselected = true;
                }
                const opt = new Option(`[${g.code}] ${g.name} (Sucursal: ${branchStock} pzas)`, id, isSelected, isSelected);
                opt.setAttribute('data-stock', branchStock);
                $select.append(opt);
            }
        });

        $select.val(hasPreselected ? currentVal : '').trigger('change.select2');

        if (availableCount === 0) {
            $help.removeClass('text-success').addClass('text-danger')
                .text('Esta sucursal no tiene gifts en existencia. Realiza primero un traspaso desde Inventario General.');
            $btn.prop('disabled', true);
            $qty.val(1).removeAttr('max');
        } else {
            syncDeliveryQtyValidation();
        }
    }

    function syncDeliveryQtyValidation() {
        const branchId = String($('#deliverBranchSelect').val() || '');
        const giftId = String($('#deliverGiftSelect').val() || '');
        const $help = $('#deliverBranchStockHelp');
        const $qty = $('#deliverQuantityInput');
        const $btn = $('#btnSubmitDeliverGift');

        if (!giftId || !giftCatalogMap[giftId]) {
            if ($('#deliverGiftSelect option').length > 1) {
                $help.removeClass('text-danger').addClass('text-muted').text('Selecciona un regalo para ver el stock disponible en la sucursal.');
                $btn.prop('disabled', false);
            }
            return;
        }

        const stock = branchId ? getOriginStock(giftId, branchId) : 0;
        $qty.attr('max', Math.max(stock, 1));
        const req = parseInt($qty.val() || 1, 10);

        if (stock <= 0 || req > stock) {
            $help.removeClass('text-success text-muted').addClass('text-danger')
                .text('Stock insuficiente en esta sucursal (Disponible: ' + stock + ' pzas).');
            $btn.prop('disabled', true);
        } else {
            $help.removeClass('text-danger text-muted').addClass('text-success')
                .text('Disponible en sucursal para entregar: ' + stock + ' pzas.');
            $btn.prop('disabled', false);
        }
    }

    $('#deliverBranchSelect').on('change', function() {
        populateDeliveryGiftOptions();
    });
    $('#deliverGiftSelect').on('change', syncDeliveryQtyValidation);
    $('#deliverQuantityInput').on('input change', syncDeliveryQtyValidation);

    $('#modalDeliverGift').on('show.bs.modal', function(e) {
        const trigger = e.relatedTarget ? $(e.relatedTarget) : null;
        if (!trigger || !trigger.hasClass('btn-open-deliver')) {
            populateDeliveryGiftOptions();
        }
    });

    $(document).on('click', '.btn-open-deliver', function() {
        const giftId = String($(this).data('gift-id') || '');
        const branchId = String($(this).data('branch-id') || '');
        if (branchId && $('#deliverBranchSelect').is('select')) {
            $('#deliverBranchSelect').val(branchId);
        }
        $('#modalDeliverGift').modal('show');
        populateDeliveryGiftOptions(giftId);
    });

    // Sincronizar cliente seleccionado o texto libre (tags)
    $('#deliverCustomerSelect').on('change', function() {
        const $selected = $(this).find('option:selected');
        const val = String($(this).val() || '');
        const custId = $selected.data('customer-id');
        const custName = $selected.data('name');
        const custPhone = $selected.data('phone');

        if (custId) {
            $('#deliverCustomerIdInput').val(custId);
            $('#deliverCustomerNameInput').val(custName || 'Cliente');
            if (custPhone) {
                $('#deliverCustomerPhoneInput').val(custPhone);
            }
        } else {
            $('#deliverCustomerIdInput').val('');
            $('#deliverCustomerNameInput').val(val || 'Cliente General');
        }
    });

    // 4. Editar Ficha Maestra
    $(document).on('click', '.btn-open-edit', function() {
        const btn = $(this);
        const id = btn.data('id');
        $('#formEditGift').attr('action', giftUpdateUrlTemplate.replace('__ID__', id));
        $('#editGiftCode').val(btn.data('code') || '');
        $('#editGiftName').val(btn.data('name') || '');
        $('#editGiftBrand').val(btn.data('brand') || '');
        $('#editGiftDescription').val(btn.data('description') || '');
        $('#editGiftSupplierId').val(btn.data('supplier-id') || '');
        $('#editGiftFinSupplierId').val(btn.data('fin-supplier-id') || '');
        $('#editGiftSupplierName').val(btn.data('supplier-name') || '');
        $('#editGiftMinStock').val(btn.data('min-stock') ?? 3);
        $('#editGiftUnitCost').val(btn.data('unit-cost') ?? 0);
        $('#editGiftStatus').val(btn.data('status') || 'activo');
        $('#modalEditGift').modal('show');
    });

    // Inicializar estado de stock al cargar
    syncEntryModalInfo();
})();
</script>
@endsection
