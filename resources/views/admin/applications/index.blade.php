@extends('layouts.app')

@section('title', 'Senarai Permohonan')

@php
    $currentStatus = $filters['status'] ?? null;
    // Keep the search words / permit type when switching tabs
    $tabUrl = fn ($status) => route('admin.applications.index', array_filter([
        'q' => $filters['q'] ?? null,
        'permit' => $filters['permit'] ?? null,
        'status' => $status,
    ]));
    $target = config('speed.target_days');
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-0">Senarai Permohonan</h1>
        <p class="text-muted mb-0">Semua permohonan yang telah dihantar oleh pemohon. Draf tidak dipaparkan.</p>
    </div>

    {{-- Tabs with counts --}}
    <ul class="nav nav-pills ss-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $currentStatus ? '' : 'active' }}" href="{{ $tabUrl(null) }}">
                Semua <span class="badge">{{ $total }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentStatus === 'in_progress' ? 'active' : '' }}" href="{{ $tabUrl('in_progress') }}">
                Sedang Diproses <span class="badge">{{ $inProgressCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentStatus === 'overdue' ? 'active' : '' }}" href="{{ $tabUrl('overdue') }}">
                <i class="bi bi-exclamation-circle text-danger"></i> Melebihi {{ $target }} Hari
                <span class="badge">{{ $overdueCount }}</span>
            </a>
        </li>
        @foreach ($statuses as $status)
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus === $status->value ? 'active' : '' }}" href="{{ $tabUrl($status->value) }}">
                    {{ $status->label() }} <span class="badge">{{ $counts[$status->value] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Search and filter --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.applications.index') }}" class="row g-2 align-items-end">
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="col-md-6">
                    <label for="q" class="form-label small">Carian</label>
                    <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                           placeholder="No. rujukan, tajuk projek, nama pemohon, syarikat, No. KP atau SSM">
                </div>
                <div class="col-md-3">
                    <label for="permit" class="form-label small">Jenis Permit</label>
                    <select name="permit" id="permit" class="form-select">
                        <option value="">Semua jenis</option>
                        @foreach (\App\Enums\PermitType::cases() as $permit)
                            <option value="{{ $permit->value }}" @selected(($filters['permit'] ?? '') === $permit->value)>
                                {{ $permit->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-navy flex-grow-1"><i class="bi bi-search"></i> Cari</button>
                    @if (! empty($filters['q']) || ! empty($filters['permit']))
                        <a href="{{ route('admin.applications.index', array_filter(['status' => $currentStatus])) }}"
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
                        <th class="ps-4">No. Rujukan</th>
                        <th>Pemohon</th>
                        <th>Tajuk Projek</th>
                        <th>Jenis Permit</th>
                        <th>Dihantar</th>
                        <th>Tempoh</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $app)
                        <tr>
                            <td class="ps-4 text-nowrap">
                                <a href="{{ route('admin.applications.show', $app) }}" class="fw-semibold text-decoration-none" style="color: var(--ss-navy);">
                                    {{ $app->reference_no }}
                                </a>
                            </td>
                            <td>
                                {{ $app->vendor->displayName() }}
                                <div class="small text-muted">{{ $app->vendor->applicant_type->label() }}</div>
                            </td>
                            <td>{{ $app->project_title }}</td>
                            <td class="small text-nowrap">{{ $app->permit_type->label() }}</td>
                            <td class="small text-nowrap">{{ $app->submitted_at->format('d/m/Y') }}</td>
                            <td class="small text-nowrap">
                                @if ($app->current_status->isFinal())
                                    <span class="text-muted">Selesai &middot; {{ $app->daysElapsed() }} hari</span>
                                @elseif ($app->isOverdue())
                                    <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle"></i> {{ $app->daysElapsed() }} hari</span>
                                @else
                                    {{ $app->daysElapsed() }} / {{ $target }} hari
                                @endif
                            </td>
                            <td>{!! $app->current_status->badge() !!}</td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-outline-navy btn-sm">
                                    Lihat <i class="bi bi-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                                Tiada permohonan dijumpai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($applications->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Memaparkan {{ $applications->firstItem() }}–{{ $applications->lastItem() }} daripada {{ $applications->total() }} permohonan
            </span>
            {{ $applications->links() }}
        </div>
    @endif
@endsection