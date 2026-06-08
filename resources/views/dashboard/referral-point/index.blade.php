@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    @if( auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Referral Points
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('referral') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Order ID</label>
                    <input type="text" name="order_id" class="form-control" placeholder="Search order ID..." value="{{ request('order_id') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Brand Name</label>
                    <select name="brand_name" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand_name') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Date Range</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('referral') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Referral Points</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ number_format($totalPoints) }}</h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-coins me-1"></i>Earned</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Referral Points History</h5>
                <small class="text-muted">Track your referral earnings from voucher purchases.</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('referral.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.referral-point.referral-table')
        </div>
    </div>
    @endif
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
<style>
    .bg-soft-success {
        background-color: rgba(40, 167, 69, 0.1);
    }
</style>
@endpush
@endsection
