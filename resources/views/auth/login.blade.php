@extends('layouts.app')

@section('title', 'Log Masuk')

@php
    // Pre-select the role from ?role=admin (e.g. after resetting an officer password)
    $role = old('role', request('role') === 'admin' ? 'admin' : 'vendor');
@endphp

@section('hero')
    <section class="ss-hero">
        <div class="container">
            <div class="text-center mb-4">
                <h1 class="ss-hero-title mb-2">Selamat Datang ke Speed Selangor</h1>
                <p class="ss-hero-sub mb-1">Sistem permohonan dan pemantauan kelulusan pembangunan secara dalam talian</p>
                <p class="ss-hero-tagline mb-0">&ldquo;Kelulusan lebih pantas, telus dan cekap&rdquo;</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">
                    @include('partials.flash')

                    <div class="card ss-login-card">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="h4 mb-1">Log Masuk</h2>
                            <p class="text-muted small mb-4">Sila pilih jenis pengguna dan masukkan maklumat akaun anda.</p>

                            <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                                @csrf

                                {{-- Who is logging in --}}
                                <div class="btn-group w-100 mb-4" role="group" aria-label="Jenis pengguna">
                                    <input type="radio" class="btn-check" name="role" id="role-vendor" value="vendor"
                                           autocomplete="off" @checked($role === 'vendor')>
                                    <label class="btn btn-outline-navy" for="role-vendor">
                                        <i class="bi bi-person"></i> Pemohon
                                    </label>

                                    <input type="radio" class="btn-check" name="role" id="role-admin" value="admin"
                                           autocomplete="off" @checked($role === 'admin')>
                                    <label class="btn btn-outline-navy" for="role-admin">
                                        <i class="bi bi-person-badge"></i> Pegawai PBT
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mel</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="nama@contoh.com" required autofocus>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Kata Laluan</label>
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror" required>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                               value="1" @checked(old('remember'))>
                                        <label class="form-check-label" for="remember">Ingat saya</label>
                                    </div>
                                    <a href="{{ route('password.request') }}" class="small" id="forgot-link">Lupa kata laluan?</a>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    Log Masuk <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>

                            <p class="text-center small mt-4 mb-0">
                                Belum mempunyai akaun?
                                <a href="{{ route('register') }}" class="fw-semibold">Daftar sebagai pemohon</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navy highlight bar, like the shortcut bar on the MPKS portal --}}
        <div class="container mt-auto">
            <div class="ss-feature-bar mx-auto" style="max-width: 1100px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <div class="ss-feature"><i class="bi bi-file-earmark-arrow-up"></i> Permohonan Dalam Talian</div>
                    </div>
                    <div class="col-md-4">
                        <div class="ss-feature"><i class="bi bi-geo-alt"></i> Jejak Status Masa Nyata</div>
                    </div>
                    <div class="col-md-4">
                        <div class="ss-feature"><i class="bi bi-stopwatch"></i> Sasaran {{ config('speed.target_days') }} Hari</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // "Lupa kata laluan?" opens the forgot page for the role currently selected
        (function () {
            const link = document.getElementById('forgot-link');
            const base = link.getAttribute('href');
            function update() {
                const admin = document.getElementById('role-admin').checked;
                link.setAttribute('href', admin ? base + '?role=admin' : base);
            }
            document.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', update));
            update();
        })();
    </script>
@endpush