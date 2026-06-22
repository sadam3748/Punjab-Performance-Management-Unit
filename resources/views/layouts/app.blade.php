<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | PPMU - Punjab Performance Management Unit</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- PPMF Global Theme CSS --}}
    <link rel="stylesheet" href="{{ asset('css/ppmf-style.css') }}?v={{ filemtime(public_path('css/ppmf-style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ppmf-sidebar-collapse.css') }}?v={{ filemtime(public_path('css/ppmf-sidebar-collapse.css')) }}">

    {{-- Sidebar state (desktop): collapsed by default, pin via localStorage --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('ppmuSidebarExpandedV3');
                var pinned = saved === '1';
                var root = document.documentElement;
                if (pinned) {
                    root.classList.add('ppmf-sidebar-pinned');
                    root.classList.remove('ppmf-sidebar-collapsed');
                } else {
                    root.classList.add('ppmf-sidebar-collapsed');
                    root.classList.remove('ppmf-sidebar-pinned');
                }
            } catch (e) {
                document.documentElement.classList.add('ppmf-sidebar-collapsed');
            }
        })();
    </script>

    {{-- Page Specific CSS --}}
    @stack('styles')
</head>

<body>

    {{-- Mobile Sidebar Overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    @include('partials.sidebar')

    {{-- Main Wrapper --}}
    <div class="ppmf-main" id="ppmfMain">

        {{-- Header --}}
        @include('partials.header')

        {{-- Page Content --}}
        <main class="ppmf-content @yield('content_class')">
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('partials.footer')

    </div>

    {{-- Bootstrap 5 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    {{-- PPMF Global Dashboard JS --}}
    <script src="{{ asset('js/ppmf-dashboard.js') }}?v={{ filemtime(public_path('js/ppmf-dashboard.js')) }}"></script>
    <script src="{{ asset('js/ppmf-sidebar-collapse.js') }}?v={{ filemtime(public_path('js/ppmf-sidebar-collapse.js')) }}"></script>

    {{-- Page Specific JS --}}
    @stack('scripts')

</body>
</html>
