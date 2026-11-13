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

        // Almacenamiento seguro (evita excepciones en modo privado o bloqueo de cookies de Safari/Firefox)
        window.zyaTicketStorage = {
            get: function(key, fallback) {
                try {
                    var val = window.localStorage ? window.localStorage.getItem(key) : null;
                    return val !== null && val !== undefined ? val : fallback;
                } catch (e) {
                    return fallback;
                }
            },
            set: function(key, value) {
                try {
                    if (window.localStorage) {
                        window.localStorage.setItem(key, value);
                    }
                } catch (e) {}
            },
            getPaperMode: function() {
                var mode = this.get('zya_ticket_paper_mode', '');
                if (mode === '80' || mode === '58' || mode === 'letter') {
                    return mode;
                }
                return this.get('zya_ticket_width', '') === '58' ? '58' : '80';
            }
        };

        window.normalizeTicketUrl = function(rawUrl) {
            if (!rawUrl || typeof rawUrl !== 'string') return '';
            if (rawUrl.charAt(0) === '/') return rawUrl;
            try {
                var parsed = new URL(rawUrl, window.location.origin);
                if (
                    parsed.origin === window.location.origin ||
                    parsed.hostname === window.location.hostname ||
                    /^\/(central-pos|loans|orders|cash)\b/.test(parsed.pathname || '')
                ) {
                    return (parsed.pathname || '/') + (parsed.search || '') + (parsed.hash || '');
                }
            } catch (e) {}
            return rawUrl;
        };

        window._zyaTicketBrowser = {
            isTouchMobile: function() {
                var ua = navigator.userAgent || '';
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(ua)) {
                    return true;
                }
                // iPadOS en modo escritorio reporta Macintosh con soporte multitouch
                return /Macintosh/i.test(ua) && (navigator.maxTouchPoints || 0) > 1;
            },
            isSafariDesktop: function() {
                if (this.isTouchMobile()) return false;
                var ua = navigator.userAgent || '';
                return /Safari/i.test(ua) && !/Chrome|Chromium|CriOS|Edg|EdgiOS|OPR|Firefox|FxiOS|Brave/i.test(ua);
            }
        };

        function triggerFramePrint(win) {
            if (!win) return false;
            var modalPlugin = (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal && window.jQuery.fn.modal.Constructor)
                ? window.jQuery.fn.modal.Constructor
                : null;
            var modalProto = modalPlugin && modalPlugin.prototype ? modalPlugin.prototype : null;
            var origEnforceFocus = modalProto && modalProto._enforceFocus ? modalProto._enforceFocus : null;

            // Evita que Bootstrap 4 modal.enforceFocus robe el foco al iframe durante la impresión (Firefox/Safari)
            if (modalProto && origEnforceFocus) {
                modalProto._enforceFocus = function() {};
            }

            try {
                try {
                    if (document.activeElement && document.activeElement.blur) {
                        document.activeElement.blur();
                    }
                } catch (blurErr) {}

                win.focus();

                if (window._zyaTicketBrowser.isSafariDesktop()) {
                    var safariHandled = false;
                    try {
                        safariHandled = !!(win.document && win.document.execCommand && win.document.execCommand('print', false, null));
                    } catch (execErr) {}
                    if (!safariHandled && typeof win.print === 'function') {
                        win.print();
                    }
                } else if (typeof win.print === 'function') {
                    win.print();
                }
                return true;
            } finally {
                if (modalProto && origEnforceFocus) {
                    setTimeout(function() {
                        modalProto._enforceFocus = origEnforceFocus;
                    }, 350);
                }
            }
        }

        function waitTicketLayoutAndPrint(doc, win, onDone) {
            var finish = function() {
                var raf = window.requestAnimationFrame || function(cb) { return setTimeout(cb, 16); };
                raf(function() {
                    raf(function() {
                        var ok = false;
                        try {
                            ok = triggerFramePrint(win);
                        } catch (err) {
                            console.warn('Error al invocar impresión del ticket:', err);
                        }
                        if (typeof onDone === 'function') onDone(ok);
                    });
                });
            };

            try {
                if (doc && doc.fonts && doc.fonts.ready && typeof doc.fonts.ready.then === 'function') {
                    var raced = false;
                    var fontTimer = setTimeout(function() {
                        if (!raced) {
                            raced = true;
                            finish();
                        }
                    }, 250);
                    doc.fonts.ready.then(function() {
                        if (!raced) {
                            raced = true;
                            clearTimeout(fontTimer);
                            finish();
                        }
                    }, function() {
                        if (!raced) {
                            raced = true;
                            clearTimeout(fontTimer);
                            finish();
                        }
                    });
                    return;
                }
            } catch (e) {}

            finish();
        }

        function appendTicketParam(url, key, val) {
            if (!url) return '';
            var hashIdx = url.indexOf('#');
            var hash = hashIdx >= 0 ? url.slice(hashIdx) : '';
            var clean = hashIdx >= 0 ? url.slice(0, hashIdx) : url;
            var qIdx = clean.indexOf('?');
            var base = qIdx >= 0 ? clean.slice(0, qIdx) : clean;
            var query = qIdx >= 0 ? clean.slice(qIdx + 1) : '';
            try {
                var params = new URLSearchParams(query);
                params.set(key, val);
                return base + '?' + params.toString() + hash;
            } catch (e) {
                var sep = clean.indexOf('?') >= 0 ? '&' : '?';
                return clean + sep + encodeURIComponent(key) + '=' + encodeURIComponent(val) + hash;
            }
        }

        // Impresión directa de tickets compatible con Chrome, Edge, Firefox, Safari (macOS/iOS) y Android
        window._posDirectPrintTimer = window._posDirectPrintTimer || null;
        window._posDirectPrintCleanupTimer = window._posDirectPrintCleanupTimer || null;
        window._posDirectPrintLastUrl = window._posDirectPrintLastUrl || '';
        window._posDirectPrintLastAt = window._posDirectPrintLastAt || 0;

        window.printTicketDirectly = function(url, options) {
            if (!url) return;
            options = options || {};
            url = window.normalizeTicketUrl(url);
            if (!/[?&]paper=/.test(url)) {
                url = appendTicketParam(url, 'paper', window.zyaTicketStorage.getPaperMode());
            }

            var isUserClick = typeof options.userInitiated === 'boolean'
                ? options.userInitiated
                : Boolean(typeof window.event !== 'undefined' && window.event && /click|pointerup|touchend/i.test(window.event.type || ''));

            // En iOS/iPadOS/Android, los iframes ocultos no aíslan el área de impresión del documento padre.
            // Si proviene de clic directo, abrir ventana limpia para disparo nativo; si es automático, mostrar vista previa.
            if (window._zyaTicketBrowser.isTouchMobile()) {
                if (isUserClick) {
                    var popup = window.open(url, '_blank');
                    if (popup && !popup.closed) {
                        return;
                    }
                }
                if (!options.skipModalFallback && typeof window.openTicketPreviewModal === 'function') {
                    var modalOpen = window.jQuery && $('#globalTicketPreviewModal').hasClass('show');
                    if (!modalOpen) {
                        window.openTicketPreviewModal(url, options.title || 'Ticket listo para imprimir', { popupBlocked: true });
                    }
                }
                return;
            }

            // Bloquear disparos duplicados inmediatos sobre la misma URL (doble click / propagación)
            var now = Date.now();
            if (window._posDirectPrintLastUrl === url && (now - window._posDirectPrintLastAt) < 1000) {
                return;
            }
            window._posDirectPrintLastUrl = url;
            window._posDirectPrintLastAt = now;

            // Cancelar temporizador anterior e invalidar iframe previo para evitar múltiples diálogos de impresión
            if (window._posDirectPrintTimer) {
                clearTimeout(window._posDirectPrintTimer);
                window._posDirectPrintTimer = null;
            }
            if (window._posDirectPrintCleanupTimer) {
                clearTimeout(window._posDirectPrintCleanupTimer);
                window._posDirectPrintCleanupTimer = null;
            }

            var oldFrame = document.getElementById('posDirectPrintFrame');
            if (oldFrame) {
                oldFrame.onload = null;
                oldFrame.remove();
            }

            // Mantener el iframe dentro del viewport (evita culling offscreen y recorte vertical en Safari/Firefox)
            var frame = document.createElement('iframe');
            frame.id = 'posDirectPrintFrame';
            frame.name = 'posDirectPrintFrame';
            frame.setAttribute('aria-hidden', 'true');
            frame.setAttribute('tabindex', '-1');
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '210px';
            frame.style.height = '210px';
            frame.style.opacity = '0.001';
            frame.style.pointerEvents = 'none';
            frame.style.border = '0';
            frame.style.zIndex = '-1';

            var hasPrinted = false;
            var cleanupFrame = function() {
                if (window._posDirectPrintCleanupTimer) {
                    clearTimeout(window._posDirectPrintCleanupTimer);
                    window._posDirectPrintCleanupTimer = null;
                }
                if (frame && frame.parentNode) {
                    frame.onload = null;
                    frame.remove();
                }
            };

            frame.onload = function() {
                if (hasPrinted) return;
                try {
                    var loadedHref = '';
                    try {
                        loadedHref = (frame.contentWindow && frame.contentWindow.location)
                            ? String(frame.contentWindow.location.href || '')
                            : '';
                    } catch (locErr) {}
                    if (loadedHref === 'about:blank') {
                        return;
                    }

                    hasPrinted = true;
                    frame.onload = null;

                    if (frame.contentWindow) {
                        if (window._posDirectPrintTimer) {
                            clearTimeout(window._posDirectPrintTimer);
                        }
                        window._posDirectPrintTimer = setTimeout(function() {
                            window._posDirectPrintTimer = null;
                            if (!frame.isConnected || !frame.contentWindow) return;
                            try {
                                frame.contentWindow.addEventListener('afterprint', function() {
                                    window._posDirectPrintLastAt = 0;
                                    setTimeout(cleanupFrame, 1500);
                                }, { once: true });
                            } catch (apErr) {}

                            var doc = null;
                            try {
                                doc = frame.contentDocument || frame.contentWindow.document;
                            } catch (docErr) {}

                            waitTicketLayoutAndPrint(doc, frame.contentWindow, function(ok) {
                                if (!ok) {
                                    window._posDirectPrintLastAt = 0;
                                }
                                window._posDirectPrintCleanupTimer = setTimeout(cleanupFrame, 60000);
                            });
                        }, 150);
                    }
                } catch (e) {
                    window._posDirectPrintLastAt = 0;
                    console.error('Error al imprimir ticket directo:', e);
                }
            };

            // Asignar src ANTES de insertar el iframe en el DOM para evitar onload inicial de about:blank
            frame.src = url;
            document.body.appendChild(frame);
        };

        $(document).on('click', '.btn-direct-print, [data-direct-print]', function(e) {
            e.preventDefault();
            var url = $(this).data('direct-print') || $(this).attr('href');
            if (url && url !== '#') {
                window.printTicketDirectly(url, { userInitiated: true });
            }
        });

        function updateGlobalPreviewModeButtons(mode) {
            var map = {
                '80': 'globalTicketMode80Btn',
                '58': 'globalTicketMode58Btn',
                'letter': 'globalTicketModeLetterBtn'
            };
            Object.keys(map).forEach(function(k) {
                var btn = document.getElementById(map[k]);
                if (!btn) return;
                btn.classList.remove('btn-dark', 'btn-info', 'active');
                if (k === mode) {
                    btn.classList.add(k === 'letter' ? 'btn-info' : 'btn-dark', 'active');
                }
            });
        }

        window.openTicketPreviewModal = function(receiptUrl, titleText, options) {
            if (!receiptUrl) return;
            options = options || {};
            var baseUrl = window.normalizeTicketUrl(receiptUrl).split('?')[0];
            var mode = window.zyaTicketStorage.getPaperMode();
            var frameEl = document.getElementById('globalTicketPreviewIframe');
            var titleEl = document.getElementById('globalTicketPreviewTitle');
            var fullBtn = document.getElementById('globalTicketOpenFullBtn');
            var printBtn = document.getElementById('globalTicketPrintBtn');
            var hintEl = document.getElementById('globalTicketBrowserHint');

            if (titleEl) titleEl.textContent = titleText || 'Vista Previa de Ticket';
            if (hintEl) {
                var needsManualTap = Boolean(options.popupBlocked) || window._zyaTicketBrowser.isTouchMobile();
                hintEl.classList.toggle('d-none', !needsManualTap);
            }

            updateGlobalPreviewModeButtons(mode);

            if (fullBtn) fullBtn.href = baseUrl + '?preview=1&paper=' + mode;
            if (printBtn) {
                printBtn.onclick = function() {
                    var currentMode = window.zyaTicketStorage.getPaperMode();
                    var printUrl = baseUrl + '?skip_drawer=1&paper=' + currentMode;

                    if (window._zyaTicketBrowser.isTouchMobile() || window._zyaTicketBrowser.isSafariDesktop()) {
                        var opened = window.open(printUrl, '_blank');
                        if (opened && !opened.closed) {
                            return;
                        }
                    }

                    // Si el iframe visible del modal ya cargó el ticket actual, imprimirlo directo evita doble petición y bloqueos
                    try {
                        if (
                            frameEl &&
                            frameEl.contentWindow &&
                            frameEl.contentDocument &&
                            frameEl.contentDocument.readyState === 'complete' &&
                            String(frameEl.src || '').indexOf('paper=' + currentMode) !== -1
                        ) {
                            waitTicketLayoutAndPrint(frameEl.contentDocument, frameEl.contentWindow);
                            return;
                        }
                    } catch (e) {}

                    window.printTicketDirectly(printUrl, { userInitiated: true });
                };
            }
            if (frameEl) {
                frameEl.src = baseUrl + '?preview=1&paper=' + mode;
            }
            if (window.jQuery) {
                // focus: false evita que Bootstrap 4 enforceFocus secuestre el foco del iframe de impresión en Firefox/Safari
                $('#globalTicketPreviewModal').modal({ show: true, focus: false });
            }
        };

        window.setGlobalTicketPreviewMode = function(mode) {
            window.zyaTicketStorage.set('zya_ticket_paper_mode', mode);
            window.zyaTicketStorage.set('zya_ticket_width', mode === '58' ? '58' : '80');
            updateGlobalPreviewModeButtons(mode);
            var frameEl = document.getElementById('globalTicketPreviewIframe');
            var fullBtn = document.getElementById('globalTicketOpenFullBtn');
            if (frameEl && frameEl.src) {
                var baseUrl = window.normalizeTicketUrl(frameEl.src).split('?')[0];
                frameEl.src = baseUrl + '?preview=1&paper=' + mode;
                if (fullBtn) fullBtn.href = baseUrl + '?preview=1&paper=' + mode;
            }
        };
    </script>

    <!-- Modal Global de Vista Previa de Tickets (WhatsApp / Impresión XP o Epson) -->
    <div class="modal fade" id="globalTicketPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title font-weight-bold text-white mb-0">
                        <i class="fa-solid fa-receipt mr-2"></i><span id="globalTicketPreviewTitle">Vista Previa de Ticket</span>
                    </h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-2 bg-light">
                    <div id="globalTicketBrowserHint" class="alert alert-info py-1 px-2 mb-2 small d-none">
                        <i class="fa-solid fa-mobile-screen-button mr-1"></i>
                        En dispositivos móviles o Safari, toca <strong>Imprimir</strong> para abrir el ticket en pantalla completa e iniciar la impresión del sistema.
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 px-1">
                        <div class="btn-group btn-group-sm mb-1" role="group">
                            <button type="button" id="globalTicketMode80Btn" class="btn btn-outline-secondary font-weight-bold" onclick="window.setGlobalTicketPreviewMode('80')">XP 80mm</button>
                            <button type="button" id="globalTicketMode58Btn" class="btn btn-outline-secondary font-weight-bold" onclick="window.setGlobalTicketPreviewMode('58')">XP 58mm</button>
                            <button type="button" id="globalTicketModeLetterBtn" class="btn btn-outline-info font-weight-bold" onclick="window.setGlobalTicketPreviewMode('letter')">Epson Carta</button>
                        </div>
                        <div class="mb-1">
                            <button type="button" id="globalTicketPrintBtn" class="btn btn-sm btn-primary font-weight-bold mr-1">
                                <i class="fa-solid fa-print mr-1"></i>Imprimir
                            </button>
                            <a href="#" id="globalTicketOpenFullBtn" target="_blank" rel="noopener" class="btn btn-sm btn-success font-weight-bold">
                                <i class="fa-brands fa-whatsapp mr-1"></i>Captura / Pantalla Completa
                            </a>
                        </div>
                    </div>
                    <iframe id="globalTicketPreviewIframe" src="about:blank" style="width:100%; height:520px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;"></iframe>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
