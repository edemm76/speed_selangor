@extends('layouts.app')

@php
    $request = $application->verifications->firstWhere('decision', \App\Enums\ApplicationStatus::ActionRequired);
    $note = $request?->notes ?? '';
    $maxKb = config('speed.upload.max_kb');
    $accept = collect(config('speed.upload.mimes'))->map(fn ($m) => '.' . $m)->implode(',');
@endphp

@section('title', 'Hantar Semula ' . $application->reference_no)

@section('content')
    <div class="mb-4">
        <a href="{{ route('vendor.applications.show', $application) }}" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> {{ $application->reference_no }}
        </a>
        <h1 class="h3 mb-0 mt-1">Kemas Kini &amp; Hantar Semula</h1>
        <p class="text-muted mb-0">{{ $application->reference_no }} &middot; {{ $application->project_title }}</p>
    </div>

    {{-- What the officer asked for --}}
    <div class="alert alert-danger">
        <div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill"></i> Maklumat tambahan yang diminta oleh pegawai</div>
        <div class="mt-1">{{ $note ?: 'Sila semak dan kemas kini permohonan anda.' }}</div>
        @if ($request)
            <div class="small mt-1">{{ $request->admin?->name }} &middot; {{ $request->created_at->format('d/m/Y, h:i A') }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('vendor.applications.resubmit.store', $application) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Project details (permit type and title cannot be changed) --}}
        <div class="card mb-3">
            <div class="card-header">Maklumat Projek</div>
            <div class="card-body">
                <dl class="row mb-3">
                    <dt class="col-sm-3 text-muted fw-normal">Jenis Permit</dt>
                    <dd class="col-sm-9">{{ $application->permit_type->label() }}</dd>
                    <dt class="col-sm-3 text-muted fw-normal">Tajuk Projek</dt>
                    <dd class="col-sm-9 mb-0">{{ $application->project_title }}</dd>
                </dl>

                <div class="mb-3">
                    <label for="project_description" class="form-label">Keterangan Projek <span class="text-danger">*</span></label>
                    <textarea name="project_description" id="project_description" rows="4" maxlength="2000"
                              class="form-control @error('project_description') is-invalid @enderror">{{ old('project_description', $application->project_description) }}</textarea>
                    @error('project_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="site_address" class="form-label">Alamat Tapak <span class="text-danger">*</span></label>
                    <textarea name="site_address" id="site_address" rows="2" maxlength="500"
                              class="form-control @error('site_address') is-invalid @enderror">{{ old('site_address', $application->site_address) }}</textarea>
                    @error('site_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="latitude" class="form-label">Latitud <span class="text-muted small">(pilihan)</span></label>
                        <input type="text" inputmode="decimal" name="latitude" id="latitude"
                               value="{{ old('latitude', $application->latitude) }}"
                               class="form-control @error('latitude') is-invalid @enderror">
                        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="longitude" class="form-label">Longitud <span class="text-muted small">(pilihan)</span></label>
                        <input type="text" inputmode="decimal" name="longitude" id="longitude"
                               value="{{ old('longitude', $application->longitude) }}"
                               class="form-control @error('longitude') is-invalid @enderror">
                        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents: upload a new file only for those that need replacing --}}
        <div class="card mb-3">
            <div class="card-header">Dokumen Sokongan</div>
            <div class="card-body">
                <p class="small text-muted">
                    Muat naik fail baharu hanya bagi dokumen yang perlu diganti. Dokumen lain akan dikekalkan.
                    Format PDF, JPG atau PNG, maksimum {{ $maxKb / 1024 }} MB setiap fail.
                </p>
                @error('documents')
                    <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
                @foreach ($application->permit_type->requiredDocuments() as $type => $label)
                    @php
                        $doc = $application->documentFor($type);
                        $asked = $note !== '' && stripos($note, $label) !== false; // officer named this document
                    @endphp
                    <div class="border rounded-3 p-3 mb-2 {{ $asked ? 'border-danger' : '' }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <label for="doc-{{ $type }}" class="fw-semibold">
                                {{ $label }}
                                @if ($asked)
                                    <span class="badge bg-danger ms-1">Diminta oleh pegawai</span>
                                @endif
                            </label>
                            @if ($doc)
                                <span class="small">
                                    <i class="bi bi-file-earmark-text text-success"></i>
                                    <a href="{{ route('vendor.applications.document', [$application, $doc]) }}" target="_blank" rel="noopener">{{ $doc->file_name }}</a>
                                    @if ($application->isDocumentUpdated($doc))
                                        <span class="badge bg-info-subtle text-info-emphasis ms-1">Dikemas kini</span>
                                    @endif
                                </span>
                            @else
                                <span class="small text-danger"><i class="bi bi-x-circle"></i> Tiada fail</span>
                            @endif
                        </div>
                        <input type="file" name="documents[{{ $type }}]" id="doc-{{ $type }}" accept="{{ $accept }}"
                               data-max-bytes="{{ $maxKb * 1024 }}"
                               class="form-control form-control-sm mt-2 @error('documents.' . $type) is-invalid @enderror">
                        @error('documents.' . $type) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="small text-danger mt-1 size-msg" hidden>Saiz fail melebihi {{ $maxKb / 1024 }} MB. Sila pilih fail yang lebih kecil.</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Response + declaration --}}
        <div class="card mb-3">
            <div class="card-header">Maklum Balas kepada Pegawai</div>
            <div class="card-body">
                <label for="response" class="form-label">Catatan <span class="text-muted small">(pilihan)</span></label>
                <textarea name="response" id="response" rows="3" maxlength="1000"
                          class="form-control @error('response') is-invalid @enderror"
                          placeholder="Contoh: Salinan geran tanah telah diimbas semula dengan lebih jelas.">{{ old('response') }}</textarea>
                @error('response') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="form-check mt-3">
                    <input class="form-check-input @error('declaration') is-invalid @enderror" type="checkbox"
                           name="declaration" id="declaration" value="1" @checked(old('declaration'))>
                    <label class="form-check-label" for="declaration">
                        Saya mengaku bahawa semua maklumat dan dokumen yang dikemas kini adalah benar dan tepat.
                    </label>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('vendor.applications.show', $application) }}" class="btn btn-outline-navy">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Hantar Semula</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Stop files that are too big before uploading
        document.querySelectorAll('input[type="file"][data-max-bytes]').forEach(function (input) {
            input.addEventListener('change', function () {
                const tooBig = input.files.length && input.files[0].size > Number(input.dataset.maxBytes);
                input.classList.toggle('is-invalid', tooBig);
                input.parentElement.querySelector('.size-msg').hidden = !tooBig;
                if (tooBig) input.value = '';
            });
        });
    </script>
@endpush
