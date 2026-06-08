@extends('layouts.master')

@section('content')

<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
            <i class="fas fa-filter me-2"></i>Filter
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="filter-form" action="{{ route('bonus') }}" method="GET">
            <div class="mb-4">
                <label class="form-label fw-bold">Order ID</label>
                <input type="text" name="order_id" class="form-control" placeholder="Search order ID..." value="">
            </div>

            <div class="d-grid gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('bonus') }}" class="btn btn-light">Reset All</a>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Job Applications</h5>
            <small class="text-muted">Manage and review support team applications.</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0">S.NO</th>
                        <th class="py-3 border-0">User Info</th>
                        <th class="py-3 border-0">Country</th>
                        <th class="py-3 border-0">Designation</th>
                        <th class="py-3 border-0 text-center">Date</th>
                        <th class="py-3 border-0 text-center">Status</th>
                        <th class="py-3 border-0 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse($applications as $index => $application)
                    <tr id="application-row-{{ $application->id }}">
                        <td class="px-4">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $application->name }}</div>
                            <small class="text-muted">{{ $application->email }}</small><br>
                            <small class="text-muted">{{ $application->phone }}</small>
                        </td>
                        <td>{{ $application->country }}</td>
                        <td>{{ $application->designation }}</td>
                        <td class="text-center small text-muted">
                            {{ $application->created_at->format('Y-m-d') }}<br>
                            {{ $application->created_at->format('H:i') }}
                        </td>
                        <td class="text-center">
                            @if($application->status == 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($application->status == 'selected')
                            <span class="badge bg-success">Selected</span>
                            @elseif($application->status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-info view-application" data-id="{{ $application->id }}" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($application->status == 'pending')
                                <button class="btn btn-sm btn-outline-success update-status" data-id="{{ $application->id }}" data-status="selected" title="Select">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger update-status" data-id="{{ $application->id }}" data-status="rejected" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                                <button class="btn btn-sm btn-outline-dark delete-application" data-id="{{ $application->id }}" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No applications found.</td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white border-0 py-3">

    </div>

</div>
<!-- Application Details Modal -->
<div class="modal fade" id="applicationModal" tabindex="-1" aria-labelledby="applicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="applicationModalLabel">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="application-details-content">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // View Application Details
        $('.view-application').on('click', function() {
            let id = $(this).data('id');
            let modal = $('#applicationModal');
            let content = $('#application-details-content');

            content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
            modal.modal('show');

            $.get(`/dashboard/job-applications/${id}/view`, function(response) {
                if (response.success) {
                    let data = response.data;
                    let html = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold border-bottom pb-1">Basic Info</h6>
                            <p><strong>Name:</strong> ${data.name}</p>
                            <p><strong>DOB:</strong> ${data.dob}</p>
                            <p><strong>Email:</strong> ${data.email}</p>
                            <p><strong>Phone:</strong> ${data.phone}</p>
                            <p><strong>WhatsApp:</strong> ${data.whatsapp_number}</p>
                            <p><strong>Social Link:</strong> ${data.social_link ? `<a href="${data.social_link}" target="_blank">View</a>` : 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold border-bottom pb-1">Address</h6>
                            <p><strong>Address:</strong> ${data.address}</p>
                            <p><strong>City:</strong> ${data.city}</p>
                            <p><strong>State:</strong> ${data.state}</p>
                            <p><strong>Country:</strong> ${data.country}</p>
                            <p><strong>Post Code:</strong> ${data.post_code}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold border-bottom pb-1">Reference</h6>
                            <p><strong>Name:</strong> ${data.reference_name}</p>
                            <p><strong>Org:</strong> ${data.organization_name}</p>
                            <p><strong>Email:</strong> ${data.reference_email}</p>
                            <p><strong>Phone:</strong> ${data.reference_phone}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold border-bottom pb-1">Identity & Bank</h6>
                            <p><strong>ID Type:</strong> ${data.id_type}</p>
                            <p><strong>ID Number:</strong> ${data.id_number}</p>
                            <p><strong>Designation:</strong> ${data.designation}</p>
                            <p><strong>Bank:</strong> ${data.bank_name} (${data.bank_country})</p>
                            <p><strong>Account:</strong> ${data.bank_account_number}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="fw-bold border-bottom pb-1">Documents</h6>
                            <div class="d-flex gap-3">
                                ${data.id_document ? `<a href="/storage/${data.id_document}" target="_blank" class="btn btn-sm btn-outline-primary">ID Document</a>` : ''}
                                ${data.photograph ? `<a href="/storage/${data.photograph}" target="_blank" class="btn btn-sm btn-outline-primary">Photograph</a>` : ''}
                                ${data.reference_letter ? `<a href="/storage/${data.reference_letter}" target="_blank" class="btn btn-sm btn-outline-primary">Ref Letter</a>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                    content.html(html);
                }
            });
        });

        // Update Application Status (Select/Reject)
        $('.update-status').on('click', function() {
            let id = $(this).data('id');
            let status = $(this).data('status');
            let statusText = status === 'selected' ? 'select' : 'reject';
            let confirmBtnColor = status === 'selected' ? '#28a745' : '#dc3545';

            Swal.fire({
                title: `Are you sure?`,
                text: `You want to ${statusText} this application.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${statusText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`/dashboard/job-applications/${id}/status`, {
                        _token: "{{ csrf_token() }}",
                        status: status
                    }, function(response) {
                        if (response.success) {
                            Swal.fire('Updated!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    });
                }
            });
        });

        // Delete Application
        $('.delete-application').on('click', function() {
            let id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/dashboard/job-applications/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success').then(() => {
                                    $(`#application-row-${id}`).remove();
                                });
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection