@extends('layouts.app')

@section('title', 'Utama')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <h1 class="h3 mb-1">{{ config('speed.full_name') }}</h1>
                    <p class="text-muted">{{ config('speed.council') }}</p>

                    <p>Sasaran pemprosesan permohonan:
                        <strong>{{ config('speed.target_days') }} hari</strong>
                    </p>

                    {{-- Temporary check that the enums work. You can delete this block later. --}}
                    <h2 class="h6 mt-4">Jenis Permit</h2>
                    <ul>
                        @foreach (\App\Enums\PermitType::cases() as $type)
                            <li>{{ $type->label() }}
                                <small class="text-muted">({{ count($type->requiredDocuments()) }} dokumen wajib)</small>
                            </li>
                        @endforeach
                    </ul>

                    <h2 class="h6 mt-4">Status Permohonan</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (\App\Enums\ApplicationStatus::cases() as $status)
                            {!! $status->badge() !!}
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection