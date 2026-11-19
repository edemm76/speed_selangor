<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Vendor;
use Illuminate\View\View;

/**
 * Administrator dashboard.
 * Simple version for now - the full analytics dashboard comes in Step 15.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total' => Application::submitted()->count(),
            'in_progress' => Application::inProgress()->count(),
            'overdue' => Application::overdue()->count(),
            'pending_vendors' => Vendor::where('status', VendorStatus::Pending)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}