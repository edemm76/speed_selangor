@extends('layouts.app')

@php
    $status = $application->current_status;
    $target = config('speed.target_days');
    $days = $application->daysElapsed();
    $decidedAt = $application->decidedAt();
    $latestNote = $application->verifications->first(); // newest officer verification
    $vendor = $application->vendor;
    $choices = $status->nextForOfficer();
    $selectedStatus = old('status');

    // Progress tracker: 4 stages. "Tindakan Diperlukan" sits on stage 2, the decision on stage 4.
    $stage = match ($status) {
        \App\Enums\ApplicationStatus::Submitted => 0,
        \App\Enums\ApplicationStatus::UnderReview, \App\Enums\ApplicationStatus::ActionRequired => 1,
        \App\Enums\ApplicationStatus::Verified => 2,
        default => 3, // Approved / Rejected
    };
    $stages = [
        ['label' => 'Dihantar', 'icon' => 'bi-send'],
        ['label' => $status === \App\Enums\ApplicationStatus::ActionRequired ? 'Tindakan Diperlukan' : 'Dalam Semakan',
         'icon' => $status === \App\Enums\ApplicationStatus::ActionRequired ? 'bi-exclamation-lg' : 'bi-search'],
        ['label' => 'Disahkan', 'icon' => 'bi-patch-check'],
        ['label' => $status->isFinal() ? $status->label() : 'Keputusan',
         'icon' => $status === \App\Enums\ApplicationStatus::Rejected ? 'bi-x-lg' : 'bi-award'],
    ];
    // Colour of the current stage
    $currentColor = $stage === 3 || $status === \App\Enums\ApplicationStatus::ActionRequired ? $status->color() : 'orange';

    $percent = min(100, (int) round($days / max(1, $target) * 100));
@endphp

@section('title', $application->reference_no)

