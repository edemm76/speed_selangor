<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicantType;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SSMS-UC-200 Manage User (Administrator part)
 * - Vendor List: list and search applicant accounts [SSMS-REQ-207]
 * - view one applicant and their applications
 * - A3 Reactivate Account [SSMS-REQ-208]
 * - A4 Suspend Account [SSMS-REQ-209]
 */
class VendorController extends Controller
{
    /** Senarai Pemohon: search + filter + paging */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(VendorStatus::class)],
            'type' => ['nullable', Rule::enum(ApplicantType::class)],
        ]);

        $vendors = Vendor::query()
            ->withCount('applications')
            // Search name, company, e-mail, IC, SSM or phone
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $like = '%' . $q . '%';
                    $w->where('pic_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('ic_no', 'like', $like)
                        ->orWhere('registration_no', 'like', $like)
                        ->orWhere('no_phone', 'like', $like);
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('applicant_type', $type))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Numbers for the status tabs (ignore the status filter itself)
        $counts = Vendor::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'filters' => $filters,
            'counts' => $counts,
            'totalVendors' => $counts->sum(),
        ]);
    }

    /** Butiran Pemohon: details + their applications */
    public function show(Vendor $vendor): View
    {
        $vendor->load(['applications' => fn ($q) => $q->latest()]);

        return view('admin.vendors.show', compact('vendor'));
    }

    /** [A4: Suspend Account] the applicant can no longer log in */
    public function suspend(Vendor $vendor): RedirectResponse
    {
        if ($vendor->status === VendorStatus::Suspended) {
            return back()->with('error', 'Akaun ini telah pun digantung.');
        }

        $vendor->update(['status' => VendorStatus::Suspended]);

        return back()->with('success', 'Akaun ' . $vendor->displayName() . ' telah digantung. Pemohon tidak lagi boleh log masuk.');
    }

    /** [A3: Reactivate Account] the applicant can log in again */
    public function activate(Vendor $vendor): RedirectResponse
    {
        if ($vendor->isActive()) {
            return back()->with('error', 'Akaun ini sudah aktif.');
        }

        $vendor->update(['status' => VendorStatus::Active]);

        return back()->with('success', 'Akaun ' . $vendor->displayName() . ' telah diaktifkan semula.');
    }
}