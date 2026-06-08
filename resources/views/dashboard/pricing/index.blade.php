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
                <div class="mb-3">
                    <label class="form-label fw-bold small">Date Wise Report (From - To)</label>
                    <div class="input-group">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small"> Voucher Purchases Summary (USD / GBP)</label>
                    <select name="currency" class="form-select">
                        <option value="">All Currencies</option>
                        <option value="USD" {{ request('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="GBP" {{ request('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Brand Purchase Report</label>
                    <input type="text" name="brand_name" class="form-control" placeholder="Brand Name" value="{{ request('brand_name') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Variant Purchase Report</label>
                    <input type="text" name="voucher_variant" class="form-control" placeholder="Variant" value="{{ request('voucher_variant') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Exam Type wise Report (Online/Center)</label>
                    <input type="text" name="voucher_type" class="form-control" placeholder="Exam Type" value="{{ request('voucher_type') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Supplier-wise Purchase Report (Country)</label>
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
    <h5 class="mb-0 fw-bold">Purchase Stock</h5>
    <small class="text-muted">Add and manage stock details for purchased items.</small>
</div>
            <div class="d-flex gap-2">
                <a href="{{ route('pricing.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Add Stock
                </a>
                <a href="{{ route('pricing.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
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
                    <thead class="bg-light text-nowrap">
                        <tr>
                            <th class="px-3 py-3 border-0">Sr. No.</th>
                            <th class="py-3 border-0">P.ID.</th>
                            <th class="py-3 border-0">Date</th>
                            <th class="py-3 border-0">Time</th>
                            <th class="py-3 border-0">Brand Name</th>
                            <th class="py-3 border-0">Currency</th>
                            <th class="py-3 border-0">Country/Region</th>
                            <th class="py-3 border-0">State</th>
                            <th class="py-3 border-0">City</th>
                            <th class="py-3 border-0">Voucher Variant</th>
                            <th class="py-3 border-0">Voucher Type</th>
                            <th class="py-3 border-0">Purchase Invoice No.</th>
                            <th class="py-3 border-0">Purchase Date</th>
                            <th class="py-3 border-0">Total Quantity</th>
                            <th class="py-3 border-0">Purchase Value</th>
                            <th class="py-3 border-0">Taxes</th>
                            <th class="py-3 border-0">Per Unit Price</th>
                            <th class="py-3 border-0">Issue Date</th>
                            <th class="py-3 border-0">Expiry Date</th>
                            <th class="py-3 border-0">Credit Limit</th>
                            <th class="py-3 border-0">Status</th>
                            <th class="px-3 py-3 border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-nowrap">
                        @forelse($rules as $index => $rule)
                        <tr id="rule-row-{{ $rule->id }}">
                            <td class="px-3">{{ $index + 1 }}</td>
                            <td class="fw-bold text-primary">{{ $rule->purchase_id ?? 'N/A' }}</td>
                            <td>{{ $rule->created_at->format('d M Y') }}</td>
                            <td>{{ $rule->created_at->format('H:i A') }}</td>
                            <td>{{ $rule->brand_name ?? $rule->inventoryVoucher->brand_name ?? 'N/A' }}</td>
                            <td>{{ $rule->currency ?? $rule->inventoryVoucher->currency ?? 'N/A' }}</td>
                            <td>{{ $rule->country_name }}</td>
                            <td>{{ $rule->state ?? 'N/A' }}</td>
                            <td>{{ $rule->city ?? 'N/A' }}</td>
                            <td>{{ $rule->voucher_variant ?? $rule->inventoryVoucher->voucher_variant ?? 'N/A' }}</td>
                            <td>{{ $rule->voucher_type ?? $rule->inventoryVoucher->voucher_type ?? 'N/A' }}</td>
                            <td>{{ $rule->purchase_invoice_no ?? $rule->inventoryVoucher->purchase_invoice_no ?? 'N/A' }}</td>
                            <td>{{ $rule->purchase_date ? \Carbon\Carbon::parse($rule->purchase_date)->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $rule->total_quantity ?? $rule->inventoryVoucher->quantity ?? 0 }}</td>
                            <td>${{ number_format($rule->purchase_value ?? $rule->inventoryVoucher->purchase_value ?? 0, 2) }}</td>
                            <td>${{ number_format($rule->taxes ?? $rule->inventoryVoucher->taxes ?? 0, 2) }}</td>
                            <td>${{ number_format($rule->per_unit_price ?? $rule->inventoryVoucher->purchase_value_per_unit ?? 0, 2) }}</td>
                            <td>{{ $rule->issue_date ? \Carbon\Carbon::parse($rule->issue_date)->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @if($rule->expiry_date)
                                @php
                                $isExpired = \Carbon\Carbon::parse($rule->expiry_date)->isPast();
                                @endphp
                                <span class="{{ $isExpired ? 'text-danger fw-bold' : '' }}">
                                    {{ \Carbon\Carbon::parse($rule->expiry_date)->format('d M Y') }}
                                </span>
                                @else
                                <span class="text-muted">No Expiry</span>
                                @endif
                            </td>
                            <td>${{ number_format($rule->credit_limit ?? 0, 2) }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox"
                                        data-id="{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="px-3 text-end">
                                <a href="{{ route('pricing.edit', $rule->id) }}" class="btn btn-sm btn-light me-1">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                <button class="btn btn-sm btn-light delete-rule" data-id="{{ $rule->id }}">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="20" class="text-center py-5 text-muted">
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

<form action="{{ url('/api/webhook/wise') }}" method="POST">
    @csrf
    <input type="hidden" name="amount" value="10">
    <input type="hidden" name="currency" value="USD">
    <input type="hidden" name="reference" value="TEST-USER-1">
</form>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2-filter').select2({
            width: '100%'
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
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 5px;
    }
</style>
@endpush
@endsection