<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\MeetingResult;
use App\Enums\PermitType;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MeetingDecision;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Administrator dashboard (thesis Table 3.12 "Administrator dashboard with notification preview"
 * and the Manage Report objective "view analytics"):
 * - key figures (total, in progress, overdue, average processing days, % within target, applicants)
 * - work queue (what the officer has to do next)
 * - applications past the 14-day target
 * - charts: applications by status, by month, by permit type, meeting results
 * - notification preview
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();
        $target = config('speed.target_days');

        // Number of applications per status (one query)
        $byStatus = Application::submitted()
            ->selectRaw('current_status, count(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        // Finished applications: processing time = submitted -> final decision
        $finished = Application::whereIn('current_status', [ApplicationStatus::Approved->value, ApplicationStatus::Rejected->value])
            ->with('statusHistories')
            ->get();
        $days = $finished->map->daysElapsed();

        $stats = [
            'total' => $byStatus->sum(),
            'in_progress' => Application::inProgress()->count(),
            'overdue' => Application::overdue()->count(),
            'finished' => $finished->count(),
            'avg_days' => $days->isEmpty() ? null : round($days->avg(), 1),
            'within_target' => $days->isEmpty() ? null : (int) round($days->filter(fn ($d) => $d <= $target)->count() / $days->count() * 100),
            'vendors' => Vendor::count(),
            'suspended_vendors' => Vendor::where('status', VendorStatus::Suspended)->count(),
        ];

        // Work queue: links to the matching tab of the Verification Queue
        $queue = [
            ['new', 'Permohonan baharu belum disemak', $byStatus[ApplicationStatus::Submitted->value] ?? 0, 'bi-inbox', 'warning'],
            ['resubmitted', 'Dihantar semula oleh pemohon', Application::resubmitted()->count(), 'bi-arrow-repeat', 'info'],
            ['waiting_vendor', 'Menunggu tindakan pemohon', $byStatus[ApplicationStatus::ActionRequired->value] ?? 0, 'bi-hourglass-split', 'danger'],
            ['verified', 'Disahkan, menunggu keputusan mesyuarat', $byStatus[ApplicationStatus::Verified->value] ?? 0, 'bi-people', 'primary'],
        ];
        $pending = ($byStatus[ApplicationStatus::Submitted->value] ?? 0) + ($byStatus[ApplicationStatus::UnderReview->value] ?? 0);

        // Applications past the target, oldest first (most urgent on top)
        $overdue = Application::overdue()
            ->with(['vendor', 'statusHistories'])
            ->orderBy('submitted_at')
            ->take(5)
            ->get();

        // Chart 1: by status (drafts excluded)
        $statusChart = collect(ApplicationStatus::cases())
            ->reject(fn ($s) => $s === ApplicationStatus::Draft)
            ->map(fn ($s) => ['status' => $s, 'total' => (int) ($byStatus[$s->value] ?? 0)])
            ->values();

        // Chart 2: last 6 months, received vs finished
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $received = Application::submitted()
            ->where('submitted_at', '>=', $months->first())
            ->pluck('submitted_at')
            ->countBy(fn (Carbon $d) => $d->format('Y-m'));
        $done = $finished->map->decidedAt()->filter()->countBy(fn (Carbon $d) => $d->format('Y-m'));
        $monthChart = $months->map(fn (Carbon $m) => [
            'label' => $m->locale('ms')->translatedFormat('M Y'),
            'month' => $m->locale('ms')->translatedFormat('M'),
            'year' => $m->format('Y'),
            'received' => $received[$m->format('Y-m')] ?? 0,
            'done' => $done[$m->format('Y-m')] ?? 0,
        ]);

        // Chart 3: by permit type
        $byPermit = Application::submitted()
            ->selectRaw('permit_type, count(*) as total')
            ->groupBy('permit_type')
            ->pluck('total', 'permit_type');
        $permitChart = collect(PermitType::cases())->map(fn ($p) => ['type' => $p, 'total' => (int) ($byPermit[$p->value] ?? 0)]);

        // Chart 4: meeting results
        $byResult = MeetingDecision::selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');
        $meetingChart = collect(MeetingResult::cases())->map(fn ($r) => ['result' => $r, 'total' => (int) ($byResult[$r->value] ?? 0)]);

        // Notification preview
        $notifications = $admin->userNotifications()
            ->with(['notification', 'application'])
            ->latest('created_at')
            ->orderByDesc('user_notification_id')
            ->take(5)
            ->get();
        $unread = $admin->userNotifications()->unread()->count();

        return view('admin.dashboard', compact(
            'admin', 'stats', 'queue', 'pending', 'overdue',
            'statusChart', 'monthChart', 'permitChart', 'meetingChart',
            'notifications', 'unread'
        ));
    }
}
