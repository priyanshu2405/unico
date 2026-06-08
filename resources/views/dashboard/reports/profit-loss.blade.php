@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">


        <!-- Main Content -->
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-0">Gross Profit & Loss</h3>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.profit-loss.export') }}" id="csv-export-link" class="btn btn-success btn-sm">
                        <i class="fas fa-file-csv me-1"></i> CSV Download
                    </a>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0" id="table-container">
                    @include('dashboard.reports.partials.profit-loss-table')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Offcanvas Filter --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">Filter Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="filter-form" action="{{ route('reports.profit-loss') }}" method="GET">
            <div class="mb-4">
                <label class="form-label fw-bold">SKU ID</label>
                <input type="text" name="sku_id" class="form-control" placeholder="Search SKU" value="{{ request('sku_id') }}">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Voucher Type</label>
                <select name="voucher_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Online" {{ request('voucher_type') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Center" {{ request('voucher_type') == 'Center' ? 'selected' : '' }}>Center</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Country</label>
                <select name="country_region" class="form-select">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ request('country_region') == $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Period Type</label>
                <select name="period_type" class="form-select">
                    <option value="weekly" {{ request('period_type') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ request('period_type', 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Select Date</label>
                <input type="date" name="filter_date" class="form-control" value="{{ request('filter_date', date('Y-m-d')) }}">
                <small class="text-muted">Report will be generated for the month or week of this date.</small>
            </div>
            <div class="d-grid gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('reports.profit-loss') }}" class="btn btn-light">Reset All</a>
            </div>
        </form>
    </div>
</div>

<style>
    .hover-bg:hover {
        background-color: #f8f9fa;
    }

    .nav-link {
        font-size: 13px;
        line-height: 1.2;
    }

    table th {
        font-size: 12px;
    }

    table td {
        font-size: 12px;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function() {
        function updateTable(url) {
            $.ajax({
                url: url,
                success: function(data) {
                    $('#table-container').html(data);
                    window.history.pushState({}, '', url);
                    let csvUrl = "{{ route('reports.profit-loss.export') }}?" + (url.split('?')[1] || '');
                    $('#csv-export-link').attr('href', csvUrl);
                }
            });
        }

        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            updateTable($(this).attr('href'));
        });

        $(document).on('submit', '#filter-form', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            updateTable(url);
            bootstrap.Offcanvas.getInstance(document.getElementById('filterOffcanvas')).hide();
        });
    });
</script>
@endpush
@endsection