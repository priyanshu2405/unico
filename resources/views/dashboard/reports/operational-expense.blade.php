@extends('layouts.master')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">Operational Expense Report</h2>
            <p class="text-muted">Breakdown of system and business operational costs.</p>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body py-5 text-center">
            <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
            <h4>Coming Soon</h4>
            <p class="text-muted">This report is currently being developed.</p>
            <a href="{{ route('reports.index') }}" class="btn btn-primary">Back to Reports</a>
        </div>
    </div>
</div>
@endsection
