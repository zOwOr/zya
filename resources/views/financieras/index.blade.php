@extends('dashboard.body.main')

@section('specificpagestyles')
<style>
    .stat-card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    }
    .nav-tabs-financieras .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 600;
        color: #6c757d;
        padding: 0.85rem 1.5rem;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .nav-tabs-financieras .nav-link.active {
        color: #32475c;
        border-bottom-color: #32475c;
        background: transparent;
    }
    .nav-tabs-financieras .nav-link:hover:not(.active) {
        border-bottom-color: #dee2e6;
        color: #495057;
    }
    .badge-tab-count {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 10px;
        margin-left: 0.4rem;
    }
    .ws-live-indicator {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .ws-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
        animation: pulseAnimation 1.8s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(0.95); opacity: 0.9; }
        50% { transform: scale(1.3); opacity: 0.4; }
        100% { transform: scale(0.95); opacity: 0.9; }
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Banner Principal y Búsqueda Cruzada de IMEI -->
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-12 mb-3 mb-lg-0">
                            <div class="d-flex align-items-center">
                                <h3 class="font-weight-bold text-white mb-0 mr-3">
                                    <i class="fa-solid fa-building-columns mr-2"></i>Módulo de Financieras
                                </h3>
                            </div>
                            <p class="mb-0 text-white-50 font-size-14 mt-1">
                                Gestión centralizada de ventas a crédito, inventario por IMEI, garantías y reportes de robos.
                            </p>
                        </div>
                        <div class="col-lg-6 col-12">
                            <!-- Barra de búsqueda rápida de IMEI -->
                            <div class="bg-white p-2 rounded shadow-sm">
                                <div class="input-group">
                                    <input type="text" id="crossImeiInput" class="form-control border-0 font-weight-bold"
                                        placeholder="🔍 Consulta rápida de IMEI entre las 4 secciones..." autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-warning px-3 font-weight-bold text-white" id="btnCrossLookup">
                                            <i class="fa-solid fa-search mr-1"></i>Consultar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card shadow-sm border-0 bg-white">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-12 text-uppercase font-weight-bold">Ventas Activas</span>
                                <h3 class="font-weight-bold text-primary mb-0 mt-1" id="statVentas">{{ $counts['ventas'] }}</h3>
                            </div>
                            <div class="bg-light-primary p-3 rounded-circle text-primary font-size-24">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card shadow-sm border-0 bg-white">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-12 text-uppercase font-weight-bold">Dispositivos Disponibles</span>
                                <h3 class="font-weight-bold text-success mb-0 mt-1" id="statInventario">{{ $counts['inventario_disponible'] }}</h3>
                            </div>
                            <div class="bg-light-success p-3 rounded-circle text-success font-size-24">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card shadow-sm border-0 bg-white">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-12 text-uppercase font-weight-bold">Garantías en Proceso</span>
                                <h3 class="font-weight-bold text-warning mb-0 mt-1" id="statGarantias">{{ $counts['garantias_activas'] }}</h3>
                            </div>
                            <div class="bg-light-warning p-3 rounded-circle text-warning font-size-24">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stat-card shadow-sm border-0 bg-white">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted font-size-12 text-uppercase font-weight-bold">Reportes de Robo</span>
                                <h3 class="font-weight-bold text-danger mb-0 mt-1" id="statRobos">{{ $counts['robos_activos'] }}</h3>
                            </div>
                            <div class="bg-light-danger p-3 rounded-circle text-danger font-size-24">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navegación por Tabs -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center px-3 pt-2 border-bottom">
                        <ul class="nav nav-tabs nav-tabs-financieras border-0" id="financierasTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'ventas' ? 'active' : '' }}" id="tab-link-ventas" data-toggle="tab" href="#section-ventas" role="tab" data-tab-name="ventas">
                                    <i class="fa-solid fa-cart-shopping mr-2 text-primary"></i>1. Clientes / Ventas
                                    <span class="badge badge-primary badge-tab-count">{{ $counts['ventas'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'inventario' ? 'active' : '' }}" id="tab-link-inventario" data-toggle="tab" href="#section-inventario" role="tab" data-tab-name="inventario">
                                    <i class="fa-solid fa-boxes-stacked mr-2 text-success"></i>2. Inventario
                                    <span class="badge badge-success badge-tab-count">{{ $counts['inventario_disponible'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'garantias' ? 'active' : '' }}" id="tab-link-garantias" data-toggle="tab" href="#section-garantias" role="tab" data-tab-name="garantias">
                                    <i class="fa-solid fa-wrench mr-2 text-warning"></i>3. Garantías
                                    <span class="badge badge-warning text-white badge-tab-count">{{ $counts['garantias_activas'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'robos' ? 'active' : '' }}" id="tab-link-robos" data-toggle="tab" href="#section-robos" role="tab" data-tab-name="robos">
                                    <i class="fa-solid fa-shield-halved mr-2 text-danger"></i>4. Reporte de Robos
                                    <span class="badge badge-danger badge-tab-count">{{ $counts['robos_activos'] }}</span>
                                </a>
                            </li>
                        </ul>

                        <div class="py-2">
                            <a href="{{ route('financieras.catalogos.index') }}" class="btn btn-outline-dark btn-sm">
                                <i class="fa-solid fa-layer-group mr-1"></i>Catálogos (Financieras / Marcas / Proveedores / Etapas)
                            </a>
                        </div>
                    </div>

                    <!-- Contenido de las Secciones / Tabs -->
                    <div class="tab-content p-3" id="financierasTabsContent">
                        <!-- Sección 1: Ventas -->
                        <div class="tab-pane fade {{ $activeTab === 'ventas' ? 'show active' : '' }}" id="section-ventas" role="tabpanel">
                            @include('financieras.ventas.index')
                        </div>

                        <!-- Sección 2: Inventario -->
                        <div class="tab-pane fade {{ $activeTab === 'inventario' ? 'show active' : '' }}" id="section-inventario" role="tabpanel">
                            @include('financieras.inventario.index')
                        </div>

                        <!-- Sección 3: Garantías -->
                        <div class="tab-pane fade {{ $activeTab === 'garantias' ? 'show active' : '' }}" id="section-garantias" role="tabpanel">
                            @include('financieras.garantias.index')
                        </div>

                        <!-- Sección 4: Robos -->
                        <div class="tab-pane fade {{ $activeTab === 'robos' ? 'show active' : '' }}" id="section-robos" role="tabpanel">
                            @include('financieras.robos.index')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Búsqueda Cruzada por IMEI (Cross-Cutting IMEI Modal) -->
<div class="modal fade" id="crossImeiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white">
                    <i class="fa-solid fa-crosshairs mr-2 text-warning"></i>Trazabilidad Cruzada de IMEI
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="crossModalBody">
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-2"></i>
                    <p class="mb-0">Consultando información del IMEI...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('specificpagescripts')
<!-- Pusher JS (Reverb Client Compatible) -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
$(document).ready(function() {
    // Preservar tab activo en URL
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        let tab = $(e.target).data('tab-name');
        let url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    });

    // Búsqueda cruzada de IMEI
    $('#btnCrossLookup, #crossImeiInput').on('keydown click', function(e) {
        if (e.type === 'keydown' && e.which !== 13) return;
        let imei = $('#crossImeiInput').val().trim();
        if (!imei) {
            Swal.fire({
                icon: 'warning',
                title: 'Ingrese un IMEI',
                text: 'Por favor escriba un número de IMEI para buscar.'
            });
            return;
        }

        $('#crossImeiModal').modal('show');
        $('#crossModalBody').html('<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2"></i><p class="mb-0">Buscando IMEI: <strong>' + imei + '</strong>...</p></div>');

        $.get("{{ route('financieras.devices.lookup-imei') }}", { imei: imei }, function(res) {
            if (!res.found) {
                $('#crossModalBody').html(`
                    <div class="alert alert-warning mb-0 text-center py-4">
                        <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-warning"></i>
                        <h5>Dispositivo no encontrado en inventario</h5>
                        <p class="mb-3 text-muted">El IMEI <strong>${imei}</strong> no se encuentra registrado en el sistema.</p>
                        <div>
                            <a href="{{ route('financieras.inventario.create') }}?imei=${imei}" class="btn btn-primary btn-sm mr-2">
                                <i class="fa-solid fa-plus mr-1"></i>Registrar en Inventario
                            </a>
                            <a href="{{ route('financieras.ventas.create') }}?imei=${imei}" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-cart-plus mr-1"></i>Crear Venta Directa
                            </a>
                        </div>
                    </div>
                `);
                return;
            }

            let d = res.device;
            let statusBadge = '';
            switch(d.status) {
                case 'disponible': statusBadge = '<span class="badge badge-success">Disponible en Almacén</span>'; break;
                case 'vendido': statusBadge = '<span class="badge badge-secondary">Vendido / En Posesión de Cliente</span>'; break;
                case 'en_garantia': statusBadge = '<span class="badge badge-warning text-white">En Garantía Técnica</span>'; break;
                case 'robado': statusBadge = '<span class="badge badge-danger">Reportado como Robado</span>'; break;
            }

            let saleSection = '<div class="p-3 bg-light rounded text-muted">No cuenta con registro de venta activa.</div>';
            if (d.latest_sale) {
                let s = d.latest_sale;
                saleSection = `
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="font-weight-bold text-success font-size-16">${s.sale_code}</span>
                            <span class="badge badge-${s.status === 'activa' ? 'success' : 'danger'}">${s.status.toUpperCase()}</span>
                        </div>
                        <div class="font-size-14 text-dark">
                            <strong>Cliente:</strong> ${s.customer_name || 'Sin registrar'} (${s.customer_phone || 'Sin tel'})<br>
                            <strong>Financiera:</strong> ${s.financiera ? s.financiera.name : 'Directo'} | <strong>Vendedor:</strong> ${s.seller ? s.seller.name : 'N/A'}<br>
                            <strong>Precio:</strong> $${parseFloat(s.price).toFixed(2)} | <strong>Enganche:</strong> $${parseFloat(s.down_payment).toFixed(2)}
                        </div>
                        <div class="mt-2">
                            <a href="/financieras/ventas/${s.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye mr-1"></i>Abrir Venta
                            </a>
                        </div>
                    </div>
                `;
            }

            let actionsButtons = `
                <div class="d-flex flex-wrap mt-3 pt-3 border-top">
                    <a href="/financieras/inventario/${d.id}/history" class="btn btn-info btn-sm mr-2 mb-2">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i>Trazabilidad Completa
                    </a>
                    <a href="{{ route('financieras.garantias.create') }}?imei=${d.imei}" class="btn btn-warning text-white btn-sm mr-2 mb-2">
                        <i class="fa-solid fa-wrench mr-1"></i>Abrir Garantía
                    </a>
                    <a href="{{ route('financieras.robos.create') }}?imei=${d.imei}" class="btn btn-danger btn-sm mr-2 mb-2">
                        <i class="fa-solid fa-shield-halved mr-1"></i>Reportar Robo
                    </a>
                    ${d.status === 'disponible' ? `<a href="{{ route('financieras.ventas.create') }}?imei=${d.imei}" class="btn btn-success btn-sm mb-2"><i class="fa-solid fa-cart-shopping mr-1"></i>Vender Equipo</a>` : ''}
                </div>
            `;

            $('#crossModalBody').html(`
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border mb-0 h-100">
                            <div class="card-header bg-light py-2"><strong>Datos del Dispositivo</strong></div>
                            <div class="card-body">
                                <div class="font-size-20 font-weight-bold text-primary mb-2">${d.imei}</div>
                                <p class="mb-1"><strong>Equipo:</strong> ${d.brand ? d.brand.name : ''} ${d.model} (${d.color || 'Sin color'})</p>
                                <p class="mb-1"><strong>Sucursal:</strong> ${d.branch ? d.branch.name : 'N/A'}</p>
                                <p class="mb-0"><strong>Estado:</strong> ${statusBadge}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border mb-0 h-100">
                            <div class="card-header bg-light py-2"><strong>Información de Venta</strong></div>
                            <div class="card-body p-2">
                                ${saleSection}
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        ${actionsButtons}
                    </div>
                </div>
            `);
        }).fail(function() {
            $('#crossModalBody').html('<div class="alert alert-danger mb-0">Error al consultar el servidor.</div>');
        });
    });

    // Helper Modals
    window.openCancelModal = function(id, code, imei) {
        $('#cancelSaleCode').text(code);
        $('#cancelSaleImei').text(imei);
        $('#cancelSaleForm').attr('action', '/financieras/ventas/' + id + '/cancel');
        $('#cancelSaleModal').modal('show');
    };

    window.openTransferModal = function(id, imei, branchId, branchName) {
        $('#transferImei').text(imei);
        $('#currentBranchName').val(branchName);
        $('#toBranchSelect option').prop('disabled', false);
        $('#toBranchSelect option[value="' + branchId + '"]').prop('disabled', true);
        $('#transferForm').attr('action', '/financieras/inventario/' + id + '/transfer');
        $('#transferModal').modal('show');
    };

    // =========================================================================
    // WEBSOCKET REAL CON LARAVEL REVERB
    // =========================================================================
    try {
        const reverbKey = "{{ env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY', 'c1bydkd9ye8t035x7twu')) }}";
        const configuredHost = "{{ env('VITE_REVERB_HOST', env('REVERB_HOST', 'localhost')) }}";
        const reverbHost = (configuredHost === 'localhost' || configuredHost === '127.0.0.1')
            ? (window.location.hostname || '127.0.0.1')
            : configuredHost;
        const reverbPort = {{ env('VITE_REVERB_PORT', env('REVERB_PORT', 8080)) }};
        const reverbScheme = "{{ env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'http')) }}";
        const isSecure = reverbScheme === 'https';

        const pusher = new Pusher(reverbKey, {
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: isSecure,
            enabledTransports: isSecure ? ['wss'] : ['ws'],
            cluster: 'mt1'
        });

        let failCount = 0;

        pusher.connection.bind('connected', function() {
            failCount = 0;
            $('#wsStatusBadge').removeClass('bg-light text-muted').addClass('bg-white text-dark');
            $('#wsPulseDot').removeClass('bg-warning bg-danger bg-secondary').addClass('bg-success');
            $('#wsStatusText').text('Reverb En Vivo');
        });

        pusher.connection.bind('unavailable', function() {
            failCount++;
            if (failCount >= 3) {
                pusher.disconnect();
                $('#wsPulseDot').removeClass('bg-success bg-warning').addClass('bg-secondary');
                $('#wsStatusText').text('Reverb Offline');
            } else {
                $('#wsPulseDot').removeClass('bg-success bg-danger').addClass('bg-warning');
                $('#wsStatusText').text('Reverb Reconectando...');
            }
        });

        pusher.connection.bind('error', function() {
            failCount++;
            if (failCount >= 3) {
                pusher.disconnect();
                $('#wsPulseDot').removeClass('bg-success bg-warning').addClass('bg-secondary');
                $('#wsStatusText').text('Reverb Offline');
            }
        });

        const channel = pusher.subscribe('financieras');
        channel.bind('financieras.updated', function(data) {
            // Sonido o toast interactivo
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Actualización en Tiempo Real',
                text: data.message || 'Se ha registrado un movimiento en Financieras',
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true
            });

            // Actualizar tabla si pertenece a la sección activa
            let currentTab = new URL(window.location).searchParams.get('tab') || 'ventas';
            if (data.section === currentTab) {
                // Refresco suave de tabla
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        });
    } catch(err) {
        console.warn('WebSocket Reverb init info:', err);
    }
});
</script>
@endsection
