<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') — PPMF Portal</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- PPMF Custom CSS -->
  <link rel="stylesheet" href="{{ asset('css/ppmf-style.css') }}">

  @stack('styles')
</head>
<body>

  <!-- Mobile overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  @include('partials.sidebar')

  <!-- Main wrapper -->
  <div class="ppmf-main" id="ppmfMain">

    <!-- Header -->
    @include('partials.header')

    <!-- Page content -->
    <main class="ppmf-content">
      @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

  </div><!-- /ppmf-main -->

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <!-- PPMF Dashboard JS -->
  <script src="{{ asset('js/ppmf-dashboard.js') }}"></script>

  @stack('scripts')



</body>
</html>
</body>
</html>
