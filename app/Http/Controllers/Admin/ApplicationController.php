<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\PermitType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * SSMS-UC-300 Manage Application (Administrator part)
 * - list of all submitted applications with search and filters
 * - application detail (applicant, documents, processing days, timeline)
 * - update status with remarks; every change is saved in the status history
 */
class ApplicationController extends Controller
{
    /** Senarai Permohonan: search + status tabs + paging */
    public function index(Request $request): View
    {
        $statuses = collect(ApplicationStatus::cases())->reject(fn ($s) => $s === ApplicationStatus::Draft);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            // a status value, "in_progress" (not decided yet) or "overdue" (past the 14-day target)
            'status' => ['nullable', Rule::in($statuses->map->value->push('in_progress', 'overdue')->all())],
            'permit' => ['nullable', Rule::enum(PermitType::class)],
        ]);

        $applications = Application::query()
            ->submitted() // officers never see drafts
            ->with(['vendor', 'statusHistories'])
            // Search reference no, project title, applicant name / company / IC / SSM
            ->when($filters['q'] ?? null, function ($query, $q) {
                $like = '%' . $q . '%';
                $query->where(function ($w) use ($like) {
                    $w->where('reference_no', 'like', $like)
                        ->orWhere('project_title', 'like', $like)
                        ->orWhereHas('vendor', fn ($v) => $v->where(fn ($x) => $x
                            ->where('pic_name', 'like', $like)
                            ->orWhere('company_name', 'like', $like)
                            ->orWhere('ic_no', 'like', $like)
                            ->orWhere('registration_no', 'like', $like)));
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                match ($status) {
                    'overdue' => $query->overdue(),
                    'in_progress' => $query->inProgress(),
                    default => $query->where('current_status', $status),
                };
            })
            ->when($filters['permit'] ?? null, fn ($query, $permit) => $query->where('permit_type', $permit))
            // Overdue list: oldest first (most urgent on top). Otherwise newest first.
            ->orderBy('submitted_at', ($filters['status'] ?? null) === 'overdue' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        // Numbers for the tabs
        $counts = Application::submitted()
            ->selectRaw('current_status, count(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        return view('admin.applications.index', [
            'applications' => $applications,
            'filters' => $filters,
            'statuses' => $statuses,
            'counts' => $counts,
            'total' => $counts->sum(),
            'inProgressCount' => Application::inProgress()->count(),
            'overdueCount' => Application::overdue()->count(),
        ]);
    }

    /** Butiran Permohonan for the officer */
    public function show(Application $application): View
    {
        // Drafts are private to the applicant
        abort_if($application->current_status === ApplicationStatus::Draft, 404);

        $application->load(['vendor', 'documents', 'statusHistories.admin', 'verifications.admin']);

        return view('admin.applications.show', compact('application'));
    }

    /** Update the status (with remarks). Only allowed next statuses are accepted. */
    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        abort_if($application->current_status === ApplicationStatus::Draft, 404);

        $allowed = collect($application->current_status->nextForOfficer())->map->value->all();

        if (! $allowed) {
            return back()->with('error', 'Status permohonan ini tidak boleh diubah lagi.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'remarks' => [
                Rule::requiredIf(fn () => ApplicationStatus::tryFrom((string) $request->input('status'))?->needsRemarks()),
                'nullable', 'string', 'max:1000',
            ],
        ], [
            'status.required' => 'Sila pilih status baharu.',
            'status.in' => 'Status yang dipilih tidak dibenarkan untuk permohonan ini. Sila muat semula halaman.',
            'remarks.required' => 'Sila tulis catatan supaya pemohon tahu sebabnya.',
            'remarks.max' => 'Catatan terlalu panjang (maksimum 1000 aksara).',
        ]);

        $status = ApplicationStatus::from($data['status']);
        $remarks = ($data['remarks'] ?? null) ?: $this->defaultRemark($status);
        $adminId = Auth::guard('admin')->id();

        DB::transaction(function () use ($application, $status, $remarks, $adminId) {
            $application->changeStatus($status, $remarks, $adminId);

            // Verification decisions are also recorded in the verifications table
            if (in_array($status, [ApplicationStatus::ActionRequired, ApplicationStatus::Verified])) {
                $application->verifications()->create([
                    'verified_by_admin_id' => $adminId,
                    'decision' => $status->value,
                    'notes' => $remarks,
                ]);
            }
        });

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Status permohonan ' . $application->reference_no . ' telah dikemas kini kepada "' . $status->label() . '".');
    }

    /** Open an applicant's uploaded document (private file) */
    public function document(Application $application, ApplicationDocument $document): Response
    {
        abort_unless((int) $document->application_id === (int) $application->application_id, 404);

        $disk = Storage::disk(config('speed.upload.disk'));
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, $document->file_name);
    }

    /** Remark saved when the officer leaves the box empty */
    private function defaultRemark(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::UnderReview => 'Permohonan sedang disemak oleh pegawai.',
            ApplicationStatus::Verified => 'Maklumat permohonan telah disahkan.',
            ApplicationStatus::Approved => 'Permohonan diluluskan.',
            default => $status->label() . '.',
        };
    }
}