@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Bank Report</h2>
            <p class="text-muted">Summary of all transactions processed through banks.</p>
        </div>
        <div>
            <a href="{{ route('reports.bank.export', request()->all()) }}" class="btn btn-success px-4 fw-bold shadow-sm">
                <i class="fas fa-file-csv me-2"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">
            @include('dashboard.reports.partials.bank-table')
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $bank_data->links() }}
        </div>
    </div>
</div>
@endsection
