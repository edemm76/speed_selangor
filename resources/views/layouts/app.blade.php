<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Utama') | {{ config('speed.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background-color: #f5f6f8; }
        .navbar-brand { font-weight: 600; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 600; }
        .stat-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

    @include('partials.navbar')

    <main class="flex-grow-1 py-4">
        <div class="container">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>

    <footer class="py-3 bg-white border-top">
        <div class="container small text-muted d-flex flex-column flex-md-row justify-content-between gap-1">
            <span>&copy; {{ date('Y') }} {{ config('speed.full_name') }}</span>
            <span>{{ config('speed.council') }}</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>