@extends('layouts.master')
@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Refunds
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('refunds.index') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Email, Order ID..." value="{{ request('search') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">User ID</label>
                    <input type="text" name="user_id" class="form-control" placeholder="Search by User ID..." value="{{ request('user_id') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Country</label>
                    <select name="country" class="form-select">
                        <option value="all" {{ request('country') == 'all' ? 'selected' : '' }}>
                            All Countries
                        </option>
                        @foreach(
                        \App\Models\RefundRequest::whereHas('user', function($q){
                        $q->whereNotNull('country')
                        ->where('country', '!=', '');
                        })
                        ->with('user')
                        ->get()
                        ->pluck('user.country')
                        ->unique()
                        ->sort()
                        as $country
                        )
                        <option value="{{ $country }}"
                            {{ request('country') == $country ? 'selected' : '' }}>
                            {{ $country }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved (Refunded)</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected (Not Refunded)</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('refunds.index') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px;">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Refund Requests</h5>
                <small class="text-muted">{{ $refunds->total() }} Requests Found</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('refunds.admin_export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.refunds.refunds-table')
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle Filter Form
        $(document).on('submit', '#filter-form', function(e) {
            handleAjaxFilter(this, e);
        });
    });
</script>
@endpush
@endsection
