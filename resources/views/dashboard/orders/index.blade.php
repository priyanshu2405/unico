@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Order Management</h4>
            <p class="text-muted small mb-0">Track and manage your voucher deliveries and orders.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary bg-white shadow-sm border-0 px-3" style="border-radius: 10px;">
                <i class="fas fa-filter me-2 small"></i> Filter
            </button>
            <a href="{{ route('orders.export') }}" class="btn btn-primary shadow-sm px-4" style="border-radius: 10px; border: none;">
                Export CSV
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 py-3">Order Details</th>
                            <th class="py-3">Customer</th>
                            <th class="py-3">Amount</th>
                            <th class="py-3">Status</th>
                            <!-- <th class="text-end pe-4 py-3">Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr style="border-bottom: 1px solid #f1f3f5;">
                            <td class="ps-4 py-4">
                                <div class="fw-bold mb-1">{{ $order->order_id }}</div>
                                <div class="text-muted small mb-1">{{ $order->created_at->format('Y-m-d') }}</div>
                                <div class="text-muted extra-small">{{ $order->voucher->name ?? $order->voucher_type }}</div>
                            </td>
                            <td class="py-4">
                                <div class="fw-bold mb-1">{{ $order->user->name ?? 'Unknown' }}</div>
                                <div class="text-muted small">{{ $order->user->email ?? 'N/A' }}</div>
                            </td>
                            <td class="py-4">
                                <span class="fw-bold">{{ number_format($order->amount) }}</span>
                            </td>
                            <td class="py-4">
                                @if($order->status == 'delivered')
                                <span class="badge rounded-pill px-3 py-2" style="background-color: #e8f7f0; color: #198754; font-weight: 500;">Delivered</span>
                                @elseif($order->status == 'cancelled')
                                <span class="badge rounded-pill px-3 py-2" style="background-color: #fdeaea; color: #dc3545; font-weight: 500;">Cancelled</span>
                                @else
                                <span class="badge rounded-pill px-3 py-2" style="background-color: #fff4e6; color: #fd7e14; font-weight: 500;">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 py-4">
                                <div class="d-flex justify-content-end gap-3">
                                    @if($order->status == 'pending')
                                    <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #5e5ce6; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#deliverModal{{ $order->id }}">
                                        Deliver Voucher
                                    </button>
                                    <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #ef9a9a; font-size: 0.85rem;" onclick="return confirm('Are you sure?')">
                                            Cancel
                                        </button>
                                    </form>
                                    @elseif($order->status == 'delivered')
                                    <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #5e5ce6; font-size: 0.85rem;" onclick="alert('Codes: {{ $order->delivery_details }}')">
                                        Resend
                                    </button>
                                    <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #ef9a9a; font-size: 0.85rem;" onclick="return confirm('Are you sure?')">
                                            Cancel
                                        </button>
                                    </form>
                                    @elseif($order->status == 'cancelled')
                                    <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #5e5ce6; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#deliverModal{{ $order->id }}">
                                        Deliver Voucher
                                    </button>
                                    <button class="btn btn-link text-decoration-none p-0 fw-bold" style="color: #5e5ce6; font-size: 0.85rem;">
                                        Resend
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Deliver Modal -->
                        <div class="modal fade" id="deliverModal{{ $order->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form action="{{ route('orders.deliver', $order->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                                        <div class="modal-header border-0 pt-4 px-4">
                                            <h5 class="fw-bold">Deliver Voucher: {{ $order->order_id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body px-4">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Voucher Codes</label>
                                                <textarea name="codes" class="form-control border-0 bg-light p-3" rows="5" placeholder="Enter codes (one per line)..." style="border-radius: 10px;" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pb-4 px-4">
                                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                                            <button type="submit" class="btn btn-success px-4" style="border-radius: 10px; background-color: #6fc2a6; border: none;">Deliver Now</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-0 py-4 px-4">
            @include('dashboard.partials.custom-pagination', ['items' => $orders])
        </div>
    </div>
</div>

<style>
    .extra-small {
        font-size: 0.75rem;
    }

    .table thead th {
        border-bottom: none;
    }

    .table td {
        vertical-align: middle;
    }

    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        border: 1px solid #f1f3f5;
        color: #666;
        padding: 0.5rem 1rem;
        margin: 0 2px;
        border-radius: 8px !important;
    }

    .page-item.active .page-link {
        background-color: #5e5ce6;
        border-color: #5e5ce6;
    }

    .btn-link:hover {
        opacity: 0.8;
    }
</style>
@endsection