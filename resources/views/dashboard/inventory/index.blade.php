@extends('layouts.master')

@section('content')
<style>
    .voucher-cp-header {
        background: transparent;
        margin-bottom: 2rem;
    }

    .cp-title {
        font-size: 24px;
        font-weight: 800;
        color: #1a1a1a;
    }

    .btn-cp {
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-export {
        background: #fff;
        border: 1px solid #10b981;
        color: #10b981;
    }

    .btn-import {
        background: #fff;
        border: 1px solid #3b82f6;
        color: #3b82f6;
    }

    .btn-add-v {
        background: #3b82f6;
        border: none;
        color: #fff;
    }

    .stat-card {
        background: #fff;
        border-radius: 15px;
        padding: 1.5rem;
        height: 100%;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .icon-blue {
        background: #eff6ff;
        color: #3b82f6;
    }

    .icon-green {
        background: #f0fdf4;
        color: #10b981;
    }

    .icon-orange {
        background: #fff7ed;
        color: #f97316;
    }

    .filter-btn {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-count {
        background: #3b82f6;
        color: #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="cp-title mb-0">Stocks</h2>
        <div class="d-flex gap-2">
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_edit_voucher_stock))
            <button id="bulk-duplicate-btn" class="btn btn-outline-info btn-sm px-3 shadow-sm d-flex align-items-center">
                <i class="fas fa-copy me-1"></i> Bulk Duplicate
            </button>
            @endif
            <a href="{{ route('inventory.export') }}" id="inventory-export-btn" class="btn btn-success btn-sm px-3 shadow-sm d-flex align-items-center">
                <i class="fas fa-file-csv me-1"></i> CSV
            </a>
            <button class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <!-- <button class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#importInventoryModal">
                <i class="fas fa-file-import me-1"></i> Import
            </button> -->
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_edit_voucher_stock))
            <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center">
                <i class="fas fa-plus me-1"></i> Add Voucher
            </a>
            @endif
        </div>
    </div>

    <!-- Filters Status Area -->
    <!-- <div class="d-flex justify-content-end align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="reset-filters-btn" class="btn btn-link text-danger text-decoration-none fw-bold p-0">
                <i class="fas fa-times me-1"></i> RESET
            </button>
            <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px;">
                Showing <span id="vouchers-count">{{ $inventory->total() }}</span> Vouchers
            </span>
        </div>
    </div> -->

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2"></i>Filter Inventory
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="inventory-filter-form">
                <div class="mb-3">
                    <label class="form-label fw-bold small">SKU ID</label>
                    <input type="text" name="sku_id" class="form-control" placeholder="Search SKU ID..." value="{{ request('sku_id') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Purchase Date Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-6">
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="{{ request('expiry_date') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Country</label>
                    <select name="countries" class="form-select">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                        <option value="{{ $country }}" {{ request('countries') == $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">State</label>
                    <select name="states" class="form-select">
                        <option value="">All States</option>
                        @foreach($states as $state)
                        <option value="{{ $state }}" {{ request('states') == $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Brand Name</label>
                    <select name="brands" class="form-select">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brands') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Voucher Type</label>
                    <select name="types" class="form-select">
                        <option value="">All Types</option>
                        @foreach($voucherTypes as $type)
                        <option value="{{ $type }}" {{ request('types') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Voucher Variant</label>
                    <select name="variants" class="form-select">
                        <option value="">All Variants</option>
                        @foreach($voucherVariants as $variant)
                        <option value="{{ $variant }}" {{ request('variants') == $variant ? 'selected' : '' }}>{{ $variant }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['IN STOCK', 'OUT OF STOCK'] as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Sort By</label>
                    <select name="sort" class="form-select" id="sort-input">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <button type="button" id="clear-filters" class="btn btn-light">Reset All</button>
                </div>
            </form>
        </div>
    </div>



    <!-- List -->
    <div id="voucher-list-container">
        @include('dashboard.inventory.partials.voucher-list')
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importInventoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Import Inventory CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text mt-2">
                            <small>Please use the format provided by the Export CSV feature.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Import Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        function fetchVouchers(url = null) {
            let formData = $('#inventory-filter-form').serialize();
            let targetUrl = url ? url : "{{ route('inventory.index') }}?" + formData;

            $.ajax({
                url: targetUrl,
                beforeSend: function() {
                    $('#voucher-list-container').css('opacity', '0.5');
                },
                success: function(data) {
                    $('#voucher-list-container').html(data);
                    $('#voucher-list-container').css('opacity', '1');

                    // Update count display
                    const totalCount = $(data).find('#total-vouchers-count').val() || $(data).filter('#total-vouchers-count').val();
                    if (totalCount) $('#vouchers-count').text(totalCount);

                    window.history.pushState({}, '', targetUrl);
                }
            });
        }

        // Handle Filter Form Submit
        $('#inventory-filter-form').on('submit', function(e) {
            e.preventDefault();
            fetchVouchers();

            // Close offcanvas
            var offcanvasElement = document.getElementById('filterOffcanvas');
            var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (offcanvas) offcanvas.hide();
        });

        // Handle Bulk Duplicate
        $('#bulk-duplicate-btn').on('click', function() {
            let selectedIds = $('.voucher-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                toastr.warning('Please select at least one voucher.');
                return;
            }

            if (!confirm('Are you sure you want to duplicate ' + selectedIds.length + ' vouchers?')) return;

            $.ajax({
                url: "{{ route('inventory.bulk-duplicate') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        fetchVouchers(window.location.href);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    let errorMessage = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                    toastr.error(errorMessage);
                }
            });
        });

        // Trigger fetch on input change (optional, for live updates)
        $('.filter-input').on('change', function() {
            // fetchVouchers(); // Uncomment if you want live updates
        });

        $(document).on('click', '.delete-voucher-btn', function() {
            if (!confirm('Are you sure you want to delete this voucher?')) return;
            let id = $(this).data('id');
            let url = "{{ route('inventory.destroy', ':id') }}".replace(':id', id);

            $.ajax({
                url: url,
                method: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    toastr.success(response.success);
                    fetchVouchers(window.location.href);
                }
            });
        });

        // Handle CSV Export with Filters
        $('#inventory-export-btn').on('click', function(e) {
            e.preventDefault();
            let formData = $('#inventory-filter-form').serialize();
            let baseUrl = "{{ route('inventory.export') }}";
            window.location.href = baseUrl + "?" + formData;
        });

        $('#clear-filters, #reset-filters-btn').on('click', function() {
            $('#inventory-filter-form')[0].reset();
            fetchVouchers("{{ route('inventory.index') }}");

            // Close offcanvas if it was open
            var offcanvasElement = document.getElementById('filterOffcanvas');
            var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (offcanvas) offcanvas.hide();
        });
    });
</script>
@endpush
@endsection