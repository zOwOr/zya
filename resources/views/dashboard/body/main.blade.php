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
</body>

</html>
