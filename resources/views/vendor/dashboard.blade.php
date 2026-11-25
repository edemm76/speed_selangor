@extends('layouts.app')

@section('title', 'Papan Pemuka')

@php
    $target = config('speed.target_days');
    $cards = [
        ['all', 'Jumlah Permohonan', $total, 'dark', 'bi-folder2-open'],
        ['in_progress', $groups['in_progress'][0], $counts['in_progress'], 'info', 'bi-hourglass-split'],
        ['action_required', $groups['action_required'][0], $counts['action_required'], 'danger', 'bi-exclamation-triangle'],
        ['approved', $groups['approved'][0], $counts['approved'], 'success', 'bi-award'],
        ['rejected', $groups['rejected'][0], $counts['rejected'], 'dark', 'bi-x-circle'],
    ];
@endphp

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Papan Pemuka</h1>
            <p class="text-muted mb-0">
                Selamat datang, {{ $vendor->displayName() }}
                @if ($vendor->isCompany()) &middot; {{ $vendor->pic_name }} @endif
                &middot; {{ $vendor->applicant_type->label() }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.notifications.index') }}" class="btn btn-outline-navy position-relative">
                <i class="bi bi-bell"></i> Notifikasi
                @if ($unread)
                    <span class="badge rounded-pill bg-danger">{{ $unread }}</span>
                @endif
            </a>
            <a href="{{ route('vendor.applications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Permohonan Baharu
            </a>
        </div>
    </div>

    {{-- Applications waiting for the applicant --}}
    @foreach ($actionNeeded as $app)
        <div class="alert alert-danger d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill"></i> {{ $app->reference_no }} memerlukan tindakan anda</div>
                <div class="small">{{ \Illuminate\Support\Str::limit($app->verifications->firstWhere('decision', \App\Enums\ApplicationStatus::ActionRequired)?->notes ?? 'Pegawai memerlukan maklumat tambahan.', 160) }}</div>
            </div>
            <a href="{{ route('vendor.applications.resubmit', $app) }}" class="btn btn-danger btn-sm text-nowrap">
                <i class="bi bi-pencil-square"></i> Kemas Kini &amp; Hantar Semula
            </a>
        </div>
    @endforeach

    {{-- Status cards: each opens the matching list --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @foreach ($cards as [$key, $label, $value, $color, $icon])
            <div class="col">
                <a href="{{ $key === 'all' ? route('vendor.applications.index') : route('vendor.applications.index', ['status' => $key]) }}"
                   class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-muted small">
                            {{ $label }} <i class="bi {{ $icon }} text-{{ $color }}"></i>
                        </div>
                        <div class="stat-value {{ in_array($key, ['action_required', 'approved']) && $value ? 'text-' . $color : '' }}">{{ $value }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- Latest applications --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Permohonan Terkini</span>
                    <a href="{{ route('vendor.applications.index') }}" class="small fw-normal">Lihat semua <i class="bi bi-chevron-right"></i></a>
                </div>
                @if ($latest->isEmpty())
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-folder2-open display-6"></i>
                        <p class="mt-2 mb-3">Anda belum menghantar sebarang permohonan.</p>
                        <a href="{{ route('vendor.applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Buat Permohonan</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Rujukan</th>
                                    <th>Tajuk Projek</th>
                                    <th>Status</th>
                                    <th>Tempoh</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latest as $app)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $app->reference_no }}</td>
                                        <td>
                                            {{ $app->project_title }}
                                            <div class="small text-muted">{{ $app->permit_type->label() }}</div>
                                        </td>
                                        <td>{!! $app->current_status->badge() !!}</td>
                                        <td class="small text-nowrap">
                                            @if ($app->current_status->isFinal())
                                                <span class="text-muted">Selesai &middot; {{ $app->daysElapsed() }} hari</span>
                                            @elseif ($app->isOverdue())
                                                <span class="text-danger fw-semibold">{{ $app->daysElapsed() }} hari</span>
                                            @else
                                                {{ $app->daysElapsed() }} / {{ $target }} hari
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('vendor.applications.show', $app) }}" class="btn btn-sm btn-outline-navy">Lihat</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Notification preview --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Notifikasi Terkini</span>
                    <a href="{{ route('vendor.notifications.index') }}" class="small fw-normal">Lihat semua <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($notifications as $item)
                        @php [$icon, $color] = $item->notification->icon(); @endphp
                        <a href="{{ route('vendor.notifications.show', $item) }}"
                           class="list-group-item list-group-item-action d-flex gap-2 py-2 {{ $item->isRead() ? '' : 'bg-light' }}">
                            <i class="bi {{ $icon }} text-{{ $color }} fs-5 flex-shrink-0"></i>
                            <span style="min-width: 0">
                                <span class="d-block small {{ $item->isRead() ? 'fw-semibold' : 'fw-bold' }}">
                                    {{ $item->notification->title }}
                                    @unless ($item->isRead()) <span class="badge bg-danger">Baharu</span> @endunless
                                </span>
                                <span class="d-block small text-muted">
                                    {{ $item->application?->reference_no }} &middot; {{ $item->created_at->locale('ms')->diffForHumans() }}
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="text-center text-muted small py-4"><i class="bi bi-bell-slash fs-4 d-block mb-1"></i> Tiada notifikasi.</div>
                    @endforelse
                </div>
            </div>

            {{-- Drafts not sent yet --}}
            @if ($drafts->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header">Draf Belum Dihantar</div>
                    <div class="list-group list-group-flush">
                        @foreach ($drafts as $draft)
                            <a href="{{ route('vendor.applications.edit', $draft) }}" class="list-group-item list-group-item-action py-2">
                                <span class="d-block small fw-semibold">{{ $draft->project_title }}</span>
                                <span class="d-block small text-muted">Dikemas kini {{ $draft->updated_at->format('d/m/Y') }} &middot; Sambung <i class="bi bi-chevron-right"></i></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Short guide --}}
            <div class="card">
                <div class="card-header">Panduan Ringkas</div>
                <div class="card-body small">
                    <ol class="ps-3 mb-0">
                        <li>Klik <strong>Permohonan Baharu</strong> dan pilih jenis permit.</li>
                        <li>Isi maklumat projek dan muat naik semua dokumen wajib (PDF/JPG/PNG, maks. {{ config('speed.upload.max_kb') / 1024 }} MB).</li>
                        <li>Hantar permohonan dan simpan nombor rujukan.</li>
                        <li>Pantau status dan notifikasi. Sasaran pemprosesan ialah <strong>{{ $target }} hari</strong>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
