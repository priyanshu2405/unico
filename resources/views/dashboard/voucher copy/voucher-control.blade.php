@extends('layouts.master')

@section('content')
<style>
    .stat-card {
        background: #fff;
        border-radius: 20px;
        padding: 1.5rem;
        height: 100%;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .icon-blue {
        background: #eef2ff;
        color: #6366f1;
    }

    .icon-green {
        background: #ecfdf5;
        color: #10b981;
    }

    .icon-orange {
        background: #fff7ed;
        color: #f97316;
    }

    .voucher-card {
        background: #fff;
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
        overflow: hidden;
    }

    .voucher-card:hover {
        transform: translateY(-5px);
    }

    .card-logo-container {
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: #fff;
    }

    .voucher-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        height: 44px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .btn-edit {
        background: #6366f1;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        padding: 0.5rem 1rem;
    }

    .btn-edit:hover {
        background: #4f46e5;
        color: #fff;
    }

    .btn-delete {
        border: 1px solid #ef4444;
        color: #ef4444;
        background: transparent;
        border-radius: 10px;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-delete:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    .points-footer {
        background: #fdfdfd;
        padding: 0.8rem 1rem;
        border-top: 1px solid #f3f4f6;
    }

    .filter-btn {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Voucher Control Panel</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('vouchers.export') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
            <button class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-import me-1"></i> Import CSV
            </button>
            <a href="{{ route('vouchers.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Add Voucher
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-ticket-alt fs-5"></i>
                </div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total Vouchers</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['total_vouchers']) }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-boxes fs-5"></i>
                </div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total Stock</small>
                <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['total_stock']) }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-orange">
                    <i class="fas fa-chart-pie fs-5"></i>
                </div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total Valuation</small>
                <h3 class="fw-bold mb-0 mt-1">PKR {{ number_format($stats['total_valuation'], 0) }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-tags fs-5"></i>
                </div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Active Brands</small>
                <h3 class="fw-bold mb-0 mt-1">{{ $stats['active_brands'] }}</h3>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
        <div class="card-body p-3">
            <form action="{{ route('vouchers.control') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select name="category" class="form-select border-0 bg-light rounded-pill px-4 py-2" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control border-0 bg-light rounded-pill ps-4 py-2" placeholder="Search vouchers..." value="{{ request('search') }}">
                        <button class="btn btn-primary rounded-pill ms-2 px-4" type="submit">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @forelse($vouchers as $voucher)
        <div class="col-md-6 col-lg-4">
            <div class="voucher-card h-100 d-flex flex-column">
                <div class="card-body p-4 flex-grow-1">
                    <div class="row g-3">
                        <div class="col-5">
                            <div class="card-logo-container border rounded-3">
                                @if($voucher->logo)
                                <img src="{{ asset('images/logos/' . $voucher->logo) }}" alt="{{ $voucher->name }}" style="max-width: 90%; max-height: 80px; object-fit: contain;">
                                @else
                                <div class="text-center">
                                    <i class="fas fa-image fa-2x text-light mb-1"></i><br>
                                    <small class="text-muted fw-bold">No Logo</small>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-7">
                            <h5 class="voucher-title">{{ $voucher->name }}</h5>
                            <div class="mb-4">
                                <span class="h4 fw-bold text-dark mb-0">PKR {{ number_format($voucher->price) }}</span>
                                @if($voucher->original_price)
                                <span class="text-danger text-decoration-line-through ms-2 small">PKR {{ number_format($voucher->original_price) }}</span>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('vouchers.edit', $voucher->id) }}" class="btn btn-edit flex-grow-1">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="points-footer d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/2830/2830305.png" width="18" class="me-2" alt="quarterly">
                        <span class="small fw-bold text-muted">{{ $voucher->quarterly_points ?? 0 }} Quarterly</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/2830/2830312.png" width="18" class="me-2" alt="yearly">
                        <span class="small fw-bold text-muted">{{ $voucher->yearly_points ?? 0 }} Yearly</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="fas fa-ticket-alt fa-4x text-light mb-3"></i>
            <h4 class="text-muted">No vouchers found</h4>
        </div>
        @endforelse
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('vouchers.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Import Vouchers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
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
@endsection