<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SSMS-UC-500 Manage Notification (both roles)
 * - list of notifications [SSMS-REQ-501]
 * - full message details, marked as read when opened [SSMS-REQ-502]
 * The latest notifications and the unread count are also shown in the
 * navbar bell on every page (partials/navbar).
 */
class NotificationController extends Controller
{
    /** 'admin' for /admin/... routes, 'vendor' for /vendor/... routes */
    private function role(Request $request): string
    {
        return $request->routeIs('admin.*') ? 'admin' : 'vendor';
    }

    /** Only this user's notifications */
    private function myNotifications(Request $request)
    {
        $role = $this->role($request);
        $column = $role === 'admin' ? 'admin_id' : 'vendor_id';

        return UserNotification::where($column, $request->user($role)->getKey());
    }

    /** Senarai Notifikasi */
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = $this->myNotifications($request)
            ->with(['notification', 'application'])
            ->when($filter === 'unread', fn ($q) => $q->unread())
            ->latest('created_at')
            ->orderByDesc('user_notification_id')
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'role' => $this->role($request),
            'unreadCount' => $this->myNotifications($request)->unread()->count(),
            'totalCount' => $this->myNotifications($request)->count(),
        ]);
    }

    /** Full message; opening it marks it as read */
    public function show(Request $request, UserNotification $userNotification): View
    {
        $role = $this->role($request);
        $column = $role === 'admin' ? 'admin_id' : 'vendor_id';

        // Someone else's notification -> not found
        abort_unless((int) $userNotification->{$column} === (int) $request->user($role)->getKey(), 404);

        $userNotification->load(['notification', 'application']);
        $userNotification->markAsRead();

        return view('notifications.show', [
            'item' => $userNotification,
            'role' => $role,
        ]);
    }

    /** "Tandakan semua telah dibaca" */
    public function readAll(Request $request): RedirectResponse
    {
        $count = $this->myNotifications($request)->unread()->update(['read_at' => now()]);

        return back()->with('success', $count > 0
            ? $count . ' notifikasi ditandakan telah dibaca.'
            : 'Tiada notifikasi baharu.');
    }
}
