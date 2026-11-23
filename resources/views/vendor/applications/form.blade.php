@extends('layouts.app')

@php
    $isNew = ! $application->exists;
    $selected = old('permit_type', $application->permit_type?->value);
    $maxKb = config('speed.upload.max_kb');
    $accept = collect(config('speed.upload.mimes'))->map(fn ($m) => '.' . $m)->implode(',');
    $permitIcons = [
        'kebenaran_merancang' => 'bi-map',
        'pelan_bangunan' => 'bi-building',
        'ubahsuai_rumah' => 'bi-house-gear',
    ];
    $permitHints = [
        'kebenaran_merancang' => 'Kebenaran merancang untuk pemajuan tanah',
        'pelan_bangunan' => 'Kelulusan pelan bangunan baharu',
        'ubahsuai_rumah' => 'Ubahsuai atau tambahan pada rumah sedia ada',
    ];
@endphp

@section('title', $isNew ? 'Permohonan Baharu' : 'Kemas Kini Draf')

@section('content')
    <div class="mb-4">
        <a href="{{ route('vendor.applications.index') }}" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> Permohonan Saya
        </a>
        <h1 class="h3 mb-0 mt-1">{{ $isNew ? 'Permohonan Baharu' : 'Kemas Kini Draf' }}</h1>
        <p class="text-muted mb-0">
            Medan bertanda <span class="text-danger">*</span> wajib diisi sebelum permohonan dihantar.
            Anda boleh <strong>Simpan Draf</strong> pada bila-bila masa dan sambung kemudian.
        </p>
    </div>

    <form method="POST" enctype="multipart/form-data" id="application-form"
          action="{{ $isNew ? route('vendor.applications.store') : route('vendor.applications.update', $application) }}">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        {{-- 1. Permit type --}}
        <div class="card mb-3">
            <div class="card-header">1. Jenis Permit <span class="text-danger">*</span></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach (\App\Enums\PermitType::cases() as $permit)
                        <div class="col-md-4">
                            <input type="radio" class="btn-check" name="permit_type" id="permit-{{ $permit->value }}"
                                   value="{{ $permit->value }}" autocomplete="off" @checked($selected === $permit->value)>
                            <label class="ss-choice w-100 h-100" for="permit-{{ $permit->value }}">
                                <i class="bi {{ $permitIcons[$permit->value] }}"></i>
                                <span>
                                    <strong>{{ $permit->label() }}</strong>
                                    <small>{{ $permitHints[$permit->value] }}</small>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2. Project details --}}
        <div class="card mb-3">
            <div class="card-header">2. Maklumat Projek</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="project_title" class="form-label">Tajuk Projek <span class="text-danger">*</span></label>
                    <input type="text" name="project_title" id="project_title" maxlength="255"
                           value="{{ old('project_title', $application->project_title) }}"
                           class="form-control @error('project_title') is-invalid @enderror"
                           placeholder="Contoh: Cadangan membina sebuah rumah kedai 2 tingkat">
                    @error('project_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="project_description" class="form-label">Keterangan Projek <span class="text-danger">*</span></label>
                    <textarea name="project_description" id="project_description" rows="4" maxlength="2000"
                              class="form-control @error('project_description') is-invalid @enderror"
                              placeholder="Terangkan skop kerja, keluasan, bilangan tingkat dan sebagainya">{{ old('project_description', $application->project_description) }}</textarea>
                    @error('project_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="site_address" class="form-label">Alamat Tapak <span class="text-danger">*</span></label>
                    <textarea name="site_address" id="site_address" rows="2" maxlength="500"
                              class="form-control @error('site_address') is-invalid @enderror"
                              placeholder="No. lot, jalan, mukim, daerah">{{ old('site_address', $application->site_address) }}</textarea>
                    @error('site_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="latitude" class="form-label">Latitud <span class="text-muted small">(pilihan)</span></label>
                        <input type="text" inputmode="decimal" name="latitude" id="latitude"
                               value="{{ old('latitude', $application->latitude) }}"
                               class="form-control @error('latitude') is-invalid @enderror" placeholder="Contoh: 3.3391">
                        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="longitude" class="form-label">Longitud <span class="text-muted small">(pilihan)</span></label>
                        <input type="text" inputmode="decimal" name="longitude" id="longitude"
                               value="{{ old('longitude', $application->longitude) }}"
                               class="form-control @error('longitude') is-invalid @enderror" placeholder="Contoh: 101.2453">
                        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-text">Koordinat boleh disalin dari Google Maps (klik kanan pada lokasi tapak).</div>
            </div>
        </div>

        {{-- 3. Documents: one group per permit type, only the selected group is shown --}}
        <div class="card mb-3">
            <div class="card-header">3. Dokumen Sokongan <span class="text-danger">*</span></div>
            <div class="card-body">
                <p class="small text-muted">
                    Format PDF, JPG atau PNG. Saiz maksimum {{ $maxKb / 1024 }} MB setiap fail.
                    Semua dokumen wajib dimuat naik sebelum permohonan boleh dihantar.
                </p>
                @error('documents')
                    <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> {{ $message }}</div>
                @enderror

                <p class="small text-muted d-none" id="no-permit-msg">Sila pilih jenis permit dahulu.</p>

                @foreach (\App\Enums\PermitType::cases() as $permit)
                    <div class="doc-group" data-permit="{{ $permit->value }}" @if ($selected !== $permit->value) hidden @endif>
                        @foreach ($permit->requiredDocuments() as $type => $label)
                            @php $existing = $application->exists ? $application->documentFor($type) : null; @endphp
                            <div class="border rounded-3 p-3 mb-2">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                    <label for="doc-{{ $permit->value }}-{{ $type }}" class="fw-semibold">
                                        {{ $label }} <span class="text-danger">*</span>
                                    </label>
                                    @if ($existing)
                                        <span class="small text-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <a href="{{ route('vendor.applications.document', [$application, $existing]) }}"
                                               target="_blank" rel="noopener">{{ $existing->file_name }}</a>
                                        </span>
                                    @else
                                        <span class="small text-muted"><i class="bi bi-circle"></i> Belum dimuat naik</span>
                                    @endif
                                </div>
                                <input type="file" name="documents[{{ $type }}]" id="doc-{{ $permit->value }}-{{ $type }}"
                                       accept="{{ $accept }}" data-max-bytes="{{ $maxKb * 1024 }}"
                                       class="form-control form-control-sm mt-2 @error('documents.' . $type) is-invalid @enderror"
                                       @disabled($selected !== $permit->value)>
                                @error('documents.' . $type) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="small text-danger mt-1 size-msg" hidden>Saiz fail melebihi {{ $maxKb / 1024 }} MB. Sila pilih fail yang lebih kecil.</div>
                                @if ($existing)
                                    <div class="form-text">Pilih fail baharu hanya jika mahu menggantikan fail di atas.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @unless ($isNew)
                    <div class="form-text"><i class="bi bi-info-circle"></i>
                        Jika anda menukar jenis permit, dokumen yang tidak diperlukan untuk permit baharu akan dipadam apabila disimpan.
                    </div>
                @endunless
            </div>
        </div>

        {{-- 4. Declaration + buttons --}}
        <div class="card mb-3">
            <div class="card-header">4. Perakuan</div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input @error('declaration') is-invalid @enderror" type="checkbox"
                           name="declaration" id="declaration" value="1" @checked(old('declaration'))>
                    <label class="form-check-label" for="declaration">
                        Saya mengaku bahawa semua maklumat dan dokumen yang diberikan adalah benar dan tepat.
                        <span class="text-muted small d-block">Wajib ditanda hanya semasa menghantar permohonan.</span>
                    </label>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-navy">
                    <i class="bi bi-save"></i> Simpan Draf
                </button>
                <button type="submit" name="action" value="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> Hantar Permohonan
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const radios = document.querySelectorAll('input[name="permit_type"]');
            const groups = document.querySelectorAll('.doc-group');
            const noPermitMsg = document.getElementById('no-permit-msg');

            // Show only the documents for the chosen permit type.
            // Hidden inputs are disabled so they are not sent with the form.
            function showGroup() {
                const checked = document.querySelector('input[name="permit_type"]:checked');
                const value = checked ? checked.value : null;
                groups.forEach(function (group) {
                    const active = group.dataset.permit === value;
                    group.hidden = !active;
                    group.querySelectorAll('input[type="file"]').forEach(function (input) {
                        input.disabled = !active;
                    });
                });
                noPermitMsg.classList.toggle('d-none', value !== null);
            }

            radios.forEach(function (radio) { radio.addEventListener('change', showGroup); });
            showGroup();

            // Stop files that are too big before uploading (saves waiting for a slow upload)
            document.querySelectorAll('input[type="file"][data-max-bytes]').forEach(function (input) {
                input.addEventListener('change', function () {
                    const tooBig = input.files.length && input.files[0].size > Number(input.dataset.maxBytes);
                    input.classList.toggle('is-invalid', tooBig);
                    input.parentElement.querySelector('.size-msg').hidden = !tooBig;
                    if (tooBig) input.value = '';
                });
            });
        })();
    </script>
@endpush