@push('styles')
    <style>
        /* Progress tracker */
        .ss-steps { display: flex; list-style: none; padding: 0; margin: 0; }
        .ss-steps li { flex: 1; text-align: center; position: relative; font-size: .85rem; color: var(--ss-muted); }
        .ss-steps li::before { content: ""; position: absolute; top: 19px; left: -50%; width: 100%; height: 3px; background: var(--ss-border); z-index: 0; }
        .ss-steps li:first-child::before { display: none; }
        .ss-steps li.done::before, .ss-steps li.current::before { background: var(--ss-navy); }
        .ss-steps .dot { position: relative; z-index: 1; width: 40px; height: 40px; border-radius: 50%; margin: 0 auto .4rem;
            display: flex; align-items: center; justify-content: center; background: #fff; border: 3px solid var(--ss-border); font-size: 1.05rem; }
        .ss-steps li.done .dot { background: var(--ss-navy); border-color: var(--ss-navy); color: #fff; }
        .ss-steps li.done, .ss-steps li.current { color: var(--ss-navy); font-weight: 600; }
        .ss-steps li.current .dot { color: #fff; }
        .ss-steps li.current .dot.c-orange { background: var(--ss-orange); border-color: var(--ss-orange); }
        .ss-steps li.current .dot.c-danger { background: var(--bs-danger); border-color: var(--bs-danger); }
        .ss-steps li.current .dot.c-success { background: var(--bs-success); border-color: var(--bs-success); }
        .ss-steps li.current .dot.c-dark { background: var(--bs-dark); border-color: var(--bs-dark); }

        /* Status timeline */
        .ss-timeline { list-style: none; padding: 0; margin: 0; }
        .ss-timeline li { position: relative; padding: 0 0 1.25rem 1.6rem; }
        .ss-timeline li::before { content: ""; position: absolute; left: 6px; top: 6px; bottom: -6px; width: 2px; background: var(--ss-border); }
        .ss-timeline li:last-child { padding-bottom: 0; }
        .ss-timeline li:last-child::before { display: none; }
        .ss-timeline .tl-dot { position: absolute; left: 0; top: 4px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--ss-border); }
        /* Status choices in the officer's form */
        .ss-status-choice { cursor: pointer; }
        .ss-status-choice:has(input:checked) { border-color: var(--ss-orange) !important; background: #fff5f1; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <a href="{{ route('admin.applications.index') }}" class="small text-decoration-none">
                <i class="bi bi-arrow-left"></i> Senarai Permohonan
            </a>
            <h1 class="h3 mb-0 mt-1">{{ $application->reference_no }}</h1>
            <p class="text-muted mb-0">{{ $application->project_title }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fs-5">{!! $status->badge() !!}</span>
            @if ($status === \App\Enums\ApplicationStatus::Submitted)
                <form method="POST" action="{{ route('admin.verifications.start', $application) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-fill"></i> Mula Semak</button>
                </form>
            @elseif ($status === \App\Enums\ApplicationStatus::UnderReview)
                <a href="{{ route('admin.verifications.show', $application) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-clipboard-check"></i> Buka Semakan
                </a>
            @endif
        </div>
    </div>

    @if ($application->isResubmitted())
        <div class="alert alert-warning">
            <i class="bi bi-arrow-repeat"></i> Pemohon telah <strong>menghantar semula</strong> permohonan ini pada
            {{ $application->statusHistories->last()->created_at->format('d/m/Y, h:i A') }}.
            Sila <a href="{{ route('admin.verifications.show', $application) }}" class="alert-link">buka semakan</a> untuk menyemak pembetulan.
        </div>
    @elseif ($status === \App\Enums\ApplicationStatus::ActionRequired)
        <div class="alert alert-warning">
            <i class="bi bi-hourglass-split"></i> Menunggu tindakan pemohon.
            Catatan terakhir: <strong>{{ $latestNote?->notes ?? '—' }}</strong>
        </div>
    @elseif ($application->isOverdue())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> Permohonan ini telah melebihi sasaran
            {{ $target }} hari sebanyak <strong>{{ abs($application->daysRemaining()) }} hari</strong>.
        </div>
    @endif

    {{-- Progress tracker --}}
    <div class="card mb-3">
        <div class="card-body py-4">
            <ol class="ss-steps">
                @foreach ($stages as $i => $s)
                    <li class="{{ $i < $stage ? 'done' : ($i === $stage ? 'current' : '') }}">
                        <div class="dot {{ $i === $stage ? 'c-' . $currentColor : '' }}">
                            <i class="bi {{ $i < $stage ? 'bi-check-lg' : $s['icon'] }}"></i>
                        </div>
                        {{ $s['label'] }}
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Applicant --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>Maklumat Pemohon</span>
                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="small fw-normal">Lihat profil pemohon <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Nama</dt>
                        <dd class="col-sm-8 fw-semibold">
                            {{ $vendor->displayName() }}
                            @unless ($vendor->isActive())
                                {!! $vendor->status->badge() !!}
                            @endunless
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Jenis Pemohon</dt>
                        <dd class="col-sm-8">{{ $vendor->applicant_type->label() }}</dd>

                        @if ($vendor->isCompany())
                            <dt class="col-sm-4 text-muted fw-normal">Pegawai Dihubungi</dt>
                            <dd class="col-sm-8">{{ $vendor->pic_name }}</dd>
                        @endif

                        <dt class="col-sm-4 text-muted fw-normal">{{ $vendor->isCompany() ? 'No. SSM' : 'No. KP' }}</dt>
                        <dd class="col-sm-8">{{ $vendor->identityNo() }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">E-mel</dt>
                        <dd class="col-sm-8"><a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a></dd>

                        <dt class="col-sm-4 text-muted fw-normal">No. Telefon</dt>
                        <dd class="col-sm-8 mb-0">{{ $vendor->no_phone ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Application details --}}
            <div class="card mb-3">
                <div class="card-header">Maklumat Permohonan</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">No. Rujukan</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $application->reference_no }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Jenis Permit</dt>
                        <dd class="col-sm-8">{{ $application->permit_type->label() }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Tajuk Projek</dt>
                        <dd class="col-sm-8">{{ $application->project_title }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Keterangan Projek</dt>
                        <dd class="col-sm-8" style="white-space: pre-line">{{ $application->project_description ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Alamat Tapak</dt>
                        <dd class="col-sm-8" style="white-space: pre-line">{{ $application->site_address }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Koordinat</dt>
                        <dd class="col-sm-8">
                            @if ($application->latitude && $application->longitude)
                                {{ $application->latitude }}, {{ $application->longitude }}
                                <a href="https://www.google.com/maps?q={{ $application->latitude }},{{ $application->longitude }}"
                                   target="_blank" rel="noopener" class="ms-1 small">
                                    <i class="bi bi-geo-alt"></i> Lihat di peta
                                </a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted fw-normal">Tarikh Dihantar</dt>
                        <dd class="col-sm-8 mb-0">{{ $application->submitted_at?->format('d/m/Y, h:i A') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Documents --}}
            <div class="card mb-3">
                <div class="card-header">Dokumen Sokongan</div>
                <ul class="list-group list-group-flush">
                    @foreach ($application->permit_type->requiredDocuments() as $type => $label)
                        @php $doc = $application->documentFor($type); @endphp
                        <li class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 py-3">
                            <span>{{ $label }}</span>
                            @if ($doc)
                                <span class="small">
                                    <i class="bi bi-file-earmark-text text-success"></i>
                                    <a href="{{ route('admin.applications.document', [$application, $doc]) }}"
                                       target="_blank" rel="noopener">{{ $doc->file_name }}</a>
                                    <span class="text-muted">&middot; {{ $doc->uploaded_at?->format('d/m/Y') }}</span>
                                    @if ($application->isDocumentUpdated($doc))
                                        <span class="badge bg-info-subtle text-info-emphasis">Dikemas kini</span>
                                    @endif
                                </span>
                            @else
                                <span class="small text-muted"><i class="bi bi-dash-circle"></i> Tiada fail</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Status update --}}
            <div class="card mb-3" id="kemaskini-status">
                <div class="card-header">Kemas Kini Status</div>
                <div class="card-body">
                    @if ($choices)
                        <form method="POST" action="{{ route('admin.applications.status', $application) }}" id="status-form">
                            @csrf
                            @method('PATCH')

                            <div class="d-grid gap-2 mb-3">
                                @foreach ($choices as $choice)
                                    <label class="ss-status-choice border rounded-3 p-2 d-flex gap-2 align-items-start">
                                        <input type="radio" name="status" value="{{ $choice->value }}" class="form-check-input mt-1"
                                               data-label="{{ $choice->label() }}" data-needs-remarks="{{ $choice->needsRemarks() ? 1 : 0 }}"
                                               @checked($selectedStatus === $choice->value)>
                                        <span>
                                            {!! $choice->badge() !!}
                                            <small class="d-block text-muted mt-1">{{ $choice->officerHint() }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('status') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                            <label for="remarks" class="form-label">
                                Catatan <span class="text-muted small" id="remarks-hint">(pilihan)</span>
                            </label>
                            <textarea name="remarks" id="remarks" rows="3" maxlength="1000"
                                      class="form-control @error('remarks') is-invalid @enderror"
                                      placeholder="Catatan ini akan dilihat oleh pemohon">{{ old('remarks') }}</textarea>
                            @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            <button type="button" class="btn btn-primary w-100 mt-3" id="status-submit" disabled
                                    data-bs-toggle="modal" data-bs-target="#modal-status">
                                <i class="bi bi-check2-circle"></i> Kemas Kini Status
                            </button>
                        </form>
                    @else
                        <p class="text-muted small mb-0">
                            <i class="bi bi-lock"></i>
                            Permohonan ini telah {{ strtolower($status->label()) }}. Status tidak boleh diubah lagi.
                        </p>
                    @endif
                </div>
            </div>

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

    {{-- Confirm status change --}}
    @if ($choices)
        <div class="modal fade" id="modal-status" tabindex="-1" aria-labelledby="modal-status-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-status-title">Sahkan perubahan status</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Tukar status <strong>{{ $application->reference_no }}</strong> daripada
                        <strong>{{ $status->label() }}</strong> kepada <strong data-role="new-status"></strong>?
                        <div class="small text-muted mt-2" data-role="final-warning" hidden>
                            <i class="bi bi-exclamation-triangle"></i> Ini keputusan muktamad dan tidak boleh diubah selepas ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-navy" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="status-form" class="btn btn-primary">Ya, Kemas Kini</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($choices)
        <script>
            (function () {
                const form = document.getElementById('status-form');
                const button = document.getElementById('status-submit');
                const remarks = document.getElementById('remarks');
                const hint = document.getElementById('remarks-hint');
                const modal = document.getElementById('modal-status');

                // Enable the button once a status is chosen; remarks become required for some statuses
                function update() {
                    const chosen = form.querySelector('input[name="status"]:checked');
                    button.disabled = !chosen;
                    const needs = chosen && chosen.dataset.needsRemarks === '1';
                    remarks.required = needs;
                    hint.textContent = needs ? '(wajib)' : '(pilihan)';
                    hint.classList.toggle('text-danger', needs);
                }
                form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', update); });
                update();

                // Fill in the confirmation pop-up; stop if a required remark is empty
                modal.addEventListener('show.bs.modal', function (event) {
                    if (!form.reportValidity()) { event.preventDefault(); return; }
                    const chosen = form.querySelector('input[name="status"]:checked');
                    modal.querySelector('[data-role="new-status"]').textContent = chosen.dataset.label;
                    modal.querySelector('[data-role="final-warning"]').hidden = !['approved', 'rejected'].includes(chosen.value);
                });
            })();
        </script>
    @endif
@endpush