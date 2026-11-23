@extends('layouts.app')

@section('title', 'Papan Pemuka Pentadbir')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-0">Papan Pemuka</h1>
        <p class="text-muted mb-0">Selamat datang, {{ auth('admin')->user()->name }}</p>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Jumlah Permohonan <span class="stat-dot bg-dark"></span>
                    </div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Sedang Diproses <span class="stat-dot bg-info"></span>
                    </div>
                    <div class="stat-value">{{ $stats['in_progress'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Melebihi {{ config('speed.target_days') }} Hari <span class="stat-dot bg-danger"></span>
                    </div>
                    <div class="stat-value text-danger">{{ $stats['overdue'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.vendors.index') }}" class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Jumlah Pemohon <span class="stat-dot bg-warning"></span>
                    </div>
                    <div class="stat-value">{{ $stats['vendors'] }}</div>
                    @if ($stats['suspended_vendors'] > 0)
                        <div class="small text-danger">{{ $stats['suspended_vendors'] }} digantung</div>
                    @endif
                </div>
            </a>
        </div>
    </div>

    <p class="text-muted small mt-4">
        Papan pemuka penuh (carta dan senarai permohonan terkini) akan ditambah kemudian.
    </p>
@endsection