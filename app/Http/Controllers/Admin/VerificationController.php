<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SSMS-UC-400 Manage Verification
 * - Verification Queue: applications waiting for verification [SSMS-REQ-401]
 * - verification page with a checklist of required information and documents [SSMS-REQ-401]
 * - choose an action [SSMS-REQ-402]:
 *     A1 Request Additional Info -> "Tindakan Diperlukan" [SSMS-REQ-403]
 *     A2 Forward Next Process    -> "Disahkan"            [SSMS-REQ-404]
 * - store the verification and update the application [SSMS-REQ-405]
 */
class VerificationController extends Controller
{
    /** Tabs of the queue (see tabQuery() for what each one shows) */
    private const TABS = ['pending', 'new', 'resubmitted', 'waiting_vendor', 'verified'];

    /** Verification Queue */
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'pending';

        $applications = $this->tabQuery($tab)
            ->with(['vendor', 'statusHistories'])
            ->orderBy('submitted_at') // oldest first = most urgent on top
            ->paginate(10)
            ->withQueryString();

        $counts = collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $this->tabQuery($t)->count()]);

        return view('admin.verifications.index', compact('applications', 'tab', 'counts'));
    }

    /** "Mula Semak": a new application goes to "Dalam Semakan", then opens the verification page */
    public function start(Application $application): RedirectResponse
    {
        if ($application->current_status === ApplicationStatus::Submitted) {
            $application->changeStatus(
                ApplicationStatus::UnderReview,
                'Permohonan sedang disemak oleh pegawai.',
                Auth::guard('admin')->id()
            );
        }

        return redirect()->route('admin.verifications.show', $application);
    }

    /** Verification page (only for applications "Dalam Semakan") */
    public function show(Application $application): View|RedirectResponse
    {
        if ($application->current_status !== ApplicationStatus::UnderReview) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Permohonan ini tidak berada dalam peringkat semakan (status: ' . $application->current_status->label() . ').');
        }

        $application->load(['vendor', 'documents', 'statusHistories.admin', 'verifications.admin']);

        return view('admin.verifications.show', compact('application'));
    }

    /** Save the verification decision */
    public function store(Request $request, Application $application): RedirectResponse
    {
        if ($application->current_status !== ApplicationStatus::UnderReview) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Permohonan ini tidak lagi berada dalam peringkat semakan.');
        }

        $application->load('documents');
        $required = $application->permit_type->requiredDocuments();

        $data = $request->validate([
            'decision' => ['required', Rule::in([ApplicationStatus::ActionRequired->value, ApplicationStatus::Verified->value])],
            'info_ok' => ['nullable', 'boolean'],
            'docs_ok' => ['nullable', 'array'],
            'docs_ok.*' => [Rule::in(array_keys($required))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'decision.required' => 'Sila pilih tindakan.',
            'notes.max' => 'Catatan terlalu panjang (maksimum 1000 aksara).',
        ]);

        $decision = ApplicationStatus::from($data['decision']);
        $infoOk = (bool) ($data['info_ok'] ?? false);
        $okDocs = $data['docs_ok'] ?? [];
        // Documents not ticked, or not uploaded at all, need attention
        $problemDocs = collect($required)
            ->reject(fn ($label, $type) => in_array($type, $okDocs, true) && $application->documentFor($type))
            ->values()
            ->all();
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($decision === ApplicationStatus::Verified) {
            // [A2] everything must be checked before forwarding
            if (! $infoOk || $problemDocs) {
                return back()->withInput()
                    ->with('error', 'Permohonan hanya boleh disahkan apabila maklumat dan semua dokumen ditanda lengkap.');
            }
            $remarks = $notes ?: 'Maklumat dan dokumen permohonan telah disemak dan disahkan. Dimajukan ke proses seterusnya.';
        } else {
            // [A1] tell the applicant exactly what is wrong
            if ($infoOk && ! $problemDocs && $notes === '') {
                return back()->withInput()
                    ->withErrors(['notes' => 'Sila nyatakan maklumat tambahan yang diperlukan, atau nyahtanda item yang tidak lengkap.']);
            }
            $parts = [];
            if (! $infoOk) {
                $parts[] = 'Maklumat permohonan perlu dilengkapkan / dibetulkan.';
            }
            if ($problemDocs) {
                $parts[] = 'Dokumen perlu dikemas kini: ' . implode(', ', $problemDocs) . '.';
            }
            if ($notes !== '') {
                $parts[] = $notes;
            }
            $remarks = implode(' ', $parts);
        }

        $adminId = Auth::guard('admin')->id();

        DB::transaction(function () use ($application, $decision, $remarks, $adminId) {
            $application->verifications()->create([
                'verified_by_admin_id' => $adminId,
                'decision' => $decision->value,
                'notes' => $remarks,
            ]);
            $application->changeStatus($decision, $remarks, $adminId);
        });

        $message = $decision === ApplicationStatus::Verified
            ? 'Permohonan ' . $application->reference_no . ' telah disahkan dan dimajukan untuk keputusan.'
            : 'Permintaan maklumat tambahan bagi ' . $application->reference_no . ' telah dihantar kepada pemohon.';

        return redirect()->route('admin.verifications.index')->with('success', $message);
    }

    /** Base query for each tab of the queue */
    private function tabQuery(string $tab)
    {
        return match ($tab) {
            // everything the officer still has to check
            'pending' => Application::whereIn('current_status', [ApplicationStatus::Submitted->value, ApplicationStatus::UnderReview->value]),
            'new' => Application::where('current_status', ApplicationStatus::Submitted->value),
            'resubmitted' => Application::resubmitted(),
            'waiting_vendor' => Application::where('current_status', ApplicationStatus::ActionRequired->value),
            'verified' => Application::where('current_status', ApplicationStatus::Verified->value),
        };
    }
}
