<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Utama') | {{ config('speed.name') }}</title>

    {{-- Font (similar style to the MPKS portal) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Speed Selangor theme (public/css/speed.css) --}}
    <link href="{{ asset('css/speed.css') }}?v={{ filemtime(public_path('css/speed.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

    @include('partials.navbar')

    {{-- Optional full-width banner (used by the login page) --}}
    @hasSection('hero')
        @yield('hero')
    @endif

    @hasSection('content')
        <main class="flex-grow-1 py-4">
            <div class="container">
                {{-- Pages with a hero show their own messages inside the hero --}}
                @unless (View::hasSection('hero'))
                    @include('partials.flash')
                @endunless

                @yield('content')
            </div>
        </main>
    @endif

    <footer class="ss-footer py-4">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>
                <strong>{{ config('speed.full_name') }}</strong><br>
                {{ config('speed.council') }}
            </div>
            <div class="text-md-end">
                &copy; {{ date('Y') }} {{ config('speed.name') }}<br>
                <span class="small">Prototaip sistem untuk kegunaan Pihak Berkuasa Tempatan</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>