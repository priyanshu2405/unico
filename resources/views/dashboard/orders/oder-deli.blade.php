@extends('layouts.master')
@section('content')
<div class="container-fluid">

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Purchases
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
                <form id="filter-form" action="{{ route('orders.index') }}" method="GET">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Purchase ID</label>
                        <input type="text" name="order_id" class="form-control" placeholder="Search purchase ID..." value="{{ request('order_id') }}">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">SKU ID</label>
                        <input type="text" name="sku_id" class="form-control" placeholder="Search SKU ID..." value="{{ request('sku_id') }}">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Country</label>
                        <select name="country" class="form-select">
                            <option value="">All Countries</option>
                            @foreach($countries as $country)
                                <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">User Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="agent" {{ request('role') == 'agent' ? 'selected' : '' }}>Agent</option>
                        <option value="reseller_agent" {{ request('role') == 'reseller_agent' ? 'selected' : '' }}>Reseller Agent</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 12px;">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Purchase Deliveries</h5>
                <small class="text-muted">{{ $orders->total() }} Purchases Found</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('orders.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i> CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.orders.oder-deli-table')
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
    });

    function toggleCode(orderId, code) {
        const textarea = document.getElementById('codes_textarea_' + orderId);
        let currentCodes = textarea.value.split('\n').map(c => c.trim()).filter(c => c !== '');

        const index = currentCodes.indexOf(code);
        if (index > -1) {
            currentCodes.splice(index, 1);
            updateCodeItemUI(orderId, code, false);
        } else {
            currentCodes.push(code);
            updateCodeItemUI(orderId, code, true);
        }

        textarea.value = currentCodes.join('\n');
    }

    function updateCodeItemUI(orderId, code, isSelected) {
        const items = document.querySelectorAll(`.code-item[data-order-id="${orderId}"][data-code="${code}"]`);
        items.forEach(item => {
            const icon = item.querySelector('i');
            if (isSelected) {
                item.classList.remove('bg-white', 'border');
                item.classList.add('bg-primary', 'text-white');
                icon.classList.remove('far', 'fa-circle');
                icon.classList.add('fas', 'fa-check-circle');
            } else {
                item.classList.remove('bg-primary', 'text-white');
                item.classList.add('bg-white', 'border', 'text-dark');
                icon.classList.remove('fas', 'fa-check-circle');
                icon.classList.add('far', 'fa-circle');
            }
        });
    }

    function autoPickCodes(orderId, quantity) {
        const availableItems = Array.from(document.querySelectorAll(`.code-item[data-order-id="${orderId}"]:not(.bg-danger-subtle)`));
        const textarea = document.getElementById('codes_textarea_' + orderId);

        // Reset selections for this order first
        availableItems.forEach(item => {
            updateCodeItemUI(orderId, item.dataset.code, false);
        });

        const selectedCodes = [];
        for (let i = 0; i < Math.min(quantity, availableItems.length); i++) {
            const code = availableItems[i].dataset.code;
            selectedCodes.push(code);
            updateCodeItemUI(orderId, code, true);
        }

        textarea.value = selectedCodes.join('\n');

        if (availableItems.length < quantity) {
            alert(`Warning: Only ${availableItems.length} codes available in inventory, but ${quantity} required.`);
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            toastr.success('Code copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
</script>
@endpush
@endsection