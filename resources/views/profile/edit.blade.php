@extends('layouts.app')

@section('title', 'Profil Saya')

@php
    $isAdmin = $role === 'admin';
    $displayName = $isAdmin ? $user->name : $user->displayName();
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-0">Profil Saya</h1>
        <p class="text-muted mb-0">Lihat dan kemas kini maklumat akaun anda.</p>
    </div>

    <div class="row g-4">

        {{-- ================= Left: account summary (read only) ================= --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <span class="ss-logo mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi {{ $isAdmin ? 'bi-person-badge' : $user->applicant_type->icon() }}"></i>
                        </span>
                        <h2 class="h5 mb-1">{{ $displayName }}</h2>
                        <span class="badge bg-secondary">
                            {{ $isAdmin ? ($user->isSuperAdmin() ? 'Pentadbir Sistem' : 'Pegawai PBT') : 'Pemohon · ' . $user->applicant_type->label() }}
                        </span>
                    </div>

                    <dl class="mb-0 small">
                        @unless ($isAdmin)
                            @if ($user->isCompany())
                                <dt class="text-muted fw-normal">No. Pendaftaran Syarikat (SSM)</dt>
                                <dd class="fw-semibold">{{ $user->registration_no }}</dd>
                            @else
                                <dt class="text-muted fw-normal">Nama Penuh</dt>
                                <dd class="fw-semibold">{{ $user->pic_name }}</dd>
                                <dt class="text-muted fw-normal">No. Kad Pengenalan</dt>
                                <dd class="fw-semibold">{{ $user->ic_no }}</dd>
                            @endif
                        @endunless

                        <dt class="text-muted fw-normal">E-mel</dt>
                        <dd class="fw-semibold">{{ $user->email }}</dd>

                        @unless ($isAdmin)
                            <dt class="text-muted fw-normal">Status Akaun</dt>
                            <dd>{!! $user->status->badge() !!}</dd>
                        @endunless

                        <dt class="text-muted fw-normal">Tarikh Daftar</dt>
                        <dd class="fw-semibold mb-0">{{ $user->created_at?->format('d/m/Y') }}</dd>
                    </dl>

                    <p class="text-muted small mt-4 mb-0">
                        <i class="bi bi-info-circle"></i>
                        E-mel{{ $isAdmin ? '' : ', No. KP / SSM' }} tidak boleh diubah. Sila hubungi pihak PBT jika terdapat kesilapan.
                    </p>
                </div>
            </div>
        </div>

        {{-- ================= Right: forms ================= --}}
        <div class="col-lg-8">

            {{-- Update details [SSMS-REQ-202] --}}
            <div class="card mb-4">
                <div class="card-header py-3 px-4">
                    <i class="bi bi-pencil-square"></i> Kemas Kini Maklumat
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route($role . '.profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            @if ($isAdmin)
                                <div class="col-md-7">
                                    <label for="name" class="form-label">Nama</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                           class="form-control @error('name') is-invalid @enderror">
                                </div>
                            @elseif ($user->isCompany())
                                <div class="col-md-7">
                                    <label for="pic_name" class="form-label">Nama Pegawai Untuk Dihubungi</label>
                                    <input type="text" name="pic_name" id="pic_name" value="{{ old('pic_name', $user->pic_name) }}"
                                           class="form-control @error('pic_name') is-invalid @enderror">
                                </div>
                            @endif

                            <div class="col-md-5">
                                <label for="no_phone" class="form-label">No. Telefon</label>
                                <input type="tel" name="no_phone" id="no_phone" value="{{ old('no_phone', $user->no_phone) }}"
                                       class="form-control @error('no_phone') is-invalid @enderror"
                                       placeholder="012-3456789">
                            </div>

                            @unless ($isAdmin)
                                <div class="col-12">
                                    <label for="address" class="form-label">
                                        {{ $user->isCompany() ? 'Alamat Syarikat' : 'Alamat Surat-Menyurat' }}
                                    </label>
                                    <textarea name="address" id="address" rows="2"
                                              class="form-control @error('address') is-invalid @enderror">{{ old('address', $user->address) }}</textarea>
                                </div>
                            @endunless
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            {{-- [A2: Cancel Update] back to the profile without saving --}}
                            <a href="{{ route($role . '.profile.edit') }}" class="btn btn-outline-navy">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Change password [A1: SSMS-REQ-204] --}}
            <div class="card" id="tukar-kata-laluan">
                <div class="card-header py-3 px-4">
                    <i class="bi bi-shield-lock"></i> Tukar Kata Laluan
                </div>
                <div class="card-body p-4">

                    {{-- [E1: Wrong Current Password] and other password errors --}}
                    @if ($errors->password->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->password->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route($role . '.profile.password') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label">Kata Laluan Semasa</label>
                                <input type="password" name="current_password" id="current_password"
                                       class="form-control @error('current_password', 'password') is-invalid @enderror"
                                       autocomplete="current-password">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Kata Laluan Baharu</label>
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password', 'password') is-invalid @enderror"
                                       autocomplete="new-password">
                                <div class="form-text">Sekurang-kurangnya 8 aksara, dengan huruf dan nombor.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Sahkan Kata Laluan Baharu</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="form-control" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="{{ route($role . '.profile.edit') }}" class="btn btn-outline-navy">Batal</a>
                            <button type="submit" class="btn btn-navy">
                                <i class="bi bi-key"></i> Tukar Kata Laluan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- After a password error (or the "Tukar Kata Laluan" menu link), scroll to the password card --}}
    @if ($errors->password->any())
        <script>
            document.getElementById('tukar-kata-laluan').scrollIntoView({ behavior: 'smooth', block: 'start' });
        </script>
    @endif
@endpush