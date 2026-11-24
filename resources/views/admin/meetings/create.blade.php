@extends('layouts.app')

@php
    $vendor = $application->vendor;
    $verification = $application->verifications->firstWhere('decision', \App\Enums\ApplicationStatus::Verified);
    $selected = old('result');
@endphp

@section('title', 'Keputusan Mesyuarat ' . $application->reference_no)

@push('styles')
    <style>
        .ss-result { cursor: pointer; }
        .ss-result:has(input:checked) { border-color: var(--ss-orange) !important; background: #fff5f1; }
    </style>
@endpush

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.applications.show', $application) }}" class="small text-decoration-none">
            <i class="bi bi-arrow-left"></i> {{ $application->reference_no }}
        </a>
        <h1 class="h3 mb-0 mt-1">Rekod Keputusan Mesyuarat</h1>
        <p class="text-muted mb-0">{{ $application->reference_no }} &middot; {{ $application->project_title }} &middot; {{ $vendor->displayName() }}</p>
    </div>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        Permohonan ini telah disahkan
        @if ($verification)
            oleh <strong>{{ $verification->admin?->name }}</strong> pada {{ $verification->created_at->format('d/m/Y') }}
        @endif
        dan dibawa ke mesyuarat untuk pertimbangan. Rekod keputusan mesyuarat di bawah.
        Pemohon akan dimaklumkan secara automatik.
    </div>

    <form method="POST" action="{{ route('admin.meetings.store', $application) }}" enctype="multipart/form-data" id="meeting-form">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                {{-- 1. Meeting --}}
                <div class="card mb-3">
                    <div class="card-header">1. Maklumat Mesyuarat</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="meeting_title" class="form-label">Nama / Bil. Mesyuarat <span class="text-danger">*</span></label>
                                <input type="text" name="meeting_title" id="meeting_title" maxlength="255"
                                       value="{{ old('meeting_title') }}" placeholder="Contoh: {{ config('speed.meeting.title_example') }}"
                                       class="form-control @error('meeting_title') is-invalid @enderror">
                                @error('meeting_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="meeting_date" class="form-label">Tarikh Mesyuarat <span class="text-danger">*</span></label>
                                <input type="date" name="meeting_date" id="meeting_date" max="{{ now()->toDateString() }}"
                                       min="{{ $application->submitted_at->toDateString() }}"
                                       value="{{ old('meeting_date', now()->toDateString()) }}"
                                       class="form-control @error('meeting_date') is-invalid @enderror">
                                @error('meeting_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label for="department" class="form-label">Jabatan yang Mengendalikan Mesyuarat <span class="text-danger">*</span></label>
                                <input type="text" name="department" id="department" maxlength="255"
                                       value="{{ old('department', config('speed.meeting.department')) }}"
                                       class="form-control @error('department') is-invalid @enderror">
                                @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. People --}}
                <div class="card mb-3">
                    <div class="card-header">2. Pegawai Terlibat</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="checked_by" class="form-label">Disemak Oleh (nama dan jawatan) <span class="text-danger">*</span></label>
                            <textarea name="checked_by" id="checked_by" rows="2" maxlength="1000"
                                      class="form-control @error('checked_by') is-invalid @enderror"
                                      placeholder="Contoh: En. Ahmad bin Ali (Penolong Pegawai Perancang Bandar)">{{ old('checked_by') }}</textarea>
                            <div class="form-text">Pegawai yang menyemak dokumen permohonan. Asingkan dengan koma jika lebih daripada seorang.</div>
                            @error('checked_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="approver_name" class="form-label">Diputuskan / Diluluskan Oleh <span class="text-danger">*</span></label>
                                <input type="text" name="approver_name" id="approver_name" maxlength="255"
                                       value="{{ old('approver_name') }}" placeholder="Contoh: Tuan Haji Kamal bin Hassan"
                                       class="form-control @error('approver_name') is-invalid @enderror">
                                @error('approver_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="approver_position" class="form-label">Jawatan <span class="text-danger">*</span></label>
                                <input type="text" name="approver_position" id="approver_position" maxlength="255"
                                       value="{{ old('approver_position') }}" placeholder="Contoh: Pengerusi Jawatankuasa OSC"
                                       class="form-control @error('approver_position') is-invalid @enderror">
                                @error('approver_position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Minutes --}}
                <div class="card mb-3">
                    <div class="card-header">3. Minit Mesyuarat</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="minutes_summary" class="form-label">Ringkasan Minit <span class="text-danger">*</span></label>
                            <textarea name="minutes_summary" id="minutes_summary" rows="4" maxlength="3000"
                                      class="form-control @error('minutes_summary') is-invalid @enderror"
                                      placeholder="Ringkasan perbincangan mesyuarat berkaitan permohonan ini">{{ old('minutes_summary') }}</textarea>
                            <div class="form-text">Ringkasan ini akan dipaparkan kepada pemohon.</div>
                            @error('minutes_summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <label for="minutes_file" class="form-label">Fail Minit Mesyuarat (PDF) <span class="text-danger">*</span></label>
                        <input type="file" name="minutes_file" id="minutes_file" accept=".pdf"
                               class="form-control @error('minutes_file') is-invalid @enderror">
                        <div class="form-text"><i class="bi bi-lock"></i> Fail minit hanya boleh dilihat oleh pegawai, tidak dipaparkan kepada pemohon.</div>
                        @error('minutes_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- 4. Result --}}
                <div class="card mb-3">
                    <div class="card-header">4. Keputusan <span class="text-danger">*</span></div>
                    <div class="card-body">
                        <div class="d-grid gap-2 mb-3">
                            @foreach (\App\Enums\MeetingResult::cases() as $result)
                                <label class="ss-result border rounded-3 p-2 d-flex gap-2 align-items-center">
                                    <input type="radio" name="result" value="{{ $result->value }}" class="form-check-input mt-0"
                                           @checked($selected === $result->value)>
                                    {!! $result->badge() !!}
                                </label>
                            @endforeach
                        </div>
                        @error('result') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                        <div id="box-amendments" @if ($selected !== 'lulus_pindaan') hidden @endif>
                            <label for="amendments" class="form-label">Pindaan yang Perlu Dibuat <span class="text-danger">*</span></label>
                            <textarea name="amendments" id="amendments" rows="4" maxlength="2000"
                                      class="form-control @error('amendments') is-invalid @enderror"
                                      placeholder="Contoh: Lebarkan jalan masuk kepada 12 meter.">{{ old('amendments') }}</textarea>
                            @error('amendments') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div id="box-reason" @if ($selected !== 'ditolak') hidden @endif>
                            <label for="reason" class="form-label">Sebab Penolakan <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" rows="4" maxlength="2000"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Wajib dinyatakan supaya pemohon tahu mengapa permohonan ditolak.">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input @error('confirm') is-invalid @enderror" type="checkbox" name="confirm" id="confirm" value="1" @checked(old('confirm'))>
                            <label class="form-check-label small" for="confirm">
                                Saya mengesahkan maklumat keputusan mesyuarat ini adalah tepat. Keputusan adalah muktamad dan pemohon akan dimaklumkan.
                            </label>
                        </div>
                        @error('confirm') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-save"></i> Simpan Keputusan
                        </button>
                        <a href="{{ route('admin.applications.show', $application) }}" class="btn btn-outline-navy w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Show the amendments box for "Lulus dengan Pindaan" and the reason box for "Ditolak"
        (function () {
            const boxA = document.getElementById('box-amendments');
            const boxR = document.getElementById('box-reason');
            function update() {
                const chosen = document.querySelector('input[name="result"]:checked');
                const value = chosen ? chosen.value : '';
                boxA.hidden = value !== 'lulus_pindaan';
                boxR.hidden = value !== 'ditolak';
                document.getElementById('amendments').required = value === 'lulus_pindaan';
                document.getElementById('reason').required = value === 'ditolak';
            }
            document.querySelectorAll('input[name="result"]').forEach(function (r) { r.addEventListener('change', update); });
            update();
        })();
    </script>
@endpush
