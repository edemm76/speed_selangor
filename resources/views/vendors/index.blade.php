@extends('layouts.app')

@section('title', 'Senarai Pemohon')

@php
    $currentStatus = $filters['status'] ?? null;
    // Keep the search words / type when switching status tabs
    $tabUrl = fn ($status) => route('admin.vendors.index', array_filter([
        'q' => $filters['q'] ?? null,
        'type' => $filters['type'] ?? null,
        'status' => $status,
    ]));
@endphp

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Senarai Pemohon</h1>
            <p class="text-muted mb-0">Semua akaun pemohon (individu dan syarikat) yang berdaftar dalam sistem.</p>
        </div>
    </div>

    {{-- Status tabs with counts --}}
    <ul class="nav nav-pills ss-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $currentStatus ? '' : 'active' }}" href="{{ $tabUrl(null) }}">
                Semua <span class="badge">{{ $totalVendors }}</span>
            </a>
        </li>
        @foreach (\App\Enums\VendorStatus::cases() as $status)
            {{-- Hide "Menunggu Pengesahan" when nobody is pending (new accounts are active straight away) --}}
            @if ($status !== \App\Enums\VendorStatus::Pending || ($counts[$status->value] ?? 0) > 0)
                <li class="nav-item">
                    <a class="nav-link {{ $currentStatus === $status->value ? 'active' : '' }}" href="{{ $tabUrl($status->value) }}">
                        {{ $status->label() }} <span class="badge">{{ $counts[$status->value] ?? 0 }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>

    {{-- Search and filter --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vendors.index') }}" class="row g-2 align-items-end">
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="col-md-6">
                    <label for="q" class="form-label small">Carian</label>
                    <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                           placeholder="Nama, syarikat, e-mel, No. KP, No. SSM atau telefon">
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label small">Jenis Pemohon</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Semua jenis</option>
                        @foreach (\App\Enums\ApplicantType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-grow-1"><i class="bi bi-search"></i> Cari</button>
                    @if (! empty($filters['q']) || ! empty($filters['type']))
                        <a href="{{ route('admin.vendors.index', array_filter(['status' => $currentStatus])) }}"
                           class="btn btn-outline-navy" title="Kosongkan carian"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Pemohon</th>
                        <th>Jenis</th>
                        <th>No. KP / SSM</th>
                        <th>Hubungi</th>
                        <th class="text-center">Permohonan</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('admin.vendors.show', $vendor) }}" class="fw-semibold text-decoration-none" style="color: var(--ss-navy);">
                                    {{ $vendor->displayName() }}
                                </a>
                                @if ($vendor->isCompany())
                                    <div class="small text-muted">{{ $vendor->pic_name }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <i class="bi {{ $vendor->applicant_type->icon() }}"></i>
                                {{ $vendor->applicant_type->label() }}
                            </td>
                            <td class="text-nowrap">{{ $vendor->identityNo() }}</td>
                            <td class="small">
                                {{ $vendor->email }}<br>
                                <span class="text-muted">{{ $vendor->no_phone }}</span>
                            </td>
                            <td class="text-center">{{ $vendor->applications_count }}</td>
                            <td>{!! $vendor->status->badge() !!}</td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.vendors.show', $vendor) }}" class="btn btn-outline-navy btn-sm">
                                    Lihat <i class="bi bi-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-2 d-block mb-2"></i>
                                Tiada pemohon dijumpai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($vendors->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Memaparkan {{ $vendors->firstItem() }}–{{ $vendors->lastItem() }} daripada {{ $vendors->total() }} pemohon
            </span>
            {{ $vendors->links() }}
        </div>
    @endif
@endsection