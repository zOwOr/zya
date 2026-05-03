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
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<style>
  .iq-sidebar {
    max-height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
  }
  @media (max-width: 1299px) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        $(document).ready(function() {
            if ($('.datatable-export').length > 0) {
                $('.datatable-export').each(function() {
                    let table = $(this);
                    
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
