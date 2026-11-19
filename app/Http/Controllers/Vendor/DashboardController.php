<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Vendor dashboard.
 * Simple version for now - the full dashboard (Figure 3.40) comes in Step 14.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $applications = $vendor->applications();

        $stats = [
            'total' => (clone $applications)->count(),
            'draft' => (clone $applications)->where('current_status', ApplicationStatus::Draft)->count(),
            'action_required' => (clone $applications)->where('current_status', ApplicationStatus::ActionRequired)->count(),
            'approved' => (clone $applications)->where('current_status', ApplicationStatus::Approved)->count(),
        ];

        return view('vendor.dashboard', compact('vendor', 'stats'));
    }
}