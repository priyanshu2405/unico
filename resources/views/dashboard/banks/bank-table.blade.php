@extends('layouts.master')

@section('content')
<style>
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .edit-method-btn:hover {
        background-color: #e9ecef !important;
        transform: scale(1.1);
    }
</style>
<div class="container-fluid">


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
                        <input type="date" name="from_date" class="form-control" placeholder="From">
                        <input type="date" name="to_date" class="form-control" placeholder="To">
                    </div>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('users.management') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @php
        $totalSold = $reportData->sum('vouchers_sold');
        $totalValue = $reportData->sum('sale_value');
        $stripeData = $reportData->firstWhere('id', 'stripe');
        @endphp
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-primary text-white" style="border-radius: 20px;">
                <div class="small text-uppercase fw-bold opacity-75 mb-1">Total Sale Value</div>
                <h3 class="fw-bold mb-0">RS {{ number_format($totalValue, 2) }}</h3>
                <small class="opacity-75">All payment methods</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-success text-white" style="border-radius: 20px;">
                <div class="small text-uppercase fw-bold opacity-75 mb-1">Stripe Credit Store</div>
                <h3 class="fw-bold mb-0">RS {{ number_format($stripeData['sale_value'] ?? 0, 2) }}</h3>
                <small class="opacity-75">Total credited via Stripe</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-info text-white" style="border-radius: 20px;">
                <div class="small text-uppercase fw-bold opacity-75 mb-1">Total Vouchers Sold</div>
                <h3 class="fw-bold mb-0">{{ number_format($totalSold) }}</h3>
                <small class="opacity-75">Quantity delivered</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach($methods as $method)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative hover-card"
                style="border-radius:20px; transition: all 0.3s ease;">

                <div style="height: 6px; background: {{ $method->method_type == 'qr' ? 'linear-gradient(90deg, #8b5cf6, #a78bfa)' : ($method->method_type == 'upi' ? 'linear-gradient(90deg, #10b981, #34d399)' : 'linear-gradient(90deg, #2563eb, #60a5fa)') }};"></div>

                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white shadow-sm"
                            style="width:48px; height:48px; background: {{ $method->method_type == 'qr' ? '#8b5cf6' : ($method->method_type == 'upi' ? '#10b981' : '#2563eb') }};">
                            @if($method->method_type == 'bank')
                            <i class="fas fa-university fs-5"></i>
                            @elseif($method->method_type == 'upi')
                            <i class="fas fa-mobile-alt fs-5"></i>
                            @else
                            <i class="fas fa-qrcode fs-5"></i>
                            @endif
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-light btn-sm rounded-circle edit-method-btn shadow-sm"
                                data-method="{{ json_encode($method) }}"
                                style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #eee;">
                                <i class="fas fa-pencil-alt text-primary small"></i>
                            </button>
                            <span class="badge rounded-pill px-3 py-2"
                                style="background-color: {{ $method->method_type == 'qr' ? '#8b5cf620' : ($method->method_type == 'upi' ? '#10b98120' : '#2563eb20') }}; 
                                       color: {{ $method->method_type == 'qr' ? '#8b5cf6' : ($method->method_type == 'upi' ? '#10b981' : '#2563eb') }};
                                       font-weight: 600; font-size: 0.75rem;">
                                {{ strtoupper($method->method_type) }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1 text-capitalize">{{ $method->bank_name ?? 'Payment Method' }}</h5>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-map-marker-alt me-1 text-primary"></i> {{ $method->country ?? 'India' }}
                        </p>
                    </div>

                    <div class="p-3 rounded-3 bg-light border-0">
                        @if($method->method_type == 'bank')
                        <div class="mb-2">
                            <label class="text-muted small d-block mb-0" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Account Holder</label>
                            <span class="fw-bold text-dark">{{ $method->account_holder_name }}</span>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small d-block mb-0" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Account Number</label>
                            <span class="fw-bold text-dark font-monospace">{{ $method->account_number }}</span>
                        </div>
                        @elseif($method->method_type == 'upi')
                        <div class="mb-0">
                            <label class="text-muted small d-block mb-0" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">UPI ID</label>
                            <span class="fw-bold text-dark">{{ $method->upi_id }}</span>
                        </div>
                        @else
                        <div class="d-flex align-items-center text-primary">
                            <i class="fas fa-check-circle me-2"></i>
                            <span class="small fw-bold">QR Payment Active</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>


    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fab fa-stripe me-2 text-primary"></i>Recent Stripe Transactions (Credits)</h5>
            <small class="text-muted">Real-time payments received via Stripe</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stripeTransactions as $tx)
                        <tr>
                            <td class="font-monospace small text-primary">{{ $tx->transaction_id }}</td>
                            <td>
                                <div class="fw-bold">{{ $tx->user->name ?? 'N/A' }}</div>
                                <div class="text-muted tiny-text">{{ $tx->user->email ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <span class="fw-bold text-success">+ {{ $tx->user->currency ?? 'RS' }} {{ number_format($tx->amount, 2) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success">CREDITED</span>
                            </td>
                            <td>{{ $tx->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No Stripe transactions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Bank Report</h5>
                <small class="text-muted total-count"></small>
            </div>
            <div class="d-flex gap-2">

                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                    <i class="fas fa-plus me-1"></i> Add Payment Method
                </button>
                <a href="{{ route('banks.bank-table.export') }}" class="btn btn-success btn-sm px-3 shadow-sm">
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
                
            </div>
        </div>
        <div class="card-body" id="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th>Sr. No.</th>
                            <th>Bank ID</th>
                            <th>Currency</th>
                            <th>Country/Region</th>
                            <th>Vouchers Sold</th>
                            <th>Sale Value in Local Currency</th>
                            <th>Refunds</th>
                            <th>Disputes</th>
                            <th>Currency Conversion @</th>
                            <th>Sale Value in Buying Currency</th>
                            <th>FX Gain/Loss</th>
                            <th>Created At</th>

                        </tr>
                    </thead>

                    <tbody>
                        @foreach($reportData as $index => $data)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $data['bank_name'] }}</td>
                            <td>{{ $data['currency'] }}</td>
                            <td>{{ $data['country'] }}</td>
                            <td>{{ $data['vouchers_sold'] }}</td>
                            <td>{{ number_format($data['sale_value'], 2) }}</td>
                            <td>{{ number_format($data['refunds'], 2) }}</td>
                            <td>{{ $data['disputes'] }}</td>
                            <td>{{ $data['conversion_rate'] }}</td>
                            <td>{{ number_format($data['sale_value'], 2) }}</td>
                            <td>0.00</td>
                            <td>{{ is_string($data['created_at']) ? $data['created_at'] : $data['created_at']->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div> -->
</div>


<!-- Edit Payment Method Modal -->
<div class="modal fade" id="editMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 24px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold mb-0">Edit Payment Method</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editPaymentMethodForm" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Method Type</label>
                        <select name="method_type" class="form-select rounded-3 p-2 type-selector" required disabled>
                            <option value="bank">Bank Account</option>
                            <option value="upi">UPI ID</option>
                            <option value="qr">QR Code Only</option>
                        </select>
                        <input type="hidden" name="method_type" id="edit_method_type_hidden">
                    </div>

                    <div class="bank-fields">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Country</label>
                                <input type="text" name="country" class="form-control rounded-3 p-2" id="edit_country">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control rounded-3 p-2" id="edit_bank_name">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Account Holder Name</label>
                            <input type="text" name="account_holder_name" class="form-control rounded-3 p-2" id="edit_account_holder_name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Account Number</label>
                            <input type="text" name="account_number" class="form-control rounded-3 p-2" id="edit_account_number">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bank Code Type</label>
                                <input type="text" name="bank_code_type" class="form-control rounded-3 p-2" id="edit_bank_code_type">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bank Code Value</label>
                                <input type="text" name="bank_code_value" class="form-control rounded-3 p-2" id="edit_bank_code_value">
                            </div>
                        </div>
                    </div>

                    <div class="upi-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">UPI ID</label>
                            <input type="text" name="upi_id" class="form-control rounded-3 p-2" id="edit_upi_id">
                        </div>
                    </div>

                    <div class="qr-fields d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">QR Code Image</label>
                            <input type="file" name="qr_code" class="form-control rounded-3 p-2">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Payment Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Add Payment Method Modal -->
<!-- Add Payment Method & Webhook Modal -->
<div class="modal fade" id="addMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 24px;">

            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold mb-0">Configuration</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Tabs -->
            <div class="px-4 pt-3">
                <ul class="nav nav-pills mb-3" id="configTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#bankTab" type="button">
                            Add Bank / Payment Method
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#webhookTab" type="button">
                            Webhook Configuration
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-4 pt-0">

                <!-- ================= BANK TAB ================= -->
                <div class="tab-pane fade show active" id="bankTab">

                    <form id="addPaymentMethodForm" action="{{ route('payment-methods.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Method Type</label>
                            <select name="method_type" class="form-select rounded-3 p-2 type-selector" required>
                                <option value="bank">Bank Account</option>
                                <option value="upi">UPI ID</option>
                                <option value="qr">QR Code Only</option>
                            </select>
                        </div>

                        <div class="bank-fields">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Country</label>
                                    <input type="text" name="country" class="form-control rounded-3 p-2">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control rounded-3 p-2">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Account Holder Name</label>
                                <input type="text" name="account_holder_name" class="form-control rounded-3 p-2">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Account Number</label>
                                <input type="text" name="account_number" class="form-control rounded-3 p-2">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Bank Code Type</label>
                                    <input type="text" name="bank_code_type" class="form-control rounded-3 p-2">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Bank Code Value</label>
                                    <input type="text" name="bank_code_value" class="form-control rounded-3 p-2">
                                </div>
                            </div>
                        </div>

                        <div class="upi-fields d-none">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">UPI ID</label>
                                <input type="text" name="upi_id" class="form-control rounded-3 p-2">
                            </div>
                        </div>

                        <div class="qr-fields d-none">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">QR Code Image</label>
                                <input type="file" name="qr_code" class="form-control rounded-3 p-2">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">Save Payment Method</button>
                        </div>
                    </form>

                </div>

                <!-- ================= WEBHOOK TAB ================= -->
                <div class="tab-pane fade" id="webhookTab">

                    <form action="{{ route('webhook.save') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Webhook Name</label>
                            <input type="text" name="webhook_name" class="form-control rounded-3 p-2" value="Auto-Credit Webhook">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Webhook URL</label>
                            <input type="text" name="webhook_url" class="form-control rounded-3 p-2" value="https://api.unicou.com/webhooks/payment">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Secret Key</label>
                            <input type="text" name="webhook_secret" class="form-control rounded-3 p-2">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" class="form-select rounded-3 p-2">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success px-4">Save Webhook</button>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle fields based on method type
        $('.type-selector').on('change', function() {
            let type = $(this).val();
            let modal = $(this).closest('.modal');
            if (type === 'bank') {
                modal.find('.bank-fields').removeClass('d-none');
                modal.find('.upi-fields').addClass('d-none');
                modal.find('.qr-fields').addClass('d-none');
            } else if (type === 'upi') {
                modal.find('.bank-fields').addClass('d-none');
                modal.find('.upi-fields').removeClass('d-none');
                modal.find('.qr-fields').addClass('d-none');
            } else {
                modal.find('.bank-fields').addClass('d-none');
                modal.find('.upi-fields').addClass('d-none');
                modal.find('.qr-fields').removeClass('d-none');
            }
        });

        // Handle Add Payment Method form submission
        $('#addPaymentMethodForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let form = $(this);
            let submitBtn = form.find('button[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success('Payment method added successfully');
                    $('#addMethodModal').modal('hide');
                    form[0].reset();
                    submitBtn.prop('disabled', false).text('Save Payment Method');
                    // Optional: refresh table if payment methods are shown here
                    // updateTable(window.location.href);
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                    submitBtn.prop('disabled', false).text('Save Payment Method');
                }
            });
        });

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

        // Handle Edit button click
        $(document).on('click', '.edit-method-btn', function() {
            let method = $(this).data('method');
            let modal = $('#editMethodModal');
            let form = $('#editPaymentMethodForm');

            // Set form action
            form.attr('action', `/dashboard/payment-methods/${method.id}`);

            // Populate fields
            modal.find('select[name="method_type"]').val(method.method_type).trigger('change');
            $('#edit_method_type_hidden').val(method.method_type);
            $('#edit_country').val(method.country);
            $('#edit_bank_name').val(method.bank_name);
            $('#edit_account_holder_name').val(method.account_holder_name);
            $('#edit_account_number').val(method.account_number);
            $('#edit_bank_code_type').val(method.bank_code_type);
            $('#edit_bank_code_value').val(method.bank_code_value);
            $('#edit_upi_id').val(method.upi_id);

            modal.modal('show');
        });

        // Handle Edit form submission
        $('#editPaymentMethodForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let form = $(this);
            let submitBtn = form.find('button[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success('Payment method updated successfully');
                    $('#editMethodModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                    submitBtn.prop('disabled', false).text('Update Payment Method');
                }
            });
        });
    });
</script>
@endpush
@endsection