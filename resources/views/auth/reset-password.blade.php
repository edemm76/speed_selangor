@extends('layouts.app')

@section('title', 'Set Semula Kata Laluan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">

            <div class="text-center mb-4">
                <span class="ss-logo mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
                    <i class="bi bi-shield-lock"></i>
                </span>
                <h1 class="h3 mb-1">Set Semula Kata Laluan</h1>
                <p class="text-muted mb-0">
                    Akaun {{ $role === 'admin' ? 'Pegawai PBT' : 'Pemohon' }}. Sila masukkan kata laluan baharu anda.
                </p>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('password.update') }}" novalidate>
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mel</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $email) }}"
                                   class="form-control @error('email') is-invalid @enderror" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Laluan Baharu</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror" required autofocus>
                            <div class="form-text">Sekurang-kurangnya 8 aksara, dengan huruf dan nombor.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Sahkan Kata Laluan Baharu</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Simpan Kata Laluan <i class="bi bi-check-lg"></i>
                        </button>
                    </form>

                    <p class="text-center small mt-4 mb-0">
                        <a href="{{ route('password.request', ['role' => $role]) }}" class="fw-semibold">
                            Pautan tamat tempoh? Mohon pautan baharu
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection