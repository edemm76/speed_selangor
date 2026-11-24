@extends('layouts.app')

@php
    $status = $application->current_status;
    $target = config('speed.target_days');
    $days = $application->daysElapsed();
    $percent = min(100, (int) round($days / max(1, $target) * 100));
    $vendor = $application->vendor;
    $required = $application->permit_type->requiredDocuments();
    $resubmitted = $application->isResubmitted();
    $lastRequest = $application->verifications->firstWhere('decision', \App\Enums\ApplicationStatus::ActionRequired);
    $latestHistory = $application->statusHistories->last();
    $oldDocs = old('docs_ok', []);
@endphp

@section('title', 'Semakan ' . $application->reference_no)

@push('styles')
    <style>
        /* Status timeline */
        .ss-timeline { list-style: none; padding: 0; margin: 0; }
        .ss-timeline li { position: relative; padding: 0 0 1.25rem 1.6rem; }
        .ss-timeline li::before { content: ""; position: absolute; left: 6px; top: 6px; bottom: -6px; width: 2px; background: var(--ss-border); }
        .ss-timeline li:last-child { padding-bottom: 0; }
        .ss-timeline li:last-child::before { display: none; }
        .ss-timeline .tl-dot { position: absolute; left: 0; top: 4px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--ss-border); }
        /* Checklist rows */
        .ss-check { cursor: pointer; }
        .ss-check:has(input:checked) { border-color: var(--bs-success) !important; background: #f3fbf6; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <a href="{{ route('admin.verifications.index') }}" class="small text-decoration-none">
                <i class="bi bi-arrow-left"></i> Semakan Permohonan
            </a>
            <h1 class="h3 mb-0 mt-1">Semakan {{ $application->reference_no }}</h1>
            <p class="text-muted mb-0">{{ $application->project_title }} &middot; {{ $vendor->displayName() }}</p>
        </div>
        <div class="fs-5">
            {!! $status->badge() !!}
            @if ($resubmitted)
                <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-arrow-repeat"></i> Dihantar semula</span>
            @endif
        </div>
    </div>

    {{-- What the officer asked last time, and what the applicant answered --}}
    @if ($resubmitted && $lastRequest)
        <div class="alert alert-warning">
            <div class="fw-semibold"><i class="bi bi-arrow-repeat"></i> Pemohon telah menghantar semula pada {{ $latestHistory->created_at->format('d/m/Y, h:i A') }}</div>
            <div class="small mt-1"><strong>Permintaan pegawai sebelum ini:</strong> {{ $lastRequest->notes }}</div>
            <div class="small"><strong>Maklum balas pemohon:</strong> {{ $latestHistory->remarks }}</div>
            <div class="small mt-1">Dokumen yang dimuat naik semula ditanda <span class="badge bg-info-subtle text-info-emphasis">Dikemas kini</span>.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.verifications.store', $application) }}" id="verify-form">
                @csrf

                {{-- A. Application information --}}
                <div class="card mb-3">
                    <div class="card-header">A. Maklumat Permohonan</div>
                    <div class="card-body">
                        <dl class="row mb-3">
                            <dt class="col-sm-4 text-muted fw-normal">Pemohon</dt>
                            <dd class="col-sm-8">
                                {{ $vendor->displayName() }} ({{ $vendor->applicant_type->label() }}, {{ $vendor->identityNo() }})
                                <a href="{{ route('admin.vendors.show', $vendor) }}" class="small ms-1" target="_blank" rel="noopener">profil <i class="bi bi-box-arrow-up-right"></i></a>
                            </dd>

                            <dt class="col-sm-4 text-muted fw-normal">Jenis Permit</dt>
                            <dd class="col-sm-8">{{ $application->permit_type->label() }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Tajuk Projek</dt>
                            <dd class="col-sm-8">{{ $application->project_title }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Keterangan Projek</dt>
                            <dd class="col-sm-8" style="white-space: pre-line">{{ $application->project_description ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Alamat Tapak</dt>
                            <dd class="col-sm-8" style="white-space: pre-line">{{ $application->site_address }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Koordinat</dt>
                            <dd class="col-sm-8 mb-0">
                                @if ($application->latitude && $application->longitude)
                                    {{ $application->latitude }}, {{ $application->longitude }}
                                    <a href="https://www.google.com/maps?q={{ $application->latitude }},{{ $application->longitude }}"
                                       target="_blank" rel="noopener" class="ms-1 small"><i class="bi bi-geo-alt"></i> Lihat di peta</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </dl>

                        <label class="ss-check border rounded-3 p-2 px-3 d-flex gap-2 align-items-center mb-0">
                            <input type="checkbox" name="info_ok" value="1" class="form-check-input mt-0 verify-item" @checked(old('info_ok'))>
                            <span class="fw-semibold">Maklumat permohonan lengkap dan betul</span>
                        </label>
                    </div>
                </div>

                {{-- B. Documents --}}
                <div class="card mb-3">
                    <div class="card-header">B. Dokumen Sokongan</div>
                    <div class="card-body">
                        <p class="small text-muted">Buka setiap dokumen dan tandakan jika lengkap serta betul.</p>
                        @foreach ($required as $type => $label)
                            @php $doc = $application->documentFor($type); @endphp
                            <div class="ss-check border rounded-3 p-2 px-3 mb-2 d-flex flex-column flex-sm-row justify-content-between gap-2">
                                <label class="d-flex gap-2 align-items-center mb-0 flex-grow-1" style="cursor: pointer">
                                    <input type="checkbox" name="docs_ok[]" value="{{ $type }}" class="form-check-input mt-0 verify-item"
                                           @checked(in_array($type, $oldDocs)) @disabled(! $doc)>
                                    <span class="fw-semibold">{{ $label }}</span>
                                    @if ($doc && $application->isDocumentUpdated($doc))
                                        <span class="badge bg-info-subtle text-info-emphasis">Dikemas kini</span>
                                    @endif
                                </label>
                                @if ($doc)
                                    <a href="{{ route('admin.applications.document', [$application, $doc]) }}" target="_blank" rel="noopener" class="small text-nowrap">
                                        <i class="bi bi-file-earmark-text"></i> {{ \Illuminate\Support\Str::limit($doc->file_name, 30) }}
                                    </a>
                                @else
                                    <span class="small text-danger text-nowrap"><i class="bi bi-x-circle"></i> Tiada fail</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- C. Decision --}}
                <div class="card mb-3">
                    <div class="card-header">C. Keputusan Semakan</div>
                    <div class="card-body">
                        <div id="verify-summary" class="small mb-3"></div>

                        <label for="notes" class="form-label">Catatan kepada pemohon</label>
                        <textarea name="notes" id="notes" rows="3" maxlength="1000"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Contoh: Salinan geran tanah kurang jelas, sila imbas semula.">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Item yang tidak ditanda akan disenaraikan secara automatik dalam catatan kepada pemohon.</div>
                    </div>
                    <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2">
                        {{-- [A1] Request Additional Info --}}
                        <button type="submit" name="decision" value="action_required" class="btn btn-outline-danger">
                            <i class="bi bi-reply"></i> Minta Maklumat Tambahan
                        </button>
                        {{-- [A2] Forward Next Process --}}
                        <button type="submit" name="decision" value="verified" class="btn btn-primary" id="btn-verify">
                            <i class="bi bi-patch-check"></i> Sahkan &amp; Majukan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            {{-- Processing time vs Speed Selangor target --}}
            <div class="card mb-3">
                <div class="card-header">Tempoh Pemprosesan</div>
                <div class="card-body">
                    @if ($status->isFinal())
                        <div class="fs-3 fw-bold {{ $days <= $target ? 'text-success' : 'text-danger' }}">{{ $days }} hari</div>
                        <div class="small text-muted">
                            Selesai {{ $days <= $target ? 'dalam' : 'melebihi' }} sasaran {{ $target }} hari.
                        </div>
                    @else
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="fs-3 fw-bold {{ $application->isOverdue() ? 'text-danger' : '' }}">{{ $days }} hari</div>
                            <div class="small text-muted mb-2">sasaran {{ $target }} hari</div>
                        </div>
                        <div class="progress my-2" role="progressbar" aria-label="Tempoh pemprosesan"
                             aria-valuenow="{{ $days }}" aria-valuemin="0" aria-valuemax="{{ $target }}" style="height: 8px">
                            <div class="progress-bar {{ $application->isOverdue() ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success') }}"
                                 style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="small {{ $application->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">
                            @if ($application->isOverdue())
                                Melebihi sasaran sebanyak {{ abs($application->daysRemaining()) }} hari.
                            @else
                                Baki {{ $application->daysRemaining() }} hari sebelum sasaran.
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Status timeline (newest first) --}}
            <div class="card mb-3">
                <div class="card-header">Sejarah Status</div>
                <div class="card-body">
                    <ul class="ss-timeline">
                        @foreach ($application->statusHistories->reverse() as $history)
                            <li>
                                <span class="tl-dot bg-{{ $history->status->color() }}"></span>
                                <div class="d-flex justify-content-between gap-2">
                                    {!! $history->status->badge() !!}
                                    <span class="small text-muted text-nowrap">{{ $history->created_at->format('d/m/Y, h:i A') }}</span>
                                </div>
                                @if ($history->remarks)
                                    <div class="mt-1">{{ $history->remarks }}</div>
                                @endif
                                <div class="small text-muted">{{ $history->actorName() }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const items = document.querySelectorAll('.verify-item');
            const summary = document.getElementById('verify-summary');
            const btnVerify = document.getElementById('btn-verify');

            // "Sahkan & Majukan" only works when everything is ticked
            function update() {
                const total = items.length;
                const ticked = Array.from(items).filter(function (i) { return i.checked; }).length;
                const complete = ticked === total;
                btnVerify.disabled = !complete;
                summary.innerHTML = complete
                    ? '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Semua item lengkap. Permohonan boleh disahkan.</span>'
                    : '<span class="text-muted"><i class="bi bi-list-check"></i> ' + ticked + ' daripada ' + total + ' item ditanda lengkap.</span>';
            }
            items.forEach(function (i) { i.addEventListener('change', update); });
            update();
        })();
    </script>
@endpush
