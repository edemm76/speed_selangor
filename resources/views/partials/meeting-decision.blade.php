{{--
    Committee meeting decision card. Used on the officer's and the applicant's application pages.
    Needs: $decision (MeetingDecision), $forOfficer (true shows the minutes PDF + who recorded it)
--}}
<div class="card mb-3 border-{{ $decision->result->color() }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people"></i> Keputusan Mesyuarat</span>
        {!! $decision->result->badge() !!}
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4 text-muted fw-normal">Mesyuarat</dt>
            <dd class="col-sm-8 fw-semibold">{{ $decision->meeting_title }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Tarikh Mesyuarat</dt>
            <dd class="col-sm-8">{{ $decision->meeting_date->format('d/m/Y') }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Jabatan</dt>
            <dd class="col-sm-8">{{ $decision->department }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Disemak Oleh</dt>
            <dd class="col-sm-8" style="white-space: pre-line">{{ $decision->checked_by }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Diputuskan Oleh</dt>
            <dd class="col-sm-8">{{ $decision->approver_name }}<div class="small text-muted">{{ $decision->approver_position }}</div></dd>

            <dt class="col-sm-4 text-muted fw-normal">Keputusan</dt>
            <dd class="col-sm-8">{!! $decision->result->badge() !!}</dd>

            @if ($decision->amendments)
                <dt class="col-sm-4 text-muted fw-normal">Pindaan Diperlukan</dt>
                <dd class="col-sm-8">
                    <div class="alert alert-warning py-2 mb-0" style="white-space: pre-line">{{ $decision->amendments }}</div>
                </dd>
            @endif

            @if ($decision->reason)
                <dt class="col-sm-4 text-muted fw-normal">Sebab Penolakan</dt>
                <dd class="col-sm-8">
                    <div class="alert alert-secondary py-2 mb-0" style="white-space: pre-line">{{ $decision->reason }}</div>
                </dd>
            @endif

            <dt class="col-sm-4 text-muted fw-normal">Ringkasan Minit</dt>
            <dd class="col-sm-8 {{ $forOfficer ? '' : 'mb-0' }}" style="white-space: pre-line">{{ $decision->minutes_summary }}</dd>

            @if ($forOfficer)
                <dt class="col-sm-4 text-muted fw-normal">Fail Minit</dt>
                <dd class="col-sm-8">
                    @if ($decision->minutes_file_path)
                        <a href="{{ route('admin.meetings.minutes', $decision->application_id) }}" target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf"></i> {{ $decision->minutes_file_name }}
                        </a>
                        <span class="small text-muted">(pegawai sahaja)</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </dd>

                <dt class="col-sm-4 text-muted fw-normal">Direkod Oleh</dt>
                <dd class="col-sm-8 mb-0 small text-muted">
                    {{ $decision->recordedBy?->name ?? '—' }} &middot; {{ $decision->created_at?->format('d/m/Y, h:i A') }}
                </dd>
            @endif
        </dl>
    </div>
</div>
