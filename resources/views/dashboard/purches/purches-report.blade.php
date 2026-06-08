@extends('layouts.master')

@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Purchases
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('purches.user.report') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Date Wise Report (From - To)</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Voucher Purchases Summary (Currency)</label>
                    <select name="currency" class="form-select">
                        <option value="">All Currencies</option>
                        <option value="USD" {{ request('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="GBP" {{ request('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Brand Purchase Report</label>
                    <input type="text" name="brand_name" class="form-control" placeholder="Brand Name" value="{{ request('brand_name') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Variant Purchase Report</label>
                    <input type="text" name="voucher_variant" class="form-control" placeholder="Variant" value="{{ request('voucher_variant') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Exam Type (Online/Center)</label>
                    <select name="voucher_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Online" {{ request('voucher_type') == 'Online' ? 'selected' : '' }}>Online</option>
                        <option value="Center" {{ request('voucher_type') == 'Center' ? 'selected' : '' }}>Center</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Supplier Region (UK/USA)</label>
                    <select name="country_region" class="form-select">
                        <option value="">All Regions</option>
                        <option value="UK" {{ request('country_region') == 'UK' ? 'selected' : '' }}>UK</option>
                        <option value="USA" {{ request('country_region') == 'USA' ? 'selected' : '' }}>USA</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('purches.user.report') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">Purchase Report</h5>
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
            @include('dashboard.purches.partials.purches-table')
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        function updateTable(url) {
            $.ajax({
                url: url,
                success: function(data) {
                    $('#table-container').html(data);
                    window.history.pushState({}, '', url);

                    // Update CSV export link
                    let csvUrl = "{{ route('purches.export') }}?" + (url.split('?')[1] || '');
                    $('#csv-export-link').attr('href', csvUrl);
                }
            });
        }

        // Initialize CSV export link
        let initialUrl = window.location.href;
        let initialCsvUrl = "{{ route('purches.export') }}?" + (initialUrl.split('?')[1] || '');
        $('#csv-export-link').attr('href', initialCsvUrl);

        // Handle Pagination
        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            updateTable($(this).attr('href'));
        });

        // Handle Filters
        $(document).on('submit', '#filter-form', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            updateTable(url);

            var offcanvasElement = document.getElementById('filterOffcanvas');
            var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (offcanvas) offcanvas.hide();
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