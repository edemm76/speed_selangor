<?php

namespace App\Http\Controllers\Vendor;

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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * SSMS-UC-300 Manage Application (Vendor part)
 * - New Application form [SSMS-REQ-301]
 * - Submit -> reference number + success message [SSMS-REQ-302]
 * - My Application list + detail with status timeline [SSMS-REQ-303]
 * - A1 Save Draft [SSMS-REQ-304]
 * - E1 Missing Required File: submission blocked, list of missing documents
 * - Resubmission after "Tindakan Diperlukan" (SSMS-UC-400 A1, applicant side)
 */
class ApplicationController extends Controller
{
    /** Only the owner may open an application */
    private function authorizeOwner(Application $application): void
    {
        abort_unless((int) $application->vendor_id === (int) Auth::guard('vendor')->id(), 404);
    }

    /** Only drafts can be edited or deleted in this step */
    private function authorizeDraft(Application $application): void
    {
        $this->authorizeOwner($application);

        if ($application->current_status !== ApplicationStatus::Draft) {
            abort(redirect()->route('vendor.applications.index')
                ->with('error', 'Permohonan ' . $application->reference_no . ' telah dihantar dan tidak boleh diubah.'));
        }
    }

    /** Permohonan Saya: all of this applicant's applications */
    public function index(): View
    {
        $applications = Auth::guard('vendor')->user()
            ->applications()
            ->latest('updated_at')
            ->paginate(10);

        return view('vendor.applications.index', compact('applications'));
    }

    /**
     * Application detail [SSMS-REQ-303]: progress, processing days,
     * documents, officer notes and the full status timeline.
     * Drafts have no detail page yet, so they open the edit form.
     */
    public function show(Application $application): View|RedirectResponse
    {
        $this->authorizeOwner($application);

        if ($application->current_status === ApplicationStatus::Draft) {
            return redirect()->route('vendor.applications.edit', $application);
        }

        $application->load(['documents', 'statusHistories.admin', 'verifications.admin', 'meetingDecision']);

        return view('vendor.applications.show', compact('application'));
    }

    /** New application form */
    public function create(): View
    {
        return view('vendor.applications.form', [
            'application' => new Application(['permit_type' => PermitType::KebenaranMerancang]),
        ]);
    }

    /** Save a new application (as draft, or submit straight away) */
    public function store(Request $request): RedirectResponse
    {
        $application = new Application([
            'vendor_id' => Auth::guard('vendor')->id(),
            'current_status' => ApplicationStatus::Draft,
        ]);

        return $this->save($request, $application);
    }

    /** Edit a draft */
    public function edit(Application $application): View
    {
        $this->authorizeDraft($application);
        $application->load('documents');

        return view('vendor.applications.form', compact('application'));
    }

    /** Save changes to a draft (as draft, or submit) */
    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeDraft($application);

