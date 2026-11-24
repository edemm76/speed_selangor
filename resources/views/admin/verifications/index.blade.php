@extends('layouts.app')

@section('title', 'Semakan Permohonan')

@php
    $target = config('speed.target_days');
    $tabs = [
        'pending' => ['Perlu Disemak', 'bi-inbox'],
        'new' => ['Baharu', 'bi-stars'],
        'resubmitted' => ['Dihantar Semula', 'bi-arrow-repeat'],
        'waiting_vendor' => ['Menunggu Pemohon', 'bi-hourglass-split'],
        'verified' => ['Menunggu Keputusan', 'bi-patch-check'],
    ];
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-0">Semakan Permohonan</h1>
        <p class="text-muted mb-0">
            Permohonan yang menunggu pengesahan. Permohonan paling lama dipaparkan di atas supaya diutamakan.
        </p>
    </div>

    {{-- Queue tabs --}}
    <ul class="nav nav-pills ss-tabs mb-3">
        @foreach ($tabs as $key => [$label, $icon])
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.verifications.index', ['tab' => $key]) }}">
                    <i class="bi {{ $icon }}"></i> {{ $label }} <span class="badge">{{ $counts[$key] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if ($tab === 'waiting_vendor')
        <p class="small text-muted"><i class="bi bi-info-circle"></i> Permohonan ini sedang menunggu pemohon mengemukakan maklumat tambahan. Ia akan kembali ke tab <strong>Dihantar Semula</strong> apabila pemohon menghantar semula.</p>
    @elseif ($tab === 'verified')
        <p class="small text-muted"><i class="bi bi-info-circle"></i> Permohonan ini telah disahkan. Buka permohonan untuk membuat keputusan (Diluluskan / Ditolak).</p>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Rujukan</th>
                        <th>Pemohon</th>
                        <th>Tajuk Projek</th>
                        <th>Jenis Permit</th>
                        <th>Tempoh</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $app)
                        @php
                            $status = $app->current_status;
                            $resubmitted = $app->isResubmitted();
                        @endphp
                        <tr>
                            <td class="ps-4 text-nowrap fw-semibold">
                                <a href="{{ route('admin.applications.show', $app) }}" class="text-decoration-none" style="color: var(--ss-navy);">{{ $app->reference_no }}</a>
                            </td>
                            <td>
                                {{ $app->vendor->displayName() }}
                                <div class="small text-muted">{{ $app->vendor->applicant_type->label() }}</div>
                            </td>
                            <td>{{ $app->project_title }}</td>
                            <td class="small text-nowrap">{{ $app->permit_type->label() }}</td>
                            <td class="small text-nowrap">
                                @if ($app->isOverdue())
                                    <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle"></i> {{ $app->daysElapsed() }} hari</span>
                                @else
                                    {{ $app->daysElapsed() }} / {{ $target }} hari
                                @endif
                            </td>
                            <td>
                                {!! $status->badge() !!}
                                @if ($resubmitted)
                                    <div><span class="badge bg-warning-subtle text-warning-emphasis mt-1"><i class="bi bi-arrow-repeat"></i> Dihantar semula</span></div>
                                @endif
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                @if ($status === \App\Enums\ApplicationStatus::Submitted)
                                    {{-- Start the review: status becomes "Dalam Semakan" --}}
                                    <form method="POST" action="{{ route('admin.verifications.start', $app) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-fill"></i> Mula Semak</button>
                                    </form>
                                @elseif ($status === \App\Enums\ApplicationStatus::UnderReview)
                                    <a href="{{ route('admin.verifications.show', $app) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-clipboard-check"></i> Semak
                                    </a>
                                @else
                                    <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-outline-navy btn-sm">
                                        Lihat <i class="bi bi-chevron-right"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-check2-all fs-2 d-block mb-2"></i>
                                Tiada permohonan dalam senarai ini.
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
