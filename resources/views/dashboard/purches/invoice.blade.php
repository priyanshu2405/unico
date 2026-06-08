@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 invoice-print">
                <div class="card-header bg-white border-0 py-4 px-5">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <img src="/images/company_logo.png" alt="UniCou" style="height: 50px;">
                        </div>
                        <div class="text-end">
                            <h2 class="fw-bold text-primary mb-0">PURCHASE INVOICE</h2>
                            <p class="text-muted mb-0">Invoice #: {{ $order->order_id }}</p>
                        </div>
                    </div>
                </div>
    
                <hr class="my-0">
                <div class="card-body p-5">
                    <div class="row mb-5">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase fw-bold mb-3">From:</h6>
                            <h5 class="fw-bold mb-1">UniCou Voucher Portal</h5>
                            <p class="text-muted mb-0">Website: www.unicou.com</p>
                            <p class="text-muted mb-0">Email: support@unicou.com</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="text-muted text-uppercase fw-bold mb-3">Bill To:</h6>
                            <h5 class="fw-bold mb-1">{{ $order->user->name }}</h5>
                            <p class="text-muted mb-0">{{ $order->user->email }}</p>
                            <p class="text-muted mb-0">Account Type: {{ ucfirst($order->user_role) }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                   <p class="mb-1">
    <span class="text-muted">Expiry Date:</span> 
    <strong>{{ $order->inventoryVoucher->expiry_date?->format('d M, Y') ?? 'N/A' }}</strong>
</p>
                            <p class="mb-1"><span class="text-muted">Purchase Date:</span> <strong>{{ $order->created_at->format('d M, Y') }}</strong></p>
                            <p class="mb-1"><span class="text-muted">Status:</span> <span class="badge bg-success">{{ strtoupper($order->status) }}</span></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="mb-1"><span class="text-muted">Payment Method:</span> <strong>{{ strtoupper($order->payment_method) }}</strong></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless border-bottom">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="text-center py-3">Quantity</th>
                                    <th class="text-end py-3">Unit Price</th>
                                    <th class="text-end px-4 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-4 py-4">
                                        <h6 class="fw-bold mb-1">{{ $order->inventoryVoucher->brand_name ?? 'Voucher' }}</h6>
                                        <small class="text-muted">
                                            Variant: {{ $order->inventoryVoucher->voucher_variant ?? 'Standard' }} |
                                            Type: {{ $order->inventoryVoucher->voucher_type ?? 'N/A' }} |
                                              Brand: {{ $order->inventoryVoucher->brand_name ?? 'N/A' }} |
                                              Region: {{ is_array($order->inventoryVoucher->country_region) ? implode(', ', $order->inventoryVoucher->country_region) : ($order->inventoryVoucher->country_region ?? 'N/A') }}
                                        </small>
                                    </td>
                                    <td class="text-center py-4">{{ $order->quantity }}</td>
                                    <td class="text-end py-4">{{ number_format($order->amount / $order->quantity, 2) }}</td>
                                    <td class="text-end px-4 py-4 fw-bold">{{ $order->inventoryVoucher->currency ?? 'N/A' }} {{ number_format($order->amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mt-4">
                        <div class="col-md-5 col-lg-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span>{{$order->inventoryVoucher->currency ?? 'N/A' }} {{ number_format($order->amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tax (0%):</span>
                                <span>{{ $order->tax ?? 'N/A' }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <h5 class="fw-bold text-dark mb-0">Grand Total:</h5>
                                <h4 class="fw-bold text-primary mb-0">{{ $order->inventoryVoucher->currency ?? 'N/A' }} {{ number_format($order->amount, 2) }}</h4>
                            </div>
                        </div>
                    </div>

                    @if($order->delivery_details)
                    <div class="mt-5 p-4 bg-light rounded">
                        <h6 class="fw-bold mb-3"><i class="fas fa-key me-2"></i>Delivered Voucher Codes:</h6>
                        <pre class="mb-0 bg-white p-3 border rounded" style="font-family: monospace; white-space: pre-wrap;">{{ $order->delivery_details }}</pre>
                    </div>
                    @endif

                    <!-- <div class="mt-5 pt-5 text-center text-muted border-top">
                        <p class="mb-0 small">This is a computer-generated invoice and does not require a physical signature.</p>
                        <p class="mb-0 small">Thank you for your purchase with UniCou!</p>
                    </div> -->
                </div>
                <div class="card-footer bg-white border-0 p-5 pt-0 d-print-none text-end">
                    <button onclick="window.print()" class="btn btn-primary px-4 me-2">
                        <i class="fas fa-print me-2"></i> Print Invoice
                    </button>
                    <a href="{{ route('purches.user.report') }}" class="btn btn-outline-secondary px-4">
                        Back to Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .d-print-none,
        .sidebar,
        .header,
        .top-header,
        .left-sidebar,
        .main-header,
        .layout-header,
        nav,
        aside,
        .topbar,
        .navbar,
        .page-header,
        .breadcrumb {
            display: none !important;
        }

        body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .container-fluid,
        .content-wrapper,
        .main-panel,
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
        }

        .invoice-print {
            box-shadow: none !important;
            border: 0 !important;
            width: 100% !important;
        }

        .card {
            border: 0 !important;
        }
    }
</style>
@endsection