        return $this->save($request, $application);
    }

    /** Delete a draft and its files */
    public function destroy(Application $application): RedirectResponse
    {
        $this->authorizeDraft($application);

        DB::transaction(function () use ($application) {
            foreach ($application->documents as $document) {
                $document->deleteFile();
            }
            $application->delete(); // documents rows are removed by the foreign key (cascade)
        });

        return redirect()->route('vendor.applications.index')->with('success', 'Draf permohonan telah dipadam.');
    }

    /**
     * Resubmission form [SSMS-UC-400 A1, applicant side]:
     * after the officer asks for more information, the applicant corrects the
     * details / replaces documents and sends the application back for review.
     */
    public function resubmitForm(Application $application): View|RedirectResponse
    {
        $this->authorizeOwner($application);

        if ($application->current_status !== ApplicationStatus::ActionRequired) {
            return redirect()->route('vendor.applications.show', $application)
                ->with('error', 'Permohonan ini tidak memerlukan tindakan daripada anda.');
        }

        $application->load(['documents', 'verifications']);

        return view('vendor.applications.resubmit', compact('application'));
    }

    /** Save the corrections and send the application back to the officer */
    public function resubmit(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($application);

        if ($application->current_status !== ApplicationStatus::ActionRequired) {
            return redirect()->route('vendor.applications.show', $application)
                ->with('error', 'Permohonan ini tidak memerlukan tindakan daripada anda.');
        }

        $upload = config('speed.upload');
        $docTypes = array_keys($application->permit_type->requiredDocuments());

        $data = $request->validate([
            'project_description' => ['required', 'string', 'max:2000'],
            'site_address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:2.5,3.9'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:100.7,102.0'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:' . implode(',', $upload['mimes']), 'max:' . $upload['max_kb']],
            'response' => ['nullable', 'string', 'max:1000'],
            'declaration' => ['accepted'],
        ], $this->messages() + [
            'response.max' => 'Catatan terlalu panjang (maksimum 1000 aksara).',
        ], $this->attributes());

        // [E1: Missing Required File] every required document must exist (old file or new upload)
        $application->load('documents');
        $missing = collect($application->permit_type->requiredDocuments())
            ->reject(fn ($label, $type) => $application->documentFor($type) || $request->hasFile("documents.$type"));

        if ($missing->isNotEmpty()) {
            return back()->withInput($request->except('documents'))
                ->withErrors(['documents' => 'Dokumen wajib belum dimuat naik: ' . $missing->implode(', ') . '.']);
        }

        DB::transaction(function () use ($request, $application, $data, $docTypes) {
            $application->update([
                'project_description' => $data['project_description'],
                'site_address' => $data['site_address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $this->storeDocuments($request, $application, $docTypes);

            $remarks = 'Permohonan dihantar semula oleh pemohon selepas pembetulan.';
            if (! empty($data['response'])) {
                $remarks .= ' Catatan pemohon: ' . $data['response'];
            }

            // Back to the officer for review (recorded as a change by the applicant)
            $application->changeStatus(ApplicationStatus::UnderReview, $remarks);
        });

        return redirect()->route('vendor.applications.show', $application)
            ->with('success', 'Permohonan ' . $application->reference_no . ' telah dihantar semula kepada pegawai untuk disemak.');
    }

    /** Open one of my uploaded documents (files are private, not on a public link) */
    public function document(Application $application, ApplicationDocument $document): Response
    {
        $this->authorizeOwner($application);
        abort_unless((int) $document->application_id === (int) $application->application_id, 404);

        $disk = Storage::disk(config('speed.upload.disk'));
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, $document->file_name);
    }

    /**
     * Shared by store() and update().
     * 1. Always save what is filled in first (so typed data and uploaded files are never lost).
     * 2. action=draft  -> stop here, no reference number yet [A1]
     *    action=submit -> check everything is complete; if not, stay as draft and
     *                     show what is missing [E1]; if complete, status becomes "Dihantar".
     */
    private function save(Request $request, Application $application): RedirectResponse
    {
        $submit = $request->input('action') === 'submit';
        $upload = config('speed.upload');

        $request->validate([
            'action' => ['required', 'in:draft,submit'],
            'permit_type' => ['required', Rule::enum(PermitType::class)],
            'project_title' => ['required', 'string', 'max:255'],
            'project_description' => ['nullable', 'string', 'max:2000'],
            'site_address' => ['nullable', 'string', 'max:500'],
            // Optional map point, must be inside Selangor
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:2.5,3.9'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:100.7,102.0'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:' . implode(',', $upload['mimes']), 'max:' . $upload['max_kb']],
        ], $this->messages(), $this->attributes());

        $permit = PermitType::from($request->input('permit_type'));
        $docTypes = array_keys($permit->requiredDocuments());

        DB::transaction(function () use ($request, $application, $permit, $docTypes) {
            $application->fill([
                'permit_type' => $permit,
                'project_title' => $request->input('project_title'),
                'project_description' => $request->input('project_description'),
                'site_address' => (string) $request->input('site_address'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ])->save();

            // Remove documents that do not belong to the (possibly changed) permit type
            foreach ($application->documents()->whereNotIn('document_type', $docTypes)->get() as $old) {
                $old->deleteFile();
                $old->delete();
            }

            $this->storeDocuments($request, $application, $docTypes);
        });

        $application->load('documents');

        if (! $submit) {
            return redirect()->route('vendor.applications.edit', $application)
                ->with('success', 'Draf permohonan telah disimpan. Anda boleh menyambungnya kemudian.');
        }

        // Submit: everything must be complete
        $check = Validator::make([
            'project_description' => $application->project_description,
            'site_address' => $application->site_address,
            'declaration' => $request->input('declaration'),
        ], [
            'project_description' => ['required'],
            'site_address' => ['required'],
            'declaration' => ['accepted'],
        ], $this->messages(), $this->attributes());

        // [E1: Missing Required File]
        $check->after(function ($validator) use ($application) {
            if ($missing = $application->missingDocuments()) {
                $validator->errors()->add('documents', 'Dokumen wajib belum dimuat naik: ' . implode(', ', $missing) . '.');
            }
        });

        if ($check->fails()) {
            return redirect()->route('vendor.applications.edit', $application)
                ->withErrors($check)
                ->withInput($request->only('declaration'))
                ->with('error', 'Permohonan belum boleh dihantar. Maklumat yang telah diisi disimpan sebagai draf.');
        }

        // [SSMS-REQ-302] reference number + status "Dihantar"
        DB::transaction(function () use ($application) {
            $application->reference_no ??= Application::generateReferenceNo();
            $application->submitted_at = now();
            $application->changeStatus(ApplicationStatus::Submitted, 'Permohonan dihantar oleh pemohon.');
        });

        return redirect()->route('vendor.applications.show', $application)
            ->with('success', 'Permohonan berjaya dihantar. No. rujukan anda: ' . $application->reference_no . '.');
    }

    /** Store new uploads; a new file replaces the old one of the same type */
    private function storeDocuments(Request $request, Application $application, array $docTypes): void
    {
        foreach ($docTypes as $type) {
            $file = $request->file("documents.$type");
            if (! $file) {
                continue;
            }

            if ($old = $application->documents()->where('document_type', $type)->first()) {
                $old->deleteFile();
                $old->delete();
            }

            $path = $file->storeAs(
                'applications/' . $application->application_id,
                $type . '-' . Str::random(10) . '.' . strtolower($file->getClientOriginalExtension()),
                config('speed.upload.disk')
            );

            $application->documents()->create([
                'document_type' => $type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }

        $application->unsetRelation('documents');
    }

    private function messages(): array
    {
        $maxMb = config('speed.upload.max_kb') / 1024;

        return [
            'required' => 'Sila isi :attribute.',
            'permit_type.required' => 'Sila pilih jenis permit.',
            'permit_type.enum' => 'Jenis permit tidak sah.',
            'max' => ':attribute terlalu panjang.',
            'numeric' => ':attribute mesti nombor.',
            'latitude.between' => 'Latitud mesti berada dalam negeri Selangor (2.5 hingga 3.9).',
            'longitude.between' => 'Longitud mesti berada dalam negeri Selangor (100.7 hingga 102.0).',
            'required_with' => 'Sila isi kedua-dua latitud dan longitud, atau biarkan kedua-duanya kosong.',
            'documents.*.mimes' => 'Dokumen mesti dalam format PDF, JPG atau PNG.',
            'documents.*.max' => 'Saiz setiap dokumen tidak boleh melebihi ' . $maxMb . ' MB.',
            'documents.*.file' => 'Dokumen gagal dimuat naik. Sila cuba lagi.',
            'documents.*.uploaded' => 'Dokumen gagal dimuat naik (saiz mungkin melebihi ' . $maxMb . ' MB).',
            'declaration.accepted' => 'Sila tandakan perakuan sebelum menghantar permohonan.',
        ];
    }

    private function attributes(): array
    {
        return [
            'project_title' => 'Tajuk Projek',
            'project_description' => 'Keterangan Projek',
            'site_address' => 'Alamat Tapak',
            'latitude' => 'Latitud',
            'longitude' => 'Longitud',
        ];
    }
}