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
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}"
                               href="{{ route('admin.vendors.index') }}">Pemohon</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#">Notifikasi</a></li>
                    </ul>

                    @include('partials.user-menu', [
                        'name' => $admin->name,
                        'subtitle' => 'Pegawai PBT',
                        'icon' => 'bi-person-badge',
                        'profileUrl' => route('admin.profile.edit'),
                        'profileActive' => request()->routeIs('admin.profile.*'),
                    ])

                {{-- ===================== VENDOR / PEMOHON ===================== --}}
                @elseif ($vendor)
                    <ul class="navbar-nav ss-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}"
                               href="{{ route('vendor.dashboard') }}">Papan Pemuka</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.applications.*') ? 'active' : '' }}"
                               href="{{ route('vendor.applications.index') }}">Permohonan Saya</a>
                        </li>
                        {{-- Link below is filled in by a later step --}}
                        <li class="nav-item"><a class="nav-link" href="#">Notifikasi</a></li>
                    </ul>

                    @include('partials.user-menu', [
                        'name' => $vendor->displayName(),
                        'subtitle' => 'Pemohon · ' . $vendor->applicant_type->label(),
                        'icon' => 'bi-person-circle',
                        'profileUrl' => route('vendor.profile.edit'),
                        'profileActive' => request()->routeIs('vendor.profile.*'),
                    ])

                {{-- ===================== GUEST ===================== --}}
                @else
                    <ul class="navbar-nav ss-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                               href="{{ route('login') }}">Log Masuk</a>
                        </li>
                        <li class="nav-item mt-2 mt-lg-0">
                            <a class="btn btn-primary" href="{{ route('register') }}">Daftar Akaun</a>
                        </li>
                    </ul>
                @endif

            </div>
        </div>
    </nav>
</header>