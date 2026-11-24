@extends('layouts.app')

@section('title', $vendor->displayName())

@section('content')
    <a href="{{ route('admin.vendors.index') }}" class="small fw-semibold d-inline-block mb-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Senarai Pemohon
    </a>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="ss-logo" style="width: 60px; height: 60px; font-size: 1.7rem;">
                <i class="bi {{ $vendor->applicant_type->icon() }}"></i>
            </span>
            <div>
                <h1 class="h3 mb-1">{{ $vendor->displayName() }}</h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-secondary">{{ $vendor->applicant_type->label() }}</span>
                    {!! $vendor->status->badge() !!}
                </div>
            </div>
        </div>

        {{-- Suspend / reactivate (confirmed in a pop-up first) --}}
        <div>
            @if ($vendor->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-suspend">
                    <i class="bi bi-slash-circle"></i> Gantung Akaun
                </button>
            @else
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-activate">
                    <i class="bi bi-check-circle"></i>
                    {{ $vendor->status === \App\Enums\VendorStatus::Pending ? 'Aktifkan Akaun' : 'Aktifkan Semula' }}
                </button>
            @endif
        </div>
    </div>

    @if ($vendor->status === \App\Enums\VendorStatus::Suspended)
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-octagon"></i>
            Akaun ini sedang <strong>digantung</strong>. Pemohon tidak boleh log masuk sehingga akaun diaktifkan semula.
        </div>
    @endif

    <div class="row g-4">
        {{-- Details --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header py-3 px-4"><i class="bi bi-person-vcard"></i> Maklumat Pemohon</div>
                <div class="card-body p-4">
                    <dl class="mb-0 small">
                        @if ($vendor->isCompany())
                            <dt class="text-muted fw-normal">Nama Syarikat</dt>
                            <dd class="fw-semibold">{{ $vendor->company_name }}</dd>
                            <dt class="text-muted fw-normal">No. Pendaftaran Syarikat (SSM)</dt>
                            <dd class="fw-semibold">{{ $vendor->registration_no }}</dd>
                            <dt class="text-muted fw-normal">Pegawai Untuk Dihubungi</dt>
                            <dd class="fw-semibold">{{ $vendor->pic_name }}</dd>
                        @else
                            <dt class="text-muted fw-normal">Nama Penuh</dt>
                            <dd class="fw-semibold">{{ $vendor->pic_name }}</dd>
                            <dt class="text-muted fw-normal">No. Kad Pengenalan</dt>
                            <dd class="fw-semibold">{{ $vendor->ic_no }}</dd>
                        @endif

                        <dt class="text-muted fw-normal">E-mel</dt>
                        <dd class="fw-semibold"><a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a></dd>
                        <dt class="text-muted fw-normal">No. Telefon</dt>
                        <dd class="fw-semibold">{{ $vendor->no_phone ?: '-' }}</dd>
                        <dt class="text-muted fw-normal">Alamat</dt>
                        <dd class="fw-semibold">{{ $vendor->address ?: '-' }}</dd>
                        <dt class="text-muted fw-normal">Tarikh Daftar</dt>
                        <dd class="fw-semibold mb-0">{{ $vendor->created_at?->format('d/m/Y, g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Applications by this applicant --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header py-3 px-4 d-flex justify-content-between">
                    <span><i class="bi bi-folder2-open"></i> Permohonan</span>
                    <span class="text-muted fw-normal small">{{ $vendor->applications->count() }} permohonan</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">No. Rujukan</th>
                                <th>Projek</th>
                                <th>Jenis Permit</th>
                                <th>Dihantar</th>
                                <th class="pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendor->applications as $application)
                                <tr>
                                    <td class="ps-4 text-nowrap fw-semibold">
                                        @if ($application->reference_no)
                                            <a href="{{ route('admin.applications.show', $application) }}" class="text-decoration-none">{{ $application->reference_no }}</a>
                                        @else
                                            <span class="text-muted">— (draf)</span>
                                        @endif
                                    </td>
                                    <td>{{ $application->project_title }}</td>
                                    <td class="small">{{ $application->permit_type->label() }}</td>
                                    <td class="small text-nowrap">
                                        {{ $application->submitted_at?->format('d/m/Y') ?? 'Belum dihantar' }}
                                        @if ($application->isOverdue())
                                            <br><span class="text-danger fw-semibold">Melebihi {{ config('speed.target_days') }} hari</span>
                                        @endif
                                    </td>
                                    <td class="pe-4">{!! $application->current_status->badge() !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">Pemohon ini belum membuat sebarang permohonan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Confirmation pop-ups ===== --}}
    <div class="modal fade" id="modal-suspend" tabindex="-1" aria-labelledby="modal-suspend-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-suspend-title">Gantung akaun ini?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <strong>{{ $vendor->displayName() }}</strong> tidak akan dapat log masuk.
                        Jika pemohon sedang log masuk, mereka akan dilog keluar secara automatik.
                        Permohonan sedia ada tidak akan dipadam.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-navy" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-slash-circle"></i> Ya, Gantung</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-activate" tabindex="-1" aria-labelledby="modal-activate-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.vendors.activate', $vendor) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-activate-title">Aktifkan akaun ini?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <strong>{{ $vendor->displayName() }}</strong> akan dapat log masuk dan membuat permohonan semula.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-navy" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Ya, Aktifkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection