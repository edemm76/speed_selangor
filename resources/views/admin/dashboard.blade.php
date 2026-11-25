@extends('layouts.app')

@section('title', 'Papan Pemuka Pentadbir')

@php
    $target = config('speed.target_days');
    $statusMax = max(1, $statusChart->max('total'));
    $monthMax = max(1, $monthChart->max(fn ($m) => max($m['received'], $m['done'])));
    $permitTotal = max(1, $permitChart->sum('total'));
    $meetingTotal = $meetingChart->sum('total');
@endphp

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Papan Pemuka</h1>
            <p class="text-muted mb-0">
                Selamat datang, {{ $admin->name }} &middot; {{ now()->locale('ms')->translatedFormat('l, j F Y') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-navy">
                <i class="bi bi-bell"></i> Notifikasi
                @if ($unread)
                    <span class="badge rounded-pill bg-danger">{{ $unread }}</span>
                @endif
            </a>
            <a href="{{ route('admin.verifications.index') }}" class="btn btn-primary">
                <i class="bi bi-clipboard-check"></i> Barisan Semakan
                @if ($pending)
                    <span class="badge rounded-pill bg-white text-dark">{{ $pending }}</span>
                @endif
            </a>
        </div>
    </div>

    {{-- Key figures: the first three and the last open the matching list --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xxl-6 g-3 mb-4">
        <div class="col">
            <a href="{{ route('admin.applications.index') }}" class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Jumlah Permohonan <i class="bi bi-folder2-open"></i></div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.applications.index', ['status' => 'in_progress']) }}" class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Sedang Diproses <i class="bi bi-hourglass-split text-info"></i></div>
                    <div class="stat-value">{{ $stats['in_progress'] }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.applications.index', ['status' => 'overdue']) }}" class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Melebihi {{ $target }} Hari <i class="bi bi-alarm text-danger"></i></div>
                    <div class="stat-value {{ $stats['overdue'] ? 'text-danger' : '' }}">{{ $stats['overdue'] }}</div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Purata Tempoh Proses <i class="bi bi-speedometer2 text-primary"></i></div>
                    <div class="stat-value">{{ $stats['avg_days'] ?? '–' }} <span class="fs-6 fw-semibold text-muted">hari</span></div>
                    <div class="small text-muted">{{ $stats['finished'] }} permohonan selesai</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Selesai Dalam {{ $target }} Hari <i class="bi bi-check2-circle text-success"></i></div>
                    <div class="stat-value {{ ($stats['within_target'] ?? 100) < 80 ? 'text-danger' : 'text-success' }}">{{ $stats['within_target'] ?? '–' }}<span class="fs-6 fw-semibold">%</span></div>
                    <div class="small text-muted">Sasaran Speed Selangor</div>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('admin.vendors.index') }}" class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">Jumlah Pemohon <i class="bi bi-people text-warning"></i></div>
                    <div class="stat-value">{{ $stats['vendors'] }}</div>
                    @if ($stats['suspended_vendors'] > 0)
                        <div class="small text-danger">{{ $stats['suspended_vendors'] }} digantung</div>
                    @endif
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Work queue --}}
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Tugasan Anda</span>
                    <a href="{{ route('admin.verifications.index') }}" class="small fw-normal">Barisan Semakan <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($queue as [$tab, $label, $count, $icon, $color])
                        <a href="{{ route('admin.verifications.index', ['tab' => $tab]) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                            <span class="ss-queue-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icon }}"></i></span>
                            <span class="flex-grow-1">{{ $label }}</span>
                            <span class="fs-5 fw-bold {{ $count ? '' : 'text-muted' }}">{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Applications past the target --}}
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-alarm text-danger"></i> Melebihi {{ $target }} Hari</span>
                    <a href="{{ route('admin.applications.index', ['status' => 'overdue']) }}" class="small fw-normal">Lihat semua <i class="bi bi-chevron-right"></i></a>
                </div>
                @if ($overdue->isEmpty())
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-emoji-smile display-6"></i>
                        <p class="mt-2 mb-0">Tiada permohonan melebihi {{ $target }} hari. Syabas!</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Rujukan</th>
                                    <th>Pemohon</th>
                                    <th>Status</th>
                                    <th>Tempoh</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($overdue as $app)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $app->reference_no }}</td>
                                        <td>
                                            {{ $app->vendor->displayName() }}
                                            <div class="small text-muted">{{ $app->permit_type->label() }}</div>
                                        </td>
                                        <td>{!! $app->current_status->badge() !!}</td>
                                        <td class="text-danger fw-semibold text-nowrap small">
                                            {{ $app->daysElapsed() }} hari
                                            <div class="fw-normal">lewat {{ $app->daysElapsed() - $target }} hari</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-sm btn-outline-navy">Buka</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Chart 1: applications by status --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Permohonan Mengikut Status</div>
                <div class="card-body">
                    @foreach ($statusChart as $row)
                        <a href="{{ route('admin.applications.index', ['status' => $row['status']->value]) }}" class="ss-hbar text-decoration-none">
                            <span class="ss-hbar-label">{{ $row['status']->label() }}</span>
                            <span class="ss-hbar-track">
                                <span class="ss-hbar-fill bg-{{ $row['status']->color() }}" style="width: {{ $row['total'] / $statusMax * 100 }}%"></span>
                            </span>
                            <span class="ss-hbar-value">{{ $row['total'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Notification preview --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Notifikasi Terkini</span>
                    <a href="{{ route('admin.notifications.index') }}" class="small fw-normal">Lihat semua <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($notifications as $item)
                        @php [$icon, $color] = $item->notification->icon(); @endphp
                        <a href="{{ route('admin.notifications.show', $item) }}"
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
        </div>
    </div>

    <div class="row g-3">
        {{-- Chart 2: last 6 months --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Permohonan 6 Bulan Terakhir</span>
                    <span class="small fw-normal text-muted">
                        <span class="stat-dot bg-navy align-middle"></span> Diterima
                        <span class="stat-dot bg-success align-middle ms-2"></span> Selesai
                    </span>
                </div>
                <div class="card-body">
                    <div class="ss-vchart" role="img"
                         aria-label="Permohonan diterima dan selesai bagi 6 bulan terakhir: {{ $monthChart->map(fn ($m) => $m['label'] . ' ' . $m['received'] . ' diterima, ' . $m['done'] . ' selesai')->implode('; ') }}">
                        @foreach ($monthChart as $m)
                            <div class="ss-vchart-col">
                                <div class="ss-vchart-bars">
                                    <span class="ss-vchart-bar bg-navy" style="height: {{ $m['received'] / $monthMax * 100 }}%" title="{{ $m['received'] }} diterima">
                                        @if ($m['received']) <span>{{ $m['received'] }}</span> @endif
                                    </span>
                                    <span class="ss-vchart-bar bg-success" style="height: {{ $m['done'] / $monthMax * 100 }}%" title="{{ $m['done'] }} selesai">
                                        @if ($m['done']) <span>{{ $m['done'] }}</span> @endif
                                    </span>
                                </div>
                                <div class="ss-vchart-label">{{ $m['month'] }}<span class="d-block">{{ $m['year'] }}</span></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 3 + 4: permit type and meeting results --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Jenis Permit &amp; Keputusan Mesyuarat</div>
                <div class="card-body">
                    @foreach ($permitChart as $row)
                        <a href="{{ route('admin.applications.index', ['permit' => $row['type']->value]) }}" class="d-block text-decoration-none text-body mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $row['type']->label() }}</span>
                                <span class="fw-semibold">{{ $row['total'] }} <span class="text-muted fw-normal">({{ round($row['total'] / $permitTotal * 100) }}%)</span></span>
                            </div>
                            <div class="progress" style="height: 8px">
                                <div class="progress-bar bg-navy" style="width: {{ $row['total'] / $permitTotal * 100 }}%"></div>
                            </div>
                        </a>
                    @endforeach

                    <hr>
                    <div class="small text-muted mb-2">Keputusan mesyuarat ({{ $meetingTotal }} rekod)</div>
                    @if ($meetingTotal)
                        <div class="progress mb-2" style="height: 14px" role="img"
                             aria-label="{{ $meetingChart->map(fn ($r) => $r['result']->label() . ' ' . $r['total'])->implode(', ') }}">
                            @foreach ($meetingChart as $r)
                                <div class="progress-bar bg-{{ $r['result']->color() }}" style="width: {{ $r['total'] / $meetingTotal * 100 }}%"></div>
                            @endforeach
                        </div>
                        <div class="d-flex flex-wrap gap-3 small">
                            @foreach ($meetingChart as $r)
                                <span><span class="stat-dot bg-{{ $r['result']->color() }} align-middle"></span> {{ $r['result']->label() }} <strong>{{ $r['total'] }}</strong></span>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-muted mb-0">Belum ada keputusan mesyuarat direkodkan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
