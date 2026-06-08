@extends('layouts.master')

@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Users
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('support.team') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Name, Email, ID..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Date Range</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Country</label>
                    <select name="country" class="form-select">
                        <option value="all" {{ request('country') == 'all' ? 'selected' : '' }}>All Countries</option>
                        @foreach(\App\Models\User::where('account_type', 'support_team')->whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country') as $name)
                            <option value="{{ $name }}" {{ request('country') == $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">State</label>
                    <select name="state" class="form-select">
                        <option value="all" {{ request('state') == 'all' ? 'selected' : '' }}>All States</option>
                        @foreach(\App\Models\User::where('account_type', 'support_team')->whereNotNull('state')->where('state', '!=', '')->distinct()->orderBy('state')->pluck('state') as $name)
                            <option value="{{ $name }}" {{ request('state') == $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Frozen</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Active Login Time</label>
                    <div class="d-flex gap-2">
                        <input type="time" name="from_time" class="form-control" value="{{ request('from_time') }}" title="From Time">
                        <input type="time" name="to_time" class="form-control" value="{{ request('to_time') }}" title="To Time">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Rating</label>
                    <select name="rating" class="form-select">
                        <option value="all" {{ request('rating') == 'all' ? 'selected' : '' }}>All Ratings</option>
                        <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
                        <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
                        <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
                        <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
                        <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('support.team') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Support Team Management</h5>
                <small class="text-muted total-count">{{ $users->total() }} Members Found</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('support.team.export', request()->all()) }}" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_create_user)
                <a href="{{ route('users.create') }}?role=support_team" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Add
                </a>
                @endif
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body" id="table-container">
            @include('dashboard.partials.support-table')
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
                    // Update URL without reload
                    window.history.pushState({}, '', url);

                    // Update CSV export link
                    let csvUrl = "{{ route('support.team.export') }}?" + (url.split('?')[1] || '');
                    $('#csv-export-link').attr('href', csvUrl);
                }
            });
        }

        // Handle Pagination
        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            updateTable($(this).attr('href'));
        });

        // Handle Filters & Search
        $(document).on('submit', '#filter-form', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            updateTable(url);

            // Close offcanvas
            var offcanvasElement = document.getElementById('filterOffcanvas');
            var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (offcanvas) offcanvas.hide();
        });

        $('.btn-group a').on('click', function(e) {
            e.preventDefault();
            $('.btn-group a').removeClass('active');
            $(this).addClass('active');
            updateTable($(this).attr('href'));
        });

        // Handle AJAX Actions (Suspend, Delete)
        $(document).on('submit', '.ajax-action', function(e) {
            e.preventDefault();
            let form = $(this);
            let url = form.attr('action');
            let method = form.find('input[name="_method"]').val() || 'POST';

            if (method === 'DELETE' && !confirm('Are you sure you want to delete this?')) {
                return;
            }

            $.ajax({
                url: url,
                method: method,
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success);
                        // Refresh table with current filters
                        updateTable(window.location.href);
                    }
                },
                error: function(xhr) {
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });
    });
</script>
@endpush
@endsection