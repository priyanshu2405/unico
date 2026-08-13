@extends('layouts.master')
@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Sales
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('sales.index') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search Sale ID, Customer..." value="{{ request('search') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Date Wise Report (From - To)</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Buyer Type</label>
                    <select name="user_role" class="form-select">
                        <option value="">All Buyer Types</option>
                        <option value="student" {{ request('user_role') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="reseller_agent" {{ request('user_role') == 'reseller_agent' ? 'selected' : '' }}>Reseller Agent</option>
                        <option value="regular_agent" {{ request('user_role') == 'regular_agent' ? 'selected' : '' }}>Regular Agent</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Country</label>
                    <select name="user_country" class="form-select">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ request('user_country') == $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">State</label>
                    <select name="user_state" class="form-select">
                        <option value="">All States</option>
                        @foreach($states as $state)
                            <option value="{{ $state }}" {{ request('user_state') == $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">City</label>
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="">All Currencies</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}" {{ request('currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Brand Sale Report</label>
                    <select name="brand_name" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}" {{ request('brand_name') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Variant Sale Report</label>
                    <input type="text" name="voucher_variant" class="form-control" placeholder="Variant" value="{{ request('voucher_variant') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Voucher Type</label>
                    <select name="voucher_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($voucherTypes as $type)
                            <option value="{{ $type }}" {{ request('voucher_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">Sales Report</h5>
            </div>
            <div class="d-flex gap-2">

                <a href="#" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body" id="table-container">
            @include('dashboard.sales.sales-table')
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize CSV export link
        let initialUrl = window.location.href;
        let initialCsvUrl = "{{ route('sales.export') }}?" + (initialUrl.split('?')[1] || '');
        $('#csv-export-link').attr('href', initialCsvUrl);

        // Handle Filters
        $(document).on('submit', '#filter-form', function(e) {
            e.preventDefault();
            handleAjaxFilter(this, e);
        });
    });
</script>
@endpush

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .bg-primary-soft {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-success-soft {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-info-soft {
        background-color: rgba(13, 202, 240, 0.1);
    }

    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-danger-soft {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .bg-dark-soft {
        background-color: rgba(33, 37, 41, 0.1);
    }

    .btn-xs {
        padding: 1px 5px;
        font-size: 10px;
    }
</style>
@endsection