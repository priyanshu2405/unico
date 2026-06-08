@extends('layouts.master')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form id="filter-form" action="{{ route('users.management') }}" method="GET" class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="btn-group flex-wrap cls_us_manage" role="group">
                                <a href="{{ route('users.management', ['role' => 'all', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role', 'all') == 'all' ? 'active' : '' }}">All</a>
                                <a href="{{ route('users.management', ['role' => 'manager', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'manager' ? 'active' : '' }}">Manager</a>
                                <a href="{{ route('users.management', ['role' => 'support_team', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'support_team' ? 'active' : '' }}">Support Team</a>
                                <a href="{{ route('users.management', ['role' => 'student', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'student' ? 'active' : '' }}">Student</a>
                                <a href="{{ route('users.management', ['role' => 'reseller_agent', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'reseller_agent' ? 'active' : '' }}">Reseller Agent</a>
                                <a href="{{ route('users.management', ['role' => 'agent', 'search' => request('search')]) }}"
                                    class="btn btn-outline-primary {{ request('role') == 'agent' ? 'active' : '' }}">Agent</a>
                            </div>
                        </div>
                        <input type="hidden" name="role" value="{{ request('role', 'all') }}">
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 text-end d-flex gap-2 cls_us_mng_btns">
                            <a href="{{ route('users.pdf', ['role' => request('role'), 'search' => request('search')]) }}" class="btn btn-danger flex-fill">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a href="{{ route('users.create') }}" class="btn btn-success flex-fill">
                                <i class="fas fa-plus"></i> Add
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">dfsdfd</h5>
            <span class="badge bg-primary total-count">{{ $users->total() }} Total Users</span>
        </div>
        <div class="card-body" id="table-container">
            @include('dashboard.partials.users-table')
        </div>

    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle Search Form
        $(document).on('submit', '#filter-form', function(e) {
            handleAjaxFilter(this, e);
        });

        $('.btn-group a').on('click', function(e) {
            e.preventDefault();
            $('.btn-group a').removeClass('active');
            $(this).addClass('active');
            fetchCustomPage($(this).attr('href'));
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
                        fetchCustomPage(window.location.href);
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