@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i> KYC & Compliance</h4>
        <div class="d-flex gap-2">
            <span class="badge bg-warning text-dark px-3 py-2">Pending: {{ \App\Models\User::where('profile_verification_status', 'pending')->count() }}</span>
            <span class="badge bg-success px-3 py-2">Verified: {{ \App\Models\User::where('profile_verification_status', 'verified')->count() }}</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('approvals.index') }}" method="GET" class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="btn-group flex-wrap cls_us_manage" role="group">
                                <a href="{{ route('approvals.index', ['role' => 'all', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role', 'all') == 'all' ? 'active' : '' }}">All Roles</a>
                                <a href="{{ route('approvals.index', ['role' => 'manager', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'manager' ? 'active' : '' }}">Manager</a>
                                <a href="{{ route('approvals.index', ['role' => 'reseller_agent', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'reseller_agent' ? 'active' : '' }}">Reseller Agent</a>
                                <a href="{{ route('approvals.index', ['role' => 'support_team', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'support_team' ? 'active' : '' }}">Support Team</a>
                                <a href="{{ route('approvals.index', ['role' => 'student', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'student' ? 'active' : '' }}">Student</a>
                                <a href="{{ route('approvals.index', ['role' => 'agent', 'search' => request('search'), 'status' => request('status')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'agent' ? 'active' : '' }}">Agent</a>
                            </div>
                        </div>
                        <input type="hidden" name="role" value="{{ request('role', 'all') }}">
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0" id="table-container">
            @include('dashboard.partials.kyc-table')
        </div>
    </div>
</div>

<style>
    .btn-xs {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }

    .bg-primary-subtle {
        background-color: #e7f1ff;
    }

    .bg-success-subtle {
        background-color: #d1e7dd;
    }

    .bg-warning-subtle {
        background-color: #fff3cd;
    }

    .bg-info-subtle {
        background-color: #cff4fc;
    }

    .status-select {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        width: auto;
        display: inline-block;
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
                    // Update URL without reload
                    window.history.pushState({}, '', url);

                    // Update hidden role input if it exists in the URL
                    const urlParams = new URLSearchParams(url.split('?')[1]);
                    if (urlParams.has('role')) {
                        $('input[name="role"]').val(urlParams.get('role'));
                    }
                }
            });
        }

        // Handle Pagination
        $(document).on('click', '.ajax-pagination a', function(e) {
            e.preventDefault();
            updateTable($(this).attr('href'));
        });

        // Handle Filters & Search
        $('form').on('submit', function(e) {
            e.preventDefault();
            let url = $(this).attr('action') + '?' + $(this).serialize();
            updateTable(url);
        });

        $('.btn-group a').on('click', function(e) {
            e.preventDefault();
            $('.btn-group a').removeClass('active');
            $(this).addClass('active');
            updateTable($(this).attr('href'));
        });

        // Handle AJAX Status Change
        $(document).on('change', '.status-update-dropdown', function() {
            let status = $(this).val();
            let url = $(this).data('action');
            let userId = $(this).data('user-id');

            if (!confirm('Are you sure you want to change status to ' + status + '?')) {
                // Revert dropdown if cancelled
                updateTable(window.location.href);
                return;
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success);
                        // No need to refresh the whole table if we just changed one status, 
                        // but it helps keep flags and counts in sync
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