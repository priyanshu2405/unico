@extends('layouts.master')

@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter My Purchases
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('purches.user.report') }}" method="GET">
                <div class="mb-4">
                    <label class="form-label fw-bold">Date Range</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Brand Name</label>
                    <input type="text" name="brand_name" class="form-control" placeholder="Brand Name" value="{{ request('brand_name') }}">
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('purches.user.report') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">My Purchase Report</h5>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-nowrap">Sr. No.</th>
                            <th class="text-nowrap">P.ID.</th>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Time</th>
                            <th class="text-nowrap">Brand Name</th>
                            <th class="text-nowrap">Currency</th>
                            <th class="text-nowrap">Country/Region</th>
                            <th class="text-nowrap">Voucher Variant</th>
                            <th class="text-nowrap">Voucher Type</th>
                            <th class="text-nowrap">Purchase Invoice No.</th>
                            <th class="text-nowrap">Purchase Date</th>
                            <th class="text-nowrap">Total Quantity</th>
                            <th class="text-nowrap">Purchase Value</th>
                            <th class="text-nowrap">Taxes</th>
                            <th class="text-nowrap">Per Unit Price</th>
                            <th class="text-nowrap">Issue Date</th>
                            <th class="text-nowrap">Expiry Date</th>
                            <th class="text-nowrap">Credit Limit</th>
                            <th class="text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $loop->iteration + ($purchases->currentPage() - 1) * $purchases->perPage() }}</td>
                            <td><span class="badge bg-primary-soft text-primary fw-bold">{{ $purchase->order_id }}</span></td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                            <td>{{ $purchase->created_at->format('H:i:s') }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->brand_name ?? 'N/A' }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->currency ?? 'N/A' }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->country_region ?? 'N/A' }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->voucher_variant ?? 'N/A' }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->voucher_type ?? 'N/A' }}</td>
                            <td>{{ $purchase->order_id }}</td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                            <td class="text-center">{{ $purchase->quantity }}</td>
                            <td class="fw-bold text-success">{{ number_format($purchase->amount, 2) }}</td>
                            <td>0.00</td>
                            <td>{{ number_format($purchase->amount / $purchase->quantity, 2) }}</td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                            <td>{{ optional($purchase->inventoryVoucher)->expiry_date ? $purchase->inventoryVoucher->expiry_date->format('d-m-Y') : 'N/A' }}</td>
                            <td>{{ auth()->user()->voucher_limit ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('purches.invoice', $purchase->order_id) }}" class="btn btn-xs btn-outline-info">Invoice</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="19" class="text-center py-4 text-muted">No purchases found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                @include('dashboard.partials.custom-pagination', ['items' => $purchases])
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .btn-xs { padding: 1px 5px; font-size: 10px; }
    th { font-size: 0.85rem; }
    td { font-size: 0.85rem; }
</style>
@endsection
