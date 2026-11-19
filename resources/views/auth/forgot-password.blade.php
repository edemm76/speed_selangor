@extends('layouts.app')

@section('title', 'Lupa Kata Laluan')

@php
    $role = old('role', $role);
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">

            <div class="text-center mb-4">
                <span class="ss-logo mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
                    <i class="bi bi-key"></i>
                </span>
                <h1 class="h3 mb-1">Lupa Kata Laluan</h1>
                <p class="text-muted mb-0">Masukkan e-mel akaun anda. Kami akan menghantar pautan untuk menetapkan semula kata laluan.</p>
            </div>

            {{-- Shown only while developing (MAIL_MAILER=log), because no real e-mail is sent --}}
            @if (session('dev_reset_link'))
                <div class="alert alert-warning">
                    <strong><i class="bi bi-tools"></i> Mod pembangunan:</strong>
                    e-mel disimpan dalam <code>storage/logs/laravel.log</code>. Untuk ujian, buka pautan ini:<br>
                    <a href="{{ session('dev_reset_link') }}" class="fw-semibold">Set semula kata laluan</a>
                </div>
            @endif

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('password.email') }}" novalidate>
                        @csrf

                        <label class="form-label">Jenis akaun</label>
                        <div class="btn-group w-100 mb-4" role="group" aria-label="Jenis akaun">
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

                        <div class="mb-4">
                            <label for="email" class="form-label">E-mel</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="nama@contoh.com" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Hantar Pautan <i class="bi bi-send"></i>
                        </button>
                    </form>

                    <p class="text-center small mt-4 mb-0">
                        <a href="{{ route('login', ['role' => $role]) }}" class="fw-semibold">
                            <i class="bi bi-arrow-left"></i> Kembali ke log masuk
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection