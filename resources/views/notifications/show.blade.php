@extends('layouts.app')

@php
    $notification = $item->notification;
    [$icon, $color] = $notification->icon();
    $application = $item->application;
    $appUrl = $application
        ? route($role === 'admin' ? 'admin.applications.show' : 'vendor.applications.show', $application)
        : null;
@endphp

@section('title', $notification->title)

@section('content')
    <a href="{{ route($role . '.notifications.index') }}" class="small text-decoration-none d-inline-block mb-3">
        <i class="bi bi-arrow-left"></i> Notifikasi
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <div class="d-flex gap-3 align-items-start mb-3">
                        <span class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center bg-{{ $color }}-subtle text-{{ $color }}-emphasis"
                              style="width: 52px; height: 52px;">
                            <i class="bi {{ $icon }} fs-4"></i>
                        </span>
                        <div>
                            <h1 class="h4 mb-1">{{ $notification->title }}</h1>
                            <div class="small text-muted">
                                <i class="bi bi-clock"></i> {{ $item->created_at->format('d/m/Y, h:i A') }}
                                ({{ $item->created_at->locale('ms')->diffForHumans() }})
                            </div>
                        </div>
                    </div>

                    {{-- Full message (keeps line breaks, e.g. before "Catatan pegawai") --}}
                    <div class="fs-6" style="white-space: pre-line">{{ $notification->message }}</div>
                </div>

                @if ($application)
                    <div class="card-footer bg-white p-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <div>
                                <div class="small text-muted">Permohonan berkaitan</div>
                                <div class="fw-semibold">{{ $application->reference_no }} &middot; {{ $application->project_title }}</div>
                                <div class="mt-1">Status semasa: {!! $application->current_status->badge() !!}</div>
                            </div>
                            <a href="{{ $appUrl }}" class="btn btn-primary text-nowrap">
                                Lihat Permohonan <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
