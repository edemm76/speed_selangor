<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Applicant dashboard (thesis Figure 3.40 and Table 3.12):
 * status cards, applications that need action, latest applications,
 * notification preview and shortcuts to the application features.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $groups = ApplicationStatus::vendorGroups();

        // Number of applications per status group (one query)
        $byStatus = $vendor->applications()
            ->selectRaw('current_status, count(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');
        $counts = collect($groups)->map(fn ($g) => collect($g[1])->sum(fn ($s) => $byStatus[$s->value] ?? 0));

        // Applications waiting for the applicant
        $actionNeeded = $vendor->applications()
            ->where('current_status', ApplicationStatus::ActionRequired)
            ->with('verifications')
            ->latest('updated_at')
            ->get();

        $drafts = $vendor->applications()
            ->where('current_status', ApplicationStatus::Draft)
            ->latest('updated_at')
            ->take(3)
            ->get();

        // Latest submitted applications
        $latest = $vendor->applications()
            ->submitted()
            ->with('statusHistories')
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Notification preview
        $notifications = $vendor->userNotifications()
            ->with(['notification', 'application'])
            ->latest('created_at')
            ->orderByDesc('user_notification_id')
            ->take(5)
            ->get();
        $unread = $vendor->userNotifications()->unread()->count();

        return view('vendor.dashboard', [
            'vendor' => $vendor,
            'groups' => $groups,
            'counts' => $counts,
            'total' => $byStatus->sum() - ($byStatus[ApplicationStatus::Draft->value] ?? 0),
            'actionNeeded' => $actionNeeded,
            'drafts' => $drafts,
            'latest' => $latest,
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }
}
