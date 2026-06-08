@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Purchases
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('orders.history') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Search Order ID</label>
                    <input type="text" name="order_id" class="form-control" placeholder="Enter Order ID" value="{{ request('order_id') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Date Wise Report (From - To)</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Brand Purchase Report</label>
                    <select name="brand_name" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand_name') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Variant Purchase Report</label>
                    <select name="voucher_variant" class="form-select">
                        <option value="">All Variants</option>
                        @foreach($variants as $variant)
                        <option value="{{ $variant }}" {{ request('voucher_variant') == $variant ? 'selected' : '' }}>{{ $variant }}</option>
                        @endforeach
                    </select>
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
                    <a href="{{ route('orders.history') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">My Purchase History</h5>
                <small class="text-muted">Track your voucher purchases and referral points.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('orders.user_export', request()->all()) }}" class="btn btn-success btn-sm px-3">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.orders.history-table')
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Purchase Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <div class="order-info">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Purchase ID:</span>
                        <span class="fw-bold" id="m-order-id"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Brand Name:</span>
                        <span class="fw-bold" id="m-brand-name"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Voucher Type:</span>
                        <span id="m-voucher-type"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Quantity:</span>
                        <span id="m-quantity"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Amount:</span>
                        <span class="fw-bold text-primary" id="m-amount"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date:</span>
                        <span id="m-date"></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status:</span>
                        <span id="m-status"></span>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <span class="text-muted d-block mb-1">Delivered Codes:</span>
                        <div class="bg-light p-3 rounded shadow-sm border" id="m-delivery" style="max-height: 280px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.view-order-btn').on('click', function() {

            const order = $(this).data('order');

            $('#m-order-id').text(order.order_id);
            $('#m-brand-name').text(order.inventory_voucher ? order.inventory_voucher.brand_name : 'N/A');
            $('#m-voucher-type').text(order.voucher_type);
            $('#m-quantity').text(order.quantity);
            $('#m-amount').text('RS ' + parseFloat(order.amount).toLocaleString());
            $('#m-date').text(new Date(order.created_at).toLocaleDateString());

            let statusBadge = '';

            if (order.status === 'delivered')
                statusBadge = '<span class="badge bg-success">DELIVERED</span>';
            else if (order.status === 'completed')
                statusBadge = '<span class="badge bg-info">COMPLETED</span>';
            else if (order.status === 'pending')
                statusBadge = '<span class="badge bg-warning text-dark">PENDING</span>';
            else
                statusBadge = '<span class="badge bg-danger">' + order.status.toUpperCase() + '</span>';

            $('#m-status').html(statusBadge);

            // Delivered Codes Message
            let deliveryContent = '';

            if (order.status === 'delivered' && order.delivery_details) {
                const codes = order.delivery_details.split(/\r?\n/).filter(code => code.trim() !== '');
                deliveryContent = '<div class="d-flex flex-column gap-2">';
                codes.forEach(code => {
                    deliveryContent += `
                        <div class="d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded border shadow-sm">
                            <code class="text-primary fw-bold" style="font-size: 1.1rem;">${code.trim()}</code>
                            <button class="btn btn-sm btn-link text-muted p-0" onclick="copyToClipboard('${code.trim()}')" title="Copy Code">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>`;
                });
                deliveryContent += '</div>';
            } else if (order.status === 'completed') {
                deliveryContent = '<div class="alert alert-info py-2 small mb-0">Your order has been approved. Waiting for voucher code delivery.</div>';
            } else if (order.status === 'pending') {
                deliveryContent = '<div class="alert alert-warning py-2 small mb-0 text-dark">Waiting for order approval.</div>';
            } else {
                deliveryContent = '<div class="text-muted small">Processing...</div>';
            }

            $('#m-delivery').html(deliveryContent);

            $('#orderDetailModal').modal('show');
        });

        // Handle Filter Form
        $(document).on('submit', '#filter-form', function(e) {
            handleAjaxFilter(this, e);
        });
    });

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Code copied to clipboard: ' + text);
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>

<style>
    th {
        font-size: 0.8rem;
        background-color: #f8f9fa;
    }

    td {
        font-size: 0.85rem;
    }

    .bg-soft-success {
        background-color: rgba(40, 167, 69, 0.1);
    }
</style>
@endpush
@endsection