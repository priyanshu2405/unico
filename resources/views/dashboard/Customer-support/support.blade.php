@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4 rounded-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Queries</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                        <span class="ms-auto text-primary small fw-bold"><i class="fas fa-headset me-1"></i>All Time</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-warning border-4 rounded-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Pending Queries</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0 text-warning">{{ $stats['pending'] }}</h3>
                        <span class="ms-auto text-warning small fw-bold"><i class="fas fa-clock me-1"></i>Awaiting</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-success border-4 rounded-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Closed Queries</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0 text-success">{{ $stats['closed'] }}</h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-check-circle me-1"></i>Resolved</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Section -->
    <div class="row g-4 mb-4">
        <!-- Add Topic -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">Manage Topics</h5>
                </div>
                <div class="card-body px-4">
                    <form id="addTopicForm" class="mb-3">
                        @csrf
                        <input type="hidden" name="type" value="topic">
                        <div class="input-group">
                            <input type="text" name="name" class="form-control border-0 bg-light shadow-none" placeholder="New Topic Name..." required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-primary px-4" type="submit" style="border-radius: 0 10px 10px 0;">Add</button>
                        </div>
                    </form>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm table-hover">
                            <tbody>
                                @foreach($topics as $topic)
                                <tr id="option-row-{{ $topic->id }}">
                                    <td>{{ $topic->name }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0 delete-option" data-id="{{ $topic->id }}"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Issue -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">Manage Issues</h5>
                </div>
                <div class="card-body px-4">
                    <form id="addIssueForm" class="mb-3">
                        @csrf
                        <input type="hidden" name="type" value="issue">
                        <div class="mb-2">
                            <select name="parent_id" class="form-select border-0 bg-light shadow-none" required style="border-radius: 10px;">
                                <option value="" selected disabled>Select Parent Topic</option>
                                @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group">
                            <input type="text" name="name" class="form-control border-0 bg-light shadow-none" placeholder="New Issue Name..." required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-primary px-4" type="submit" style="border-radius: 0 10px 10px 0;">Add</button>
                        </div>
                    </form>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr class="small text-muted">
                                    <th>Topic</th>
                                    <th>Issue</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issues as $issue)
                                <tr id="option-row-{{ $issue->id }}">
                                    <td class="small text-primary">{{ $issue->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $issue->name }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0 delete-option" data-id="{{ $issue->id }}"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Queries
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('customer.query') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Topic</label>
                    <select name="topic" class="form-select select2-filter">
                        <option value="">All Topics</option>
                        @foreach($topics as $t)
                        <option value="{{ $t->name }}" {{ request('topic') == $t->name ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('customer.query') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-0">
            <div>
                <h5 class="mb-0 fw-bold">Customer Support Queries</h5>
                <small class="text-muted">Manage and respond to customer support requests.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm rounded-pill" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="px-4 py-3 border-0">User Id</th>
                            <th class="py-3 border-0">Role</th>
                            <th class="py-3 border-0">Topic</th>
                            <th class="py-3 border-0">Issue</th>
                            <th class="py-3 border-0">Message</th>
                            <th class="py-3 border-0 text-center">Status</th>
                            <th class="px-4 py-3 border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($queries as $query)
                        <tr id="query-row-{{ $query->id }}">
                            <td class="px-4 py-4">
                                @if(auth()->check() && auth()->user()->account_type === 'admin')
                                <small class="fw-bold">
                                    {{ $query->user?->name ?? '' }} <br>
                                    {{ $query->user?->email ?? '' }}
                                </small>
                                @endif
                                <div class="fw-bold">
                                    {{ $query->user?->user_id ?? 'Guest' }}
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark">{{ ucfirst($query->user->account_type ?? 'user') }}</span></td>
                            <td>{{ $query->topic }}</td>
                            <td>{{ $query->issue }}</td>
                            <td>
                                <div class="text" style="max-width: 200px;" title="{{ $query->description }}">
                                    {{ $query->description }}
                                </div>
                            </td>
                            <td class="text-center query-status-cell">
                                <span class="badge rounded-pill px-3 py-2 status-badge {{ $query->status == 'pending' ? 'bg-soft-warning text-warning' : ($query->status == 'closed' ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary') }}">
                                    {{ ucfirst($query->status) }}
                                </span>
                            </td>
                            <td class="px-4 text-end">
                                <button class="btn btn-link text-primary p-0 view-query" 
                                    data-id="{{ $query->id }}"
                                    data-user-name="{{ $query->user?->name ?? 'Guest' }}"
                                    data-user-id="{{ $query->user?->user_id ?? 'N/A' }}"
                                    data-user-email="{{ $query->user?->email ?? 'N/A' }}"
                                    data-topic="{{ $query->topic }}"
                                    data-issue="{{ $query->issue }}"
                                    data-description="{{ $query->description }}"
                                    data-status="{{ $query->status }}"
                                    data-status-class="bg-{{ $query->status == 'pending' ? 'soft-warning text-warning' : ($query->status == 'closed' ? 'soft-success text-success' : 'soft-secondary text-secondary') }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No Support Queries Found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($queries->total() > 0)
        <div class="card-footer bg-white border-0 py-4 px-4">
            @include('dashboard.partials.custom-pagination', ['items' => $queries])
        </div>
        @endif
    </div>

    <!-- Query Details Modal -->
    <div class="modal fade" id="queryDetailsModal" tabindex="-1" aria-labelledby="queryDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="queryDetailsModalLabel">Query Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">User Information</label>
                            <div class="p-3 bg-light rounded-3">
                                <div id="modal-user-name" class="fw-bold mb-1"></div>
                                <div id="modal-user-id" class="small text-muted"></div>
                                <div id="modal-user-email" class="small text-muted"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Status & Details</label>
                            <div class="p-3 bg-light rounded-3">
                                <div class="mb-2">
                                    <span class="small text-muted">Status:</span>
                                    <span id="modal-status" class="badge rounded-pill ms-2"></span>
                                </div>
                                <div class="mb-2">
                                    <span class="small text-muted">Topic:</span>
                                    <span id="modal-topic" class="ms-2 fw-semibold"></span>
                                </div>
                                <div>
                                    <span class="small text-muted">Issue:</span>
                                    <span id="modal-issue" class="ms-2 fw-semibold"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Message</label>
                            <div class="p-3 bg-light rounded-3">
                                <p id="modal-description" class="mb-0 text-dark" style="white-space: pre-wrap;"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" id="btn-resolve-query" class="btn btn-success rounded-pill px-4 me-auto d-none">
                        <i class="fas fa-check-circle me-1"></i> Resolve Query
                    </button>
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .bg-soft-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-soft-success {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .bg-soft-secondary {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .table thead th {
        border-bottom: none;
    }

    .input-group .form-control:focus {
        box-shadow: none;
        border: 1px solid #5e5ce6 !important;
    }
</style>
<script>
    $(document).ready(function() {
        // Handle Add Topic/Issue
        $('#addTopicForm, #addIssueForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button');
            const originalText = btn.text();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: "{{ route('support.options.store') }}",
                method: "POST",
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('Failed to add option');
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });

        let currentQueryId = null;

        // Handle View Query
        $('.view-query').on('click', function() {
            const btn = $(this);
            currentQueryId = btn.data('id');
            $('#modal-user-name').text(btn.data('user-name'));
            $('#modal-user-id').text('ID: ' + btn.data('user-id'));
            $('#modal-user-email').text(btn.data('user-email'));
            $('#modal-topic').text(btn.data('topic'));
            $('#modal-issue').text(btn.data('issue'));
            $('#modal-description').text(btn.data('description'));
            
            const status = btn.data('status');
            const statusClass = btn.data('status-class');
            $('#modal-status').text(status.charAt(0).toUpperCase() + status.slice(1))
                            .removeClass()
                            .addClass('badge rounded-pill ms-2 ' + statusClass);
            
            if (status === 'pending') {
                $('#btn-resolve-query').removeClass('d-none');
            } else {
                $('#btn-resolve-query').addClass('d-none');
            }
            
            $('#queryDetailsModal').modal('show');
        });

        // Handle Resolve Query
        $('#btn-resolve-query').on('click', function() {
            if (!currentQueryId) return;
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Resolving...');

            $.ajax({
                url: "{{ route('customer.query.resolve', ':id') }}".replace(':id', currentQueryId),
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        
                        // Update modal status
                        $('#modal-status').text('Closed')
                                        .removeClass()
                                        .addClass('badge rounded-pill ms-2 bg-soft-success text-success');
                        
                        btn.addClass('d-none');

                        // Update table row
                        const row = $(`#query-row-${currentQueryId}`);
                        row.find('.status-badge')
                           .text('Closed')
                           .removeClass('bg-soft-warning text-warning')
                           .addClass('bg-soft-success text-success');
                           
                        // Update view button data attributes
                        const viewBtn = row.find('.view-query');
                        viewBtn.data('status', 'closed');
                        viewBtn.data('status-class', 'bg-soft-success text-success');

                        // Close modal
                        $('#queryDetailsModal').modal('hide');
                        
                        // Update stat card values dynamically
                        const pendingCard = $('.border-warning h3');
                        const closedCard = $('.border-success h3');
                        
                        let pendingCount = parseInt(pendingCard.text());
                        let closedCount = parseInt(closedCard.text());
                        
                        if (pendingCount > 0) {
                            pendingCard.text(pendingCount - 1);
                        }
                        closedCard.text(closedCount + 1);
                    }
                },
                error: function() {
                    toastr.error('Failed to resolve query.');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Resolve Query');
                }
            });
        });

        // Handle Delete Option
        $('.delete-option').on('click', function() {
            if (!confirm('Are you sure?')) return;
            const id = $(this).data('id');
            const row = $(`#option-row-${id}`);

            $.ajax({
                url: "{{ route('support.options.destroy', ':id') }}".replace(':id', id),
                method: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        toastr.success(response.message);
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection