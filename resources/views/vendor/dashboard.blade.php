@extends('layouts.app')

@section('title', 'Papan Pemuka')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-0">Papan Pemuka</h1>
        <p class="text-muted mb-0">
            {{ $vendor->displayName() }}
            @if ($vendor->isCompany()) &middot; {{ $vendor->pic_name }} @endif
            &middot; {{ $vendor->applicant_type->label() }}
        </p>
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
                        Draf <span class="stat-dot bg-secondary"></span>
                    </div>
                    <div class="stat-value">{{ $stats['draft'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Tindakan Diperlukan <span class="stat-dot bg-danger"></span>
                    </div>
                    <div class="stat-value text-danger">{{ $stats['action_required'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between text-muted small">
                        Diluluskan <span class="stat-dot bg-success"></span>
                    </div>
                    <div class="stat-value text-success">{{ $stats['approved'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-4">
        Senarai permohonan terkini dan butang permohonan baharu akan ditambah kemudian.
    </p>
@endsection