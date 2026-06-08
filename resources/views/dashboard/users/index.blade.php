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
            <form id="filter-form" action="{{ route('users.management') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Name, Email, ID..." value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
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
                    <label class="form-label fw-bold">Role</label>
                    <select name="role" class="form-select">
                        <option value="all" {{ request('role') == 'all' ? 'selected' : '' }}>All Roles</option>
                        @if(auth()->user()->account_type === 'admin')
                        <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                        @endif
                        <option value="support_team" {{ request('role') == 'support_team' ? 'selected' : '' }}>Support Team</option>
                        <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="reseller_agent" {{ request('role') == 'reseller_agent' ? 'selected' : '' }}>Reseller Agent</option>
                        <option value="agent" {{ request('role') == 'agent' ? 'selected' : '' }}>Agent</option>
                    </select>
                </div>

               
                <div class="mb-4">
                    <label class="form-label fw-bold">Country</label>
                    <select name="country" class="form-select">
                        <option value="all" {{ request('country') == 'all' ? 'selected' : '' }}>All Countries</option>
                        @foreach(\App\Models\User::whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country') as $name)
                        <option value="{{ $name }}" {{ request('country') == $name ? 'selected' : '' }}>{{ $name }}</option>
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
                    <label class="form-label fw-bold">Verification Status</label>
                    <select name="verification_status" class="form-select">
                        <option value="all" {{ request('verification_status') == 'all' ? 'selected' : '' }}>All</option>
                        <option value="pending" {{ request('verification_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ request('verification_status') == 'verified' ? 'selected' : '' }}>Approved</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Compliance Flags</label>
                    <select name="flags" class="form-select">
                        <option value="all" {{ request('flags') == 'all' ? 'selected' : '' }}>All Flags</option>
                        <option value="email_verified" {{ request('flags') == 'email_verified' ? 'selected' : '' }}>Email Verified</option>
                        <option value="doc_verified" {{ request('flags') == 'doc_verified' ? 'selected' : '' }}>Document Verified</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('users.management') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">User Management</h5>
                <small class="text-muted total-count">{{ $users->total() }} Users Found</small>
            </div>
            <div class="d-flex gap-2">
                 @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_approve_user))
                <button id="bulk-verify-btn" class="btn btn-primary btn-sm px-3 shadow-sm d-none">
                    <i class="fas fa-sync me-1"></i> Toggle Status
                </button>
                @endif
                <a href="{{ route('users.pdf', request()->all()) }}" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
                @if(auth()->user()->account_type !== 'manager' || auth()->user()->can_create_user)
                <a href="{{ route('users.create') }}" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> Add
                </a>
                @endif
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <!-- <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Add User
                </a> -->
            </div>
        </div>
        <div class="card-body" id="table-container">
            @include('dashboard.partials.users-table')
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
                    let csvUrl = "{{ route('users.pdf') }}?" + (url.split('?')[1] || '');
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


 // Bulk Selection and Button Visibility
        $(document).on('change', '#selectAll', function() {
            $('.user-checkbox').prop('checked', $(this).prop('checked'));
            toggleBulkButton();
        });

        $(document).on('change', '.user-checkbox', function() {
            if ($('.user-checkbox:checked').length === $('.user-checkbox').length) {
                $('#selectAll').prop('checked', true);
            } else {
                $('#selectAll').prop('checked', false);
            }
            toggleBulkButton();
        });

        function toggleBulkButton() {
            if ($('.user-checkbox:checked').length > 0) {
                $('#bulk-verify-btn').removeClass('d-none');
            } else {
                $('#bulk-verify-btn').addClass('d-none');
            }
        }

        // Handle Bulk Verification
        $(document).on('click', '#bulk-verify-btn', function() {
            let selectedIds = $('.user-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) return;

            if (!confirm('Are you sure you want to toggle the status for ' + selectedIds.length + ' selected users?')) {
                return;
            }

            $.ajax({
                url: "{{ route('users.bulk-verify') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success);
                        $('#selectAll').prop('checked', false);
                        $('#bulk-verify-btn').addClass('d-none');
                        updateTable(window.location.href);
                    } else {
                        toastr.error(response.error || 'Something went wrong');
                    }
                },
                error: function(xhr) {
                    toastr.error('Something went wrong. Please try again.');
                }
            });
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