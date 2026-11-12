<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Zya</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/backend-plugin.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/backend.css?v=1.0.0') }}">

    <link rel="stylesheet" href="{{ asset('assets/vendor/line-awesome/dist/line-awesome/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/remixicon/fonts/remixicon.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<style>
  /* Select2 Bootstrap 4 styling & autocomplete */
  .select2-container .select2-selection--single {
    height: calc(1.5em + .75rem + 2px) !important;
    padding: .375rem .75rem !important;
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
    display: flex !important;
    align-items: center !important;
  }
  .form-control-sm + .select2-container .select2-selection--single,
  .select2-sm + .select2-container .select2-selection--single {
    height: calc(1.5em + .5rem + 2px) !important;
    padding: .25rem .5rem !important;
    font-size: .875rem !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: normal !important;
    padding-left: 0 !important;
    color: #495057 !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    right: 8px !important;
    top: 0 !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d !important;
  }
  .select2-container .select2-selection--single:focus,
  .select2-container--open .select2-selection--single {
    border-color: #80bdff !important;
    outline: 0 !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
  }
  .select2-dropdown {
    border-color: #80bdff !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    z-index: 1065 !important;
  }
  .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
    padding: .375rem .75rem !important;
    outline: none !important;
  }

  .iq-sidebar {
    height: 100vh;
    overflow: hidden !important;
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  .iq-sidebar::-webkit-scrollbar {
    display: none;
  }
  .iq-sidebar .data-scrollbar {
    height: calc(100vh - 75px) !important;
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  .iq-sidebar .data-scrollbar::-webkit-scrollbar {
    display: none;
  }
  .iq-sidebar .scroll-content {
    overflow-x: hidden !important;
  }
  .iq-sidebar .sidebar-section-title {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.9px;
    color: #8c98a4;
    padding: 16px 20px 6px 20px;
    margin: 0;
    pointer-events: none;
    line-height: 1;
  }
  .iq-sidebar .sidebar-section-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.06);
    margin: 8px 16px;
    border: none;
  }
  body.sidebar-main .sidebar-section-title {
    display: none !important;
  }
  body.sidebar-main .sidebar-section-divider {
    margin: 8px 10px;
  }
  .iq-sidebar-menu .iq-menu li a i {
    width: 22px;
    text-align: center;
    font-size: 16px;
    display: inline-block;
  }
  .iq-sidebar-menu .iq-submenu li a {
    padding-left: 46px !important;
    font-size: 13px;
  }
  .iq-sidebar-menu .iq-submenu li a i {
    width: 16px;
    font-size: 11px;
  }
  /* Sidebar Header & Toggle */
  .sidebar-header {
    height: 75px;
    min-height: 75px;
    max-height: 75px;
    padding: 0 16px 0 20px;
    box-sizing: border-box;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
  }
  .sidebar-header .sidebar-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
  }
  .sidebar-header .sidebar-logo img {
    height: 50px;
    width: auto;
    max-width: 175px;
    object-fit: contain;
  }
  /* Navbar Profile Dropdown Enhancements */
  .iq-top-navbar {
    z-index: 1020 !important;
  }
  .iq-top-navbar .dropdown-menu-right,
  .iq-top-navbar .caption-content .dropdown-menu {
    right: 0 !important;
    left: auto !important;
    top: 100% !important;
    margin-top: 8px !important;
    transform: none !important;
    animation: none !important;
    z-index: 1060 !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
    border-radius: 10px !important;
    min-width: 290px;
    max-width: 320px;
  }
  .iq-top-navbar .caption-content.show .dropdown-menu,
  .iq-top-navbar .caption-content .dropdown-menu.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
  }
  .iq-top-navbar .caption-content .dropdown-menu:not(.show) {
    display: none !important;
  }
  .sidebar-toggle-btn {
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    color: #475569;
    font-size: 19px;
    transition: background 0.2s ease, color 0.2s ease, transform 0.1s ease;
    user-select: none;
    border: 1px solid transparent;
  }
  .sidebar-toggle-btn:hover {
    background: #f1f5f9;
    color: #110A57;
    border-color: #e2e8f0;
  }
  .sidebar-toggle-btn:active {
    background: #e2e8f0;
    transform: scale(0.93);
  }

  /* Desktop collapsed sidebar (body.sidebar-main on >= 1300px) */
  @media (min-width: 1300px) {
    body.sidebar-main .sidebar-header {
      padding: 0 !important;
      justify-content: center !important;
    }
    body.sidebar-main .sidebar-header .sidebar-logo {
      display: none !important;
    }
    body.sidebar-main .sidebar-header .sidebar-toggle-btn {
      margin: 0 auto !important;
      color: #110A57;
    }
  }

  @media (max-width: 1299px) {
    body.sidebar-main .sidebar-header {
      padding: 0 16px 0 20px !important;
      justify-content: space-between !important;
    }
    body.sidebar-main .sidebar-header .sidebar-logo {
      display: flex !important;
    }
    body.sidebar-main .iq-sidebar {
      width: 260px !important;
      left: 0 !important;
      z-index: 9999 !important;
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    body.sidebar-main .iq-sidebar .iq-sidebar-menu .iq-menu li a span,
    body.sidebar-main .iq-sidebar .iq-sidebar-menu .iq-menu li a .iq-arrow-right {
      display: inline-block !important;
      opacity: 1 !important;
    }
  }

  /* =========================================================
     SISTEMA DE DISEÑO GLOBAL ESTANDARIZADO DE TABLAS ZYA
     ========================================================= */
  .table {
      color: #1e293b;
      vertical-align: middle;
      font-size: 13px;
      width: 100% !important;
  }
  .table thead th,
  .table .thead-light th,
  .table .thead-dark th,
  .table thead.thead-dark th,
  .table thead.thead-light th,
  .table thead.bg-light th,
  .table thead.bg-white th,
  .table .ligth th,
  .table .ligth.ligth-data th,
  table.dataTable thead th,
  table.dataTable thead td {
      background-color: #f8fafc !important;
      border-bottom: 2px solid #e2e8f0 !important;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      color: #475569 !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
      padding: 12px 14px !important;
      white-space: nowrap !important;
      vertical-align: middle !important;
      line-height: 1.4 !important;
  }
  .table tbody td,
  .table .ligth-body tr td,
  table.dataTable tbody td {
      padding: 12px 14px !important;
      vertical-align: middle !important;
      border-top: 1px solid #f1f5f9 !important;
      border-bottom: none !important;
      border-left: none !important;
      border-right: none !important;
      color: #1e293b;
      font-size: 13px;
  }
  .table-hover tbody tr:hover,
  .table tbody tr:hover,
  table.dataTable tbody tr:hover {
      background-color: #f8fafc !important;
  }

  /* Contenedores de Tarjetas para Tablas */
  .card {
      border-radius: 12px !important;
      border: 1px solid #e2e8f0 !important;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
  }
  .card .card-header {
      border-top-left-radius: 12px !important;
      border-top-right-radius: 12px !important;
  }
  .table-responsive {
      border-radius: 0 0 12px 12px !important;
  }

  /* Botones DataTables de Exportación (Excel, PDF, Copiar) */
  .dt-buttons {
      margin-bottom: 12px !important;
  }
  .dt-buttons .btn,
  .dt-buttons .btn-secondary,
  .dt-buttons button.dt-button,
  div.dt-buttons .dt-button {
      font-size: 12px !important;
      font-weight: 600 !important;
      padding: 6px 14px !important;
      border-radius: 6px !important;
      background: #ffffff !important;
      color: #475569 !important;
      border: 1px solid #cbd5e1 !important;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
      margin-right: 6px !important;
      transition: all 0.15s ease !important;
  }
  .dt-buttons .btn:hover,
  div.dt-buttons .dt-button:hover {
      background: #f1f5f9 !important;
      color: #1e293b !important;
      border-color: #94a3b8 !important;
  }

  /* =========================================================
     COLORES DE ALTA LEGIBILIDAD PARA TRANSFERENCIA Y TEXT-INFO
     ========================================================= */
  .text-transfer,
  .text-transf,
  .text-info {
      color: #0284c7 !important; /* Azul Océano / Sky-600 legible con alto contraste */
  }
  input.text-transfer,
  input.text-transf,
  input.text-info,
  label.text-transfer,
  label.text-transf,
  label.text-info {
      color: #0369a1 !important; /* Sky-700 / Más oscuro y nítido para textos y números */
  }
  .badge-info,
  .badge-transfer {
      background-color: #e0f2fe !important;
      color: #0369a1 !important;
      border: 1px solid #bae6fd !important;
  }
  .dataTables_filter {
      margin-bottom: 12px !important;
  }
  .dataTables_filter input {
      border: 1px solid #cbd5e1 !important;
      border-radius: 6px !important;
      padding: 6px 12px !important;
      font-size: 13px !important;
      outline: none !important;
  }
  .dataTables_filter input:focus {
      border-color: #3b82f6 !important;
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
  }
  .dataTables_length select {
      border: 1px solid #cbd5e1 !important;
      border-radius: 6px !important;
      padding: 5px 10px !important;
      font-size: 13px !important;
  }
  .dataTables_info {
      font-size: 12px !important;
      color: #64748b !important;
      padding-top: 14px !important;
  }
  .dataTables_paginate {
      padding-top: 10px !important;
  }
  .dataTables_paginate .pagination .page-item .page-link,
  .dataTables_paginate .paginate_button {
      font-size: 12px !important;
      padding: 6px 12px !important;
      border-radius: 6px !important;
      margin: 0 2px !important;
      color: #475569 !important;
      border: 1px solid #e2e8f0 !important;
  }
  .dataTables_paginate .pagination .page-item.active .page-link,
  .dataTables_paginate .paginate_button.current {
      background-color: #0d6efd !important;
      border-color: #0d6efd !important;
      color: #ffffff !important;
  }
</style>

    @yield('specificpagestyles')
</head>

<body>
    <!-- loader Start -->
    {{-- <div id="loading">
        <div id="loading-center"></div>
    </div> --}}
    <!-- loader END -->

    <!-- Wrapper Start -->
    <div class="wrapper">
        @include('dashboard.body.sidebar')

        @include('dashboard.body.navbar')

        <div class="content-page">
            @yield('container')
        </div>
    </div>
    <!-- Wrapper End-->

    @include('dashboard.body.footer')

    <!-- Backend Bundle JavaScript -->
    <script src="{{ asset('assets/js/backend-bundle.min.js') }}"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @yield('specificpagescripts')

    <!-- App JavaScript -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables JS and Export Buttons -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        function toggleProfileDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var menu = document.getElementById('profileDropdownMenu');
            var parent = menu ? menu.closest('.dropdown') : null;
            if (menu) {
                var isOpen = menu.classList.contains('show');
                if (isOpen) {
                    menu.classList.remove('show');
                    if (parent) parent.classList.remove('show');
                } else {
                    menu.classList.add('show');
                    if (parent) parent.classList.add('show');
                }
            }
        }

        document.addEventListener('click', function(e) {
            var menu = document.getElementById('profileDropdownMenu');
            var toggle = document.getElementById('dropdownMenuButton4');
            if (menu && menu.classList.contains('show')) {
                if (!menu.contains(e.target) && (!toggle || !toggle.contains(e.target))) {
                    menu.classList.remove('show');
                    if (menu.closest('.dropdown')) {
                        menu.closest('.dropdown').classList.remove('show');
                    }
                }
            }
        });

        // ==========================================
        // CONTROLADOR GLOBAL DE CAJÓN FÍSICO USB (ZYA)
        // ==========================================
        window.ZyaPosHardware = {
            KICK_DRAWER_BYTES: new Uint8Array([
                0x1B, 0x70, 0x00, 0x19, 0xFA,
                0x1B, 0x70, 0x01, 0x19, 0xFA,
                0x10, 0x14, 0x01, 0x00, 0x05,
                0x07, 0x0A
            ]),

            isWebSerialSupported: function () {
                return 'serial' in navigator;
            },

            _kickPort: async function (port) {
                await port.open({ baudRate: 9600, dataBits: 8, stopBits: 1, parity: 'none' });
                try {
                    await port.setSignals({ dataTerminalReady: true, requestToSend: true });
                } catch (e) {}

                const writer = port.writable.getWriter();
                await writer.write(this.KICK_DRAWER_BYTES);
                writer.releaseLock();

                await new Promise(resolve => setTimeout(resolve, 120));

                try {
                    await port.setSignals({ dataTerminalReady: false, requestToSend: false });
                } catch (e) {}

                await port.close();
            },

            autoOpenUsbDrawer: async function () {
                if (!this.isWebSerialSupported()) return false;
                try {
                    const ports = await navigator.serial.getPorts();
                    if (ports && ports.length > 0) {
                        await this._kickPort(ports[0]);
                        return true;
                    }
                } catch (err) {
                    console.warn('[ZyaPosHardware] Error en apertura automática USB:', err);
                }
                return false;
            },

            manualOpenUsbDrawer: async function () {
                if (!this.isWebSerialSupported()) {
                    alert('Para controlar el cajón USB se recomienda usar Google Chrome o Microsoft Edge.');
                    return false;
                }
                try {
                    let ports = await navigator.serial.getPorts();
                    let port = (ports && ports.length > 0) ? ports[0] : null;
                    if (!port) {
                        port = await navigator.serial.requestPort();
                    }
                    await this._kickPort(port);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cajón Abierto!',
                            text: 'Señal física enviada correctamente por USB.',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    return true;
                } catch (err) {
                    if (err.name !== 'NotFoundError') {
                        console.error('[ZyaPosHardware] Error al abrir cajón USB:', err);
                        alert('No se pudo comunicar con el cajón USB: ' + (err.message || err));
                    }
                    return false;
                }
            }
        };

        $(document).ready(function() {
            if ($('.datatable-export').length > 0) {
                $('.datatable-export').each(function() {
                    let table = $(this);

                    // Si no tiene permiso de exportar, no inicializar los botones
                    let canExport = table.attr('data-can-export');
                    if (canExport === 'false' || canExport === false) {
                        return;
                    }

                    // Buscar el título del módulo en un <h4> cercano o usar el título de la página
                    let moduleTitle = table.closest('.container-fluid').find('h4').first().text().trim();
                    if (!moduleTitle) {
                        moduleTitle = document.title || 'Exportación_de_Datos';
                    }

                    // Opciones de exportación para omitir columnas específicas
                    let exportOptions = {
                        columns: function (idx, data, node) {
                            let title = $(node).text().trim().toLowerCase();
                            // Excluir columnas de Acción y Foto
                            if (title === 'accion' || title === 'acción' || title === 'action' || title === 'foto' || title === 'photo') {
                                return false;
                            }
                            return true;
                        }
                    };

                    if (!$.fn.DataTable.isDataTable(this)) {
                        // Evitar error tn/18 de DataTables ("Incorrect column count") cuando Blade renderiza una fila vacía con colspan
                        table.find('tbody tr').each(function() {
                            if ($(this).find('td[colspan]').length > 0) {
                                $(this).remove();
                            }
                        });

                        table.DataTable({
                            dom: '<"row align-items-center mb-3"<"col-md-12 text-right"B>>rt',
                            buttons: [
                                {
                                    extend: 'excelHtml5',
                                    text: '<i class="fa-solid fa-file-excel"></i> Excel',
                                    className: 'btn btn-success btn-sm m-1',
                                    title: moduleTitle,
                                    exportOptions: exportOptions
                                },
                                {
                                    extend: 'pdfHtml5',
                                    text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                                    className: 'btn btn-danger btn-sm m-1',
                                    orientation: 'landscape',
                                    title: moduleTitle,
                                    exportOptions: exportOptions
                                }
                            ],
                            paging: false,
                            searching: false,
                            ordering: false,
                            info: false,
                            language: {
                                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-MX.json"
                            }
                        });
                    }
                });
            }
        });

        // ==========================================
        // AUTOCOMPLETE GLOBAL PARA SELECTS (CLIENTES / PRODUCTOS / ETC)
        // ==========================================
        window.initSelect2Autocompletes = function(container) {
            var $scope = container ? $(container) : $(document);
            $scope.find('.select2-autocomplete, select[data-autocomplete="true"]').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                var placeholder = $select.attr('placeholder') || $select.data('placeholder') || '-- Escribe para buscar o seleccionar --';
                var $modal = $select.closest('.modal');
                var config = {
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: !$select.prop('required'),
                    tags: $select.data('tags') === true || $select.data('tags') === 'true',
                    language: {
                        noResults: function() { return "No se encontraron coincidencias"; },
                        searching: function() { return "Buscando..."; }
                    }
                };
                if ($modal.length > 0) {
                    config.dropdownParent = $modal;
                }
                $select.select2(config);
            });
        };

        $(document).ready(function() {
            window.initSelect2Autocompletes();
        });

        // Re-inicializar cuando se abra cualquier modal
        $(document).on('shown.bs.modal', '.modal', function() {
            window.initSelect2Autocompletes(this);
        });

        // ==========================================
        // PROTECCIÓN GLOBAL CONTRA DOBLE SUBMIT
        // ==========================================
        window.resetSubmitButtons = function($form) {
            if (!$form || !$form.length) return;
            $form.data('submitting', false);
            var $btns = $form.find('button[type="submit"], input[type="submit"]');
            $btns.each(function() {
                var $btn = $(this);
                $btn.prop('disabled', false);
                if ($btn.data('original-html')) {
                    $btn.html($btn.data('original-html'));
                }
                if ($btn.data('original-val')) {
                    $btn.val($btn.data('original-val'));
                }
            });
        };

        $(document).on('submit', 'form:not([method="GET"]):not([method="get"]):not(.no-double-submit-prevent)', function(e) {
            var $form = $(this);

            // Si algún manejador previo canceló el submit (validación fallida)
            if (e.isDefaultPrevented()) {
                return;
            }

            // Si falla la validación HTML5 nativa
            if (this.checkValidity && !this.checkValidity()) {
                return;
            }

            // Si ya está en proceso de envío, bloquear el envío concurrente
            if ($form.data('submitting') === true) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            // Marcar formulario como en envío
            $form.data('submitting', true);

            var isFileUpload = ($form.attr('enctype') === 'multipart/form-data');
            var processingText = isFileUpload ? 'Subiendo e importando...' : 'Procesando...';

            var $submitButtons = $form.find('button[type="submit"], input[type="submit"]');
            $submitButtons.each(function() {
                var $btn = $(this);
                $btn.data('original-html', $btn.html());
                $btn.data('original-val', $btn.val());

                // Deshabilitar de inmediato tras permitir el despacho del evento
                setTimeout(function() {
                    $btn.prop('disabled', true);
                    if ($btn.is('button')) {
                        $btn.html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> ' + processingText);
                    } else {
                        $btn.val(processingText);
                    }
                }, 10);
            });
        });

        // Restaurar estado al cerrar un modal
        $(document).on('hidden.bs.modal', '.modal', function() {
            var $form = $(this).find('form');
            if ($form.length > 0) {
                window.resetSubmitButtons($form);
            }
        });

        // Restaurar estado si el usuario navega hacia atrás usando la caché del navegador
        window.addEventListener('pageshow', function(event) {
            $('form').each(function() {
                window.resetSubmitButtons($(this));
            });
        });

        // Impresión directa de tickets en segundo plano sin abrir nueva pestaña del navegador
        window.printTicketDirectly = function(url) {
            if (!url) return;
            var oldFrame = document.getElementById('posDirectPrintFrame');
            if (oldFrame) {
                oldFrame.remove();
            }
            var frame = document.createElement('iframe');
            frame.id = 'posDirectPrintFrame';
            frame.name = 'posDirectPrintFrame';
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '1px';
            frame.style.height = '1px';
            frame.style.border = '0';
            frame.style.opacity = '0.01';
            frame.style.pointerEvents = 'none';

            frame.onload = function() {
                try {
                    if (frame.contentWindow && frame.contentWindow.location.href !== 'about:blank') {
                        setTimeout(function() {
                            frame.contentWindow.focus();
                            frame.contentWindow.print();
                        }, 250);
                    }
                } catch (e) {
                    console.error('Error al imprimir ticket directo:', e);
                }
            };

            document.body.appendChild(frame);
            frame.src = url;
        };

        $(document).on('click', '.btn-direct-print, [data-direct-print]', function(e) {
            e.preventDefault();
            var url = $(this).data('direct-print') || $(this).attr('href');
            if (url && url !== '#') {
                window.printTicketDirectly(url);
            }
        });
    </script>
</body>

</html>
