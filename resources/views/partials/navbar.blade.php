@php
    $admin = auth('admin')->user();
    $vendor = auth('vendor')->user();

    $homeUrl = $admin ? route('admin.dashboard') : ($vendor ? route('vendor.dashboard') : route('login'));
@endphp

<header class="ss-header sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">

            {{-- Logo + system name. To use a real logo, put it at public/images/logo.png --}}
            <a class="ss-brand me-4" href="{{ $homeUrl }}">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="ss-logo-img">
                @else
                    <span class="ss-logo"><i class="bi bi-lightning-charge-fill"></i></span>
                @endif
                <span>
                    <span class="ss-brand-name">{{ config('speed.name') }}</span>
                    <span class="ss-brand-sub">{{ config('speed.council') }}</span>
                </span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Tukar navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">

                {{-- ===================== ADMINISTRATOR ===================== --}}
                @if ($admin)
                    <ul class="navbar-nav ss-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                               href="{{ route('admin.dashboard') }}">Papan Pemuka</a>
                        </li>
                        {{-- Links below are filled in by later steps --}}
                        <li class="nav-item"><a class="nav-link" href="#">Permohonan</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Semakan</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Pemohon</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Notifikasi</a></li>
                    </ul>

                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 mt-3 mt-lg-0">
                        <span class="ss-user">
                            <i class="bi bi-person-badge fs-5"></i>
                            <span>{{ $admin->name }}<br><small>Pegawai PBT</small></span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-navy btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Log Keluar
                            </button>
                        </form>
                    </div>

                {{-- ===================== VENDOR / PEMOHON ===================== --}}
                @elseif ($vendor)
                    <ul class="navbar-nav ss-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}"
                               href="{{ route('vendor.dashboard') }}">Papan Pemuka</a>
                        </li>
                        {{-- Links below are filled in by later steps --}}
                        <li class="nav-item"><a class="nav-link" href="#">Permohonan Saya</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Notifikasi</a></li>
                    </ul>

                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 mt-3 mt-lg-0">
                        <span class="ss-user">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span>{{ $vendor->displayName() }}<br><small>Pemohon &middot; {{ $vendor->applicant_type->label() }}</small></span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-navy btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Log Keluar
                            </button>
                        </form>
                    </div>

                {{-- ===================== GUEST ===================== --}}
                @else
                    <ul class="navbar-nav ss-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                               href="{{ route('login') }}">Log Masuk</a>
                        </li>
                        <li class="nav-item mt-2 mt-lg-0">
                            {{-- Register page comes in Step 4 --}}
                            <a class="btn btn-primary" href="{{ route('register') }}">Daftar Akaun</a>
                        </li>
                    </ul>
                @endif

            </div>
        </div>
    </nav>
</header>