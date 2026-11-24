<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\MeetingResult;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Committee meeting decision (SSMS-UC-400, after "Disahkan").
 * The verified application is brought to a meeting (e.g. JK OSC). The officer records:
 * meeting name and date, department, who checked the documents, who gave the decision,
 * the minutes (PDF + summary) and the result. The result sets the final status and the
 * applicant is notified automatically (with the reason if rejected).
 */
class MeetingController extends Controller
{
    /** Form "Rekod Keputusan Mesyuarat" (only for verified applications) */
    public function create(Application $application): View|RedirectResponse
    {
        if ($application->current_status !== ApplicationStatus::Verified) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Keputusan mesyuarat hanya boleh direkod bagi permohonan yang telah disahkan.');
        }

        $application->load(['vendor', 'verifications.admin']);

        return view('admin.meetings.create', compact('application'));
    }

    /** Save the decision, set the final status and notify the applicant */
    public function store(Request $request, Application $application): RedirectResponse
    {
        if ($application->current_status !== ApplicationStatus::Verified) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Keputusan mesyuarat hanya boleh direkod bagi permohonan yang telah disahkan.');
        }

        $result = MeetingResult::tryFrom((string) $request->input('result'));

        $data = $request->validate([
            'meeting_title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $application->submitted_at->toDateString()],
            'department' => ['required', 'string', 'max:255'],
            'checked_by' => ['required', 'string', 'max:1000'],
            'approver_name' => ['required', 'string', 'max:255'],
            'approver_position' => ['required', 'string', 'max:255'],
            'result' => ['required', Rule::enum(MeetingResult::class)],
            'reason' => [Rule::requiredIf($result === MeetingResult::Ditolak), 'nullable', 'string', 'max:2000'],
            'amendments' => [Rule::requiredIf($result === MeetingResult::LulusDenganPindaan), 'nullable', 'string', 'max:2000'],
            'minutes_summary' => ['required', 'string', 'max:3000'],
            'minutes_file' => ['required', 'file', 'mimes:pdf', 'max:' . config('speed.upload.max_kb')],
            'confirm' => ['accepted'],
        ], [
            'required' => 'Sila isi :attribute.',
            'max' => ':attribute terlalu panjang.',
            'meeting_date.date' => 'Tarikh mesyuarat tidak sah.',
            'meeting_date.before_or_equal' => 'Tarikh mesyuarat tidak boleh selepas hari ini.',
            'meeting_date.after_or_equal' => 'Tarikh mesyuarat tidak boleh sebelum tarikh permohonan dihantar.',
            'result.required' => 'Sila pilih keputusan mesyuarat.',
            'reason.required' => 'Sebab penolakan wajib diisi supaya pemohon tahu mengapa permohonan ditolak.',
            'amendments.required' => 'Sila nyatakan pindaan yang perlu dibuat oleh pemohon.',
            'minutes_file.required' => 'Sila muat naik minit mesyuarat (PDF).',
            'minutes_file.mimes' => 'Minit mesyuarat mesti dalam format PDF.',
            'minutes_file.max' => 'Saiz minit mesyuarat tidak boleh melebihi ' . (config('speed.upload.max_kb') / 1024) . ' MB.',
            'confirm.accepted' => 'Sila sahkan bahawa maklumat keputusan mesyuarat adalah tepat.',
        ], [
            'meeting_title' => 'Nama / Bil. Mesyuarat',
            'meeting_date' => 'Tarikh Mesyuarat',
            'department' => 'Jabatan',
            'checked_by' => 'Disemak Oleh',
            'approver_name' => 'Nama Pelulus',
            'approver_position' => 'Jawatan Pelulus',
            'minutes_summary' => 'Ringkasan Minit',
        ]);

        $file = $request->file('minutes_file');
        $path = $file->storeAs(
            'meetings/' . $application->application_id,
            'minit-' . Str::random(10) . '.pdf',
            config('speed.upload.disk')
        );

        $decision = DB::transaction(function () use ($application, $data, $result, $file, $path) {
            $decision = $application->meetingDecision()->create([
                'meeting_title' => $data['meeting_title'],
                'meeting_date' => $data['meeting_date'],
                'department' => $data['department'],
                'checked_by' => $data['checked_by'],
                'approver_name' => $data['approver_name'],
                'approver_position' => $data['approver_position'],
                'result' => $result,
                'reason' => $result === MeetingResult::Ditolak ? $data['reason'] : null,
                'amendments' => $result === MeetingResult::LulusDenganPindaan ? $data['amendments'] : null,
                'minutes_summary' => $data['minutes_summary'],
                'minutes_file_name' => $file->getClientOriginalName(),
                'minutes_file_path' => $path,
                'recorded_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            $application->setRelation('meetingDecision', $decision);

            // Final status + timeline entry + notification to the applicant
            $application->changeStatus($result->applicationStatus(), $decision->summaryLine(), Auth::guard('admin')->id());

            return $decision;
        });

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Keputusan mesyuarat bagi ' . $application->reference_no . ' (' . $decision->result->label() . ') telah direkod dan pemohon telah dimaklumkan.');
    }

    /** Minutes PDF (officers only) */
    public function minutes(Application $application): Response
    {
        $decision = $application->meetingDecision;
        abort_unless($decision && $decision->minutes_file_path, 404);

        $disk = Storage::disk(config('speed.upload.disk'));
        abort_unless($disk->exists($decision->minutes_file_path), 404);

        return $disk->response($decision->minutes_file_path, $decision->minutes_file_name);
    }
}
