@extends('layouts.master')
@section('content')
<div class="container-fluid">

    <div class="container-fluid mb-4">

        {{-- Offcanvas Filter --}}
        <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                    <i class="fas fa-filter me-2"></i>Filter Pricing Rules
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form id="filter-form" action="{{ route('vouchers') }}" method="GET">
                    <div class="mb-4">
                        <label class="form-label fw-bold">SKU ID</label>
                        <input type="text" name="sku_id" class="form-control" placeholder="Search SKU ID..." value="{{ request('sku_id') }}">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Brand Name</label>
                        <select name="brand_name" class="form-select select2-filter">
                            <option value="">All Brands</option>
                            @foreach($filterOptions['brands'] as $brand)
                            <option value="{{ $brand }}" {{ request('brand_name') == $brand ? 'selected' : '' }}>
                                {{ $brand }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Voucher Variant</label>
                        <select name="voucher_variant" class="form-select select2-filter">
                            <option value="">All Variants</option>
                            @foreach($filterOptions['variants'] as $variant)
                            <option value="{{ $variant }}" {{ request('voucher_variant') == $variant ? 'selected' : '' }}>
                                {{ $variant }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Voucher Type</label>
                        <select name="voucher_type" class="form-select select2-filter">
                            <option value="">All Types</option>
                            @foreach($filterOptions['types'] as $type)
                            <option value="{{ $type }}" {{ request('voucher_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="{{ route('vouchers') }}" class="btn btn-light">Reset All</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Vouchers Table</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vouchers.export', request()->all()) }}" id="csv-export-link" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </a>
                    <button class="btn btn-outline-primary btn-sm px-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </div>

        </div>
    </div>
    <!-- Vouchers Grid -->
    <div id="table-container">
        @include('dashboard.voucher.voucher-grid')
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Handle Filter Form
            $(document).on('submit', '#filter-form', function(e) {
                handleAjaxFilter(this, e);
            });
        });
    </script>
    @endpush

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Montserrat:wght@400;700;800&display=swap');

        .gift-voucher-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 300px;
            position: relative;
            border: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }

        .voucher-top {
            flex: 1;
            padding: 20px;
            position: relative;
            background: #fff;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .brand-logo-img {
            /* width: 40px;/ */
           max-height: 90px;
            object-fit: contain;
        }

        .brand-info h6 {
            color: #008080;
            margin: 0;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .brand-info span {
            font-size: 0.7rem;
            color: #999;
        }

        .voucher-titles {
            text-align: start;
            margin-top: 10px;
                max-width: 150PX;
    max-height: 150PX;
        }

        .gift-script {
            font-family: 'Dancing Script', cursive;
            color: #e63946;
            font-size: 3.5rem;
            line-height: 0.8;
            margin-bottom: -10px;
        }

        .voucher-bold {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            color: #008080;
            font-size: 3rem;
            letter-spacing: -2px;
            text-transform: uppercase;
        }

        /* Jagged Circle Price Tag */
        .price-ribbon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 100px;
            height: 100px;
            background: #008080;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            z-index: 5;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border: 4px dashed rgba(255,255,255,0.3);
        }

        .price-ribbon::before {
            content: '';
            position: absolute;
            top: -10px; left: -10px; right: -10px; bottom: -10px;
            border: 2px solid #008080;
            border-radius: 50%;
            opacity: 0.3;
        }

        .price-ribbon .currency {
            font-size: 1rem;
            font-weight: 700;
        }

        .price-ribbon .amount {
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1;
        }

        .price-ribbon .sub-text {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        /* Voucher Footer */
        .voucher-footer {
            background: #008080;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
        }

        .instructions-section {
            flex: 1;
            padding-right: 20px;
        }

        .instructions-section h6 {
            color: #ffb703;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .instructions-text {
            font-size: 0.65rem;
            line-height: 1.3;
            opacity: 0.9;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .qr-section {
            background: #fff;
            padding: 5px;
            border-radius: 5px;
            margin-right: 20px;
        }

        .qr-section i {
            font-size: 2.5rem;
            color: #333;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.65rem;
        }

        .contact-item i {
            color: #ffb703;
        }

        /* Stock Status & Meta */
        .card-status-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .btn-order-floating {
            position: absolute;
            bottom: -20px;
            right: 20px;
            background: #e63946;
            color: #fff;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(230, 57, 70, 0.4);
            border: 3px solid #fff;
            z-index: 11;
            transition: all 0.3s ease;
        }

        .btn-order-floating:hover {
            transform: scale(1.1);
            color: #fff;
        }

        .grayscale {
            filter: grayscale(1);
        }

        @media (max-width: 576px) {
            .gift-voucher-card { height: auto; }
            .voucher-footer { flex-direction: column; gap: 15px; }
            .price-ribbon { position: relative; margin: 20px auto; transform: none; top: 0; right: 0; }
        }
    </style>
</div>
@endsection