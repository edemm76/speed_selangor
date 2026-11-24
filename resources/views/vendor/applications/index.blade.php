@extends('layouts.app')

@section('title', 'Permohonan Saya')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Permohonan Saya</h1>
            <p class="text-muted mb-0">Semua permohonan permit anda, termasuk draf yang belum dihantar.</p>
        </div>
        <a href="{{ route('vendor.applications.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Permohonan Baharu
        </a>
    </div>

    <div class="card">
        @if ($applications->isEmpty())
            <div class="card-body text-center py-5">
                <i class="bi bi-folder2-open display-6 text-muted"></i>
                <p class="mt-2 mb-3 text-muted">Anda belum mempunyai sebarang permohonan.</p>
                <a href="{{ route('vendor.applications.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Buat Permohonan Pertama
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Rujukan</th>
                            <th>Tajuk Projek</th>
                            <th>Jenis Permit</th>
                            <th>Status</th>
                            <th>Tarikh</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $app)
                            @php $isDraft = $app->current_status === \App\Enums\ApplicationStatus::Draft; @endphp
                            <tr>
                                <td class="fw-semibold text-nowrap">
                                    @if ($isDraft)
                                        —
                                    @else
                                        <a href="{{ route('vendor.applications.show', $app) }}" class="text-decoration-none">{{ $app->reference_no }}</a>
                                    @endif
                                </td>
                                <td>
                                    {{ $app->project_title }}
                                    @if ($app->isOverdue())
                                        <div class="small text-danger">Melebihi {{ config('speed.target_days') }} hari</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $app->permit_type->label() }}</td>
                                <td>{!! $app->current_status->badge() !!}</td>
                                <td class="text-nowrap small">
                                    @if ($app->submitted_at)
                                        Dihantar {{ $app->submitted_at->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">Dikemas kini {{ $app->updated_at->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($isDraft)
                                        <a href="{{ route('vendor.applications.edit', $app) }}" class="btn btn-sm btn-outline-navy">
                                            <i class="bi bi-pencil"></i> Sambung
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#modal-delete"
                                                data-action="{{ route('vendor.applications.destroy', $app) }}"
                                                data-title="{{ $app->project_title }}"
                                                aria-label="Padam draf {{ $app->project_title }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('vendor.applications.show', $app) }}" class="btn btn-sm btn-outline-navy">
                                            Lihat <i class="bi bi-chevron-right"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">
        {{ $applications->links() }}
    </div>

    {{-- Confirm delete draft (one modal, the form action is set by the button that opened it) --}}
    <div class="modal fade" id="modal-delete" tabindex="-1" aria-labelledby="modal-delete-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="modal-delete-title">Padam draf ini?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        Draf <strong data-role="title"></strong> dan semua dokumen yang telah dimuat naik akan dipadam.
                        Tindakan ini tidak boleh dibatalkan.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-navy" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Ya, Padam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('modal-delete').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            this.querySelector('form').action = button.dataset.action;
            this.querySelector('[data-role="title"]').textContent = button.dataset.title;
        });
    </script>
@endpush