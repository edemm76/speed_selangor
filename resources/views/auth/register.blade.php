@extends('layouts.app')

@section('title', 'Daftar Akaun')

@php
    $type = old('applicant_type', 'individual');
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">

            <div class="text-center mb-4">
                <h1 class="h2 mb-1">Daftar Akaun Pemohon</h1>
                <p class="text-muted mb-0">Untuk individu atau syarikat yang ingin memohon permit secara dalam talian.</p>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('register.store') }}" novalidate>
                        @csrf

                        {{-- 1. Applicant type --}}
                        <h2 class="h5 mb-3">1. Jenis Pemohon</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="applicant_type" id="type-individual"
                                       value="individual" autocomplete="off" @checked($type === 'individual')>
                                <label class="ss-choice w-100" for="type-individual">
                                    <i class="bi bi-person"></i>
                                    <span>
                                        <strong>Individu</strong>
                                        <small>Pemilik rumah, pemilik tanah atau orang perseorangan</small>
                                    </span>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="applicant_type" id="type-company"
                                       value="company" autocomplete="off" @checked($type === 'company')>
                                <label class="ss-choice w-100" for="type-company">
                                    <i class="bi bi-building"></i>
                                    <span>
                                        <strong>Syarikat</strong>
                                        <small>Pemaju, kontraktor atau firma perunding</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- 2. Applicant details --}}
                        <h2 class="h5 mb-3">2. Maklumat Pemohon</h2>
                        <div class="row g-3 mb-4">

                            {{-- Company only --}}
                            <div class="col-md-7 js-company">
                                <label for="company_name" class="form-label">Nama Syarikat</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                       class="form-control @error('company_name') is-invalid @enderror"
                                       placeholder="Contoh: Binaan Maju Sdn Bhd">
                            </div>
                            <div class="col-md-5 js-company">
                                <label for="registration_no" class="form-label">No. Pendaftaran Syarikat (SSM)</label>
                                <input type="text" name="registration_no" id="registration_no" value="{{ old('registration_no') }}"
                                       class="form-control @error('registration_no') is-invalid @enderror"
                                       placeholder="Contoh: 201901012345">
                            </div>

                            <div class="col-md-7">
                                <label for="pic_name" class="form-label">
                                    <span class="js-individual">Nama Penuh (seperti dalam kad pengenalan)</span>
                                    <span class="js-company">Nama Pegawai Untuk Dihubungi</span>
                                </label>
                                <input type="text" name="pic_name" id="pic_name" value="{{ old('pic_name') }}"
                                       class="form-control @error('pic_name') is-invalid @enderror"
                                       placeholder="Contoh: Ahmad Faizal bin Rahman">
                            </div>

                            {{-- Individual only --}}
                            <div class="col-md-5 js-individual">
                                <label for="ic_no" class="form-label">No. Kad Pengenalan</label>
                                <input type="text" name="ic_no" id="ic_no" value="{{ old('ic_no') }}" inputmode="numeric"
                                       class="form-control @error('ic_no') is-invalid @enderror"
                                       placeholder="900101-10-5678">
                            </div>

                            <div class="col-md-5">
                                <label for="no_phone" class="form-label">No. Telefon</label>
                                <input type="tel" name="no_phone" id="no_phone" value="{{ old('no_phone') }}"
                                       class="form-control @error('no_phone') is-invalid @enderror"
                                       placeholder="012-3456789">
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">
                                    <span class="js-individual">Alamat Surat-Menyurat</span>
                                    <span class="js-company">Alamat Syarikat</span>
                                </label>
                                <textarea name="address" id="address" rows="2"
                                          class="form-control @error('address') is-invalid @enderror"
                                          placeholder="No. rumah / lot, jalan, poskod, bandar">{{ old('address') }}</textarea>
                            </div>
                        </div>

                        {{-- 3. Login details --}}
                        <h2 class="h5 mb-3">3. Maklumat Log Masuk</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="email" class="form-label">E-mel</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="nama@contoh.com">
                                <div class="form-text">E-mel ini digunakan untuk log masuk dan menerima makluman.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Kata Laluan</label>
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror">
                                <div class="form-text">Sekurang-kurangnya 8 aksara, dengan huruf dan nombor.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Sahkan Kata Laluan</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input @error('agree') is-invalid @enderror" type="checkbox"
                                   name="agree" id="agree" value="1" @checked(old('agree'))>
                            <label class="form-check-label" for="agree">
                                Saya mengaku bahawa semua maklumat yang diberikan adalah benar.
                            </label>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                            <span class="small">Sudah mempunyai akaun? <a href="{{ route('login') }}" class="fw-semibold">Log masuk</a></span>
                            <button type="submit" class="btn btn-primary btn-lg">
                                Daftar Akaun <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Show only the fields for the chosen applicant type
        (function () {
            const radios = document.querySelectorAll('input[name="applicant_type"]');

            function apply() {
                const company = document.getElementById('type-company').checked;
                document.querySelectorAll('.js-company').forEach(el => el.classList.toggle('d-none', !company));
                document.querySelectorAll('.js-individual').forEach(el => el.classList.toggle('d-none', company));
            }

            radios.forEach(r => r.addEventListener('change', apply));
            apply();
        })();
    </script>
@endpush