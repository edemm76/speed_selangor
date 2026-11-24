@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Notifikasi</h1>
            <p class="text-muted mb-0">Makluman berkaitan permohonan. Klik notifikasi untuk melihat mesej penuh.</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route($role . '.notifications.readAll') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-navy">
                    <i class="bi bi-check2-all"></i> Tandakan semua telah dibaca
                </button>
            </form>
        @endif
    </div>

    <ul class="nav nav-pills ss-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route($role . '.notifications.index') }}">
                Semua <span class="badge">{{ $totalCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $filter === 'unread' ? 'active' : '' }}" href="{{ route($role . '.notifications.index', ['filter' => 'unread']) }}">
                Belum Dibaca <span class="badge">{{ $unreadCount }}</span>
            </a>
        </li>
    </ul>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $item)
                @php [$icon, $color] = $item->notification->icon(); @endphp
                <a href="{{ route($role . '.notifications.show', $item) }}"
                   class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $item->isRead() ? '' : 'bg-light' }}">
                    <span class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center bg-{{ $color }}-subtle text-{{ $color }}-emphasis"
                          style="width: 42px; height: 42px;">
                        <i class="bi {{ $icon }} fs-5"></i>
                    </span>
                    <span class="flex-grow-1" style="min-width: 0">
                        <span class="d-flex justify-content-between gap-2">
                            <span class="{{ $item->isRead() ? 'fw-semibold' : 'fw-bold' }}" style="color: var(--ss-navy)">
                                {{ $item->notification->title }}
                                @unless ($item->isRead())
                                    <span class="badge bg-danger ms-1">Baharu</span>
                                @endunless
                            </span>
                            <span class="small text-muted text-nowrap" title="{{ $item->created_at->format('d/m/Y, h:i A') }}">
                                {{ $item->created_at->locale('ms')->diffForHumans() }}
                            </span>
                        </span>
                        <span class="d-block small text-muted text-truncate">{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $item->notification->message), 140) }}</span>
                        @if ($item->application)
                            <span class="d-block small mt-1"><i class="bi bi-folder2"></i> {{ $item->application->reference_no }}</span>
                        @endif
                    </span>
                </a>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    {{ $filter === 'unread' ? 'Tiada notifikasi yang belum dibaca.' : 'Tiada notifikasi.' }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
@endsection
