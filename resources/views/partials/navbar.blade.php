<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <i class="bi bi-lightning-charge-fill text-warning"></i> {{ config('speed.name') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Tukar navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            {{-- Left menu: filled in later for vendor / admin (Step 3) --}}
            <ul class="navbar-nav me-auto">
                @yield('nav-left')
            </ul>

            {{-- Right menu: guest links for now. Step 3 adds the logged-in user + Log Keluar --}}
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#">Log Masuk</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Daftar</a></li>
            </ul>
        </div>
    </div>
</nav>