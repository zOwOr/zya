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
    </script>
</body>

</html>
