{{--
    Navbar "Notifikasi" item with unread count and the 5 latest notifications [SSMS-REQ-501].
    Used by partials/navbar for both roles. Needs: $role ('admin' or 'vendor'), $user
--}}
@php
    $column = $role === 'admin' ? 'admin_id' : 'vendor_id';
    $myNotifications = \App\Models\UserNotification::where($column, $user->getKey());
    $unreadCount = (clone $myNotifications)->unread()->count();
    $recent = (clone $myNotifications)->with('notification')
        ->latest('created_at')->orderByDesc('user_notification_id')
        ->limit(5)->get();
@endphp

<li class="nav-item dropdown">
    <a class="nav-link {{ request()->routeIs($role . '.notifications.*') ? 'active' : '' }}" href="#"
       role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
       aria-label="Notifikasi, {{ $unreadCount }} belum dibaca">
        <i class="bi bi-bell{{ $unreadCount ? '-fill' : '' }}"></i>
        {{-- Word hidden on laptop screens (992–1399px) so the menu fits; the bell + count stay --}}
        <span class="d-lg-none d-xxl-inline">Notifikasi</span>
        @if ($unreadCount > 0)
            <span class="badge rounded-pill bg-danger">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end shadow p-0 overflow-hidden" style="width: 340px; max-width: 92vw; white-space: normal;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong style="color: var(--ss-navy)">Notifikasi</strong>
            <span class="small text-muted">{{ $unreadCount }} belum dibaca</span>
        </div>

        @forelse ($recent as $item)
            @php [$icon, $color] = $item->notification->icon(); @endphp
            <a href="{{ route($role . '.notifications.show', $item) }}"
               class="dropdown-item d-flex gap-2 py-2 border-bottom {{ $item->isRead() ? '' : 'bg-light' }}" style="white-space: normal;">
                <i class="bi {{ $icon }} text-{{ $color }} fs-5 flex-shrink-0"></i>
                <span style="min-width: 0">
                    <span class="d-block small {{ $item->isRead() ? 'fw-semibold' : 'fw-bold' }}">
                        {{ $item->notification->title }}
                        @unless ($item->isRead())
                            <span class="badge bg-danger">Baharu</span>
                        @endunless
                    </span>
                    <span class="d-block small text-muted">{{ \Illuminate\Support\Str::limit(str_replace("\n", ' ', $item->notification->message), 80) }}</span>
                    <span class="d-block small text-muted">{{ $item->created_at->locale('ms')->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <div class="text-center text-muted small py-4">
                <i class="bi bi-bell-slash fs-4 d-block mb-1"></i> Tiada notifikasi.
            </div>
        @endforelse

        <a href="{{ route($role . '.notifications.index') }}" class="dropdown-item text-center small fw-semibold py-2">
            Lihat semua notifikasi <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</li>
