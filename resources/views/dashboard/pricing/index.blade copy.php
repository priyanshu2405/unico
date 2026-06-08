@extends('layouts.master')

@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Pricing Rules
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('pricing.index') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Voucher Name</label>
                    <select name="voucher_id" class="form-select select2-filter">
                        <option value="">All Vouchers</option>
                        @foreach($vouchers as $v)
                        <option value="{{ $v->id }}" {{ request('voucher_id') == $v->id ? 'selected' : '' }}>
                            {{ $v->brand_name }} ({{ $v->sku_id }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Country Name</label>
                    <input type="text" name="country_name" class="form-control" placeholder="Search country..." value="{{ request('country_name') }}">
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('pricing.index') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Pricing & Discount Rules</h5>
                <small class="text-muted">Set specific prices and discounts for vouchers by country.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#setPriceModal">
                    <i class="fas fa-plus me-1"></i> Set Price Rule
                </button>
                <a href="#" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
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
                            <th class="px-4 py-3 border-0">Voucher</th>
                            <th class="py-3 border-0">Country</th>
                            <th class="py-3 border-0">Base Sale Price</th>
                            <th class="py-3 border-0">Discount</th>
                            <th class="py-3 border-0">Final Price</th>
                            <th class="py-3 border-0">Expiry Date</th>
                            <th class="py-3 border-0">Status</th>
                            <th class="px-4 py-3 border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr id="rule-row-{{ $rule->id }}">
                            <td class="px-4">
                                <div class="fw-bold">{{ $rule->inventoryVoucher->brand_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $rule->inventoryVoucher->sku_id ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="fi fi-{{ strtolower($rule->country_code) }} me-2"></span>
                                {{ $rule->country_name }}
                            </td>
                            <td>${{ number_format($rule->sale_price, 2) }}</td>
                            <td>
                                @if($rule->discount_type === 'percentage')
                                {{ $rule->discount_value }}% OFF
                                @else
                                ${{ number_format($rule->discount_value, 2) }} OFF
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-soft-success text-success fw-bold p-2">
                                    ${{ number_format($rule->final_price, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($rule->expiry_date)
                                    @php
                                        $isExpired = \Carbon\Carbon::parse($rule->expiry_date)->isPast();
                                    @endphp
                                    <span class="{{ $isExpired ? 'text-danger fw-bold' : '' }}">
                                        {{ \Carbon\Carbon::parse($rule->expiry_date)->format('d M Y') }}
                                        @if($isExpired) (Expired) @endif
                                    </span>
                                @else
                                    <span class="text-muted">No Expiry</span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox" 
                                        data-id="{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="px-4 text-end">
                                <button class="btn btn-sm btn-light delete-rule" data-id="{{ $rule->id }}">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No pricing rules found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Set Price Modal -->
<div class="modal fade" id="setPriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Configure Pricing Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="setPriceForm">
                @csrf
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Select Voucher</label>
                        <select name="inventory_voucher_id" class="form-select select2-modal" required>
                            <option value="">Choose a voucher...</option>
                            @foreach($vouchers as $v)
                            <option value="{{ $v->id }}">{{ $v->brand_name }} ({{ $v->sku_id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Select Country</label>
                        <select name="country" id="countrySelect" class="form-select select2-modal" required>
                            <option value="">Choose a country...</option>
                            @foreach($allCountries as $code => $name)
                            <option value="{{ $code }}" data-name="{{ $name }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="country_code" id="countryCodeInput">
                        <input type="hidden" name="country_name" id="countryNameInput">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Sale Price ($)</label>
                        <input type="number" step="0.01" name="sale_price" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Discount Type</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="typeFixed" value="fixed" checked>
                                <label class="form-check-label" for="typeFixed">Fixed Amount ($)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="typePercent" value="percentage">
                                <label class="form-check-label" for="typePercent">Percentage (%)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase" id="discountValueLabel">Discount Amount</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" value="0" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-uppercase">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label fw-bold small text-uppercase" for="isActive">Active Status</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Pricing Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2-modal').select2({
            dropdownParent: $('#setPriceModal'),
            width: '100%'
        });

        $('.select2-filter').select2({
            width: '100%'
        });

        $('#countrySelect').on('change', function() {
            const code = $(this).val();
            const name = $(this).find(':selected').data('name');
            $('#countryCodeInput').val(code);
            $('#countryNameInput').val(name);
        });

        $('input[name="discount_type"]').on('change', function() {
            const label = $(this).val() === 'percentage' ? 'Discount Percentage (%)' : 'Discount Amount ($)';
            $('#discountValueLabel').text(label);
        });

        // Handle Form Submit
        $('#setPriceForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');

            $.ajax({
                url: "{{ route('pricing.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    toastr.error('Something went wrong. Please try again.');
                    btn.prop('disabled', false).html('Save Pricing Rule');
                }
            });
        });

        // Handle Status Toggle
        $(document).on('change', '.toggle-status', function() {
            const id = $(this).data('id');
            const isActive = $(this).prop('checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('pricing.toggle-status', ':id') }}".replace(':id', id),
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    is_active: isActive
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to update status.');
                }
            });
        });

        // Handle Delete
        $(document).on('click', '.delete-rule', function() {
            if (!confirm('Are you sure you want to remove this pricing rule?')) return;

            const id = $(this).data('id');
            const row = $(`#rule-row-${id}`);

            $.ajax({
                url: "{{ route('pricing.destroy', ':id') }}".replace(':id', id),
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

<style>
    .bg-soft-success {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 5px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush
@endsection