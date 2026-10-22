@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Payments Report</h2>
            <p class="text-muted">History and details of all your voucher purchases and payments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.payments.export', request()->all()) }}" class="btn btn-success px-4 fw-bold shadow-sm">
                <i class="fas fa-file-csv me-2"></i> Export CSV
            </a>
            <button class="btn btn-outline-primary px-4 fw-bold shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                <i class="fas fa-filter me-2"></i> Filters
            </button>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small text-uppercase">
                            <th>Sr. No.</th>
                            <th>Payment ID</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Buyer Name</th>
                            <th>Buyer Type</th>
                            <th>Local currency</th>
                            <th>Country/Region</th>
                            <th>State</th>
                            <th>Brand Name</th>
                            <th>Voucher Variant</th>
                            <th>Voucher Type</th>
                            <th>Sale Invoice No.</th>
                            <th>Quantity</th>
                            <th>Sales Value (Local)</th>
                            <th>Payment Receive Bank</th>
                            <th>Admin Rate (@ USD)</th>
                            <th>Quoted Value (USD)</th>
                            <th>Settled Value (USD)</th>
                            <th>Total Cost (USD)</th>
                            <th>Gross Profit (USD)</th>
                            <th>Gateway FX Variance (USD)</th>
                            <th>Net Realized Profit (USD)</th>
                            <th>Referral Points</th>
                            <th>Bonus Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index => $payment)
                        @php
                        $currencyCode = $payment->currency ?: (optional($payment->inventoryVoucher)->currency ?? 'USD');
                        $saleValueLocal = (float) $payment->amount;
                        $quantity = max(1, (int)$payment->quantity);

                        $adminRate = (float)($payment->exchange_rate_applied ?: \App\Services\CurrencyService::getRate($currencyCode));
                        $quotedSalesUsd = $saleValueLocal * $adminRate;

                        if ($payment->amount_in_base_currency !== null && (float)$payment->amount_in_base_currency > 0) {
                            $settledSalesUsd = (float)$payment->amount_in_base_currency;
                        } else {
                            $settledSalesUsd = $quotedSalesUsd;
                        }

                        $unitCostUsd = 0.0;
                        $totalPurchaseCostUsd = 0.0;
                        if ($payment->inventoryVoucher) {
                            $unitCostUsd = (float) \App\Services\ProfitLossService::getInventoryUnitCostBase($payment->inventoryVoucher);
                            $totalPurchaseCostUsd = $unitCostUsd * $quantity;
                        }

                        $grossProfitUsd = $quotedSalesUsd - $totalPurchaseCostUsd;
                        $gatewayFxVarianceUsd = $settledSalesUsd - $quotedSalesUsd;
                        $netRealizedProfitUsd = $grossProfitUsd + $gatewayFxVarianceUsd;
                        @endphp
                        <tr>
                            <td class="px-4 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td class="fw-bold">{{ $payment->order_id }}</td>
                            <td class="text-primary">{{ $payment->transaction_id ?? 'N/A' }}</td>
                            <td>{{ $payment->created_at->format('Y-m-d') }}</td>
                            <td>{{ $payment->created_at->format('H:i:s') }}</td>
                            <td class="fw-bold">{{ $payment->user->name ?? 'N/A' }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $payment->user_role) }}</td>
                            <td>{{ $currencyCode }}</td>
                            <td>{{ $payment->user->country ?? 'N/A' }}</td>
                            <td>{{ $payment->user->state ?? 'N/A' }}</td>
                            <td>{{ optional($payment->inventoryVoucher)->brand_name ?? 'N/A' }}</td>
                            <td>{{ optional($payment->inventoryVoucher)->voucher_variant ?? 'N/A' }}</td>
                            <td>{{ $payment->voucher_type }}</td>
                            <td>{{ $payment->order_id }}</td>
                            <td class="fw-bold">{{ $payment->quantity }}</td>
                            <td class="fw-bold">{{ $currencyCode }} {{ number_format($saleValueLocal, 2) }}</td>
                            <td>{{ $payment->bank_name ?? $payment->payment_method }}</td>
                            <td>{{ number_format($adminRate, 6) }}</td>
                            <td class="text-secondary">$ {{ number_format($quotedSalesUsd, 2) }}</td>
                            <td class="fw-bold text-primary">$ {{ number_format($settledSalesUsd, 2) }}</td>
                            <td>$ {{ number_format($totalPurchaseCostUsd, 2) }}</td>
                            <td class="{{ $grossProfitUsd >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                $ {{ number_format($grossProfitUsd, 2) }}
                            </td>
                            <td class="{{ $gatewayFxVarianceUsd >= 0 ? 'text-info' : 'text-warning' }} fw-bold">
                                $ {{ number_format($gatewayFxVarianceUsd, 2) }}
                            </td>
                            <td class="{{ $netRealizedProfitUsd >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                $ {{ number_format($netRealizedProfitUsd, 2) }}
                            </td>
                            <td>{{ $payment->referral_points ?? 0 }}</td>
                            <td>{{ number_format($payment->bonus_amount ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="23" class="text-center py-5 text-muted">No payment records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-top">
                @include('dashboard.partials.custom-pagination', ['items' => $payments])
            </div>
        </div>
    </div>
</div>

{{-- Offcanvas Filter --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><i class="fas fa-filter me-2"></i>Filter Payments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form action="{{ route('reports.payments') }}" method="GET">
            <div class="mb-4">
                <label class="form-label fw-bold small">Date Range</label>
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
                <label class="form-label fw-bold small">Transaction ID</label>
                <input type="text" name="transaction_id" class="form-control" placeholder="Search Transaction ID..." value="{{ request('transaction_id') }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Country</label>
                <select name="country" class="form-select">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">State</label>
                <select name="state" class="form-select">
                    <option value="">All States</option>
                    @foreach($states as $state)
                        <option value="{{ $state }}" {{ request('state') == $state ? 'selected' : '' }}>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Brand Name</label>
                <select name="brand_name" class="form-select">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand_name') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Voucher Type</label>
                <select name="voucher_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($voucherTypes as $type)
                        <option value="{{ $type }}" {{ request('voucher_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Currency</label>
                <select name="currency" class="form-select">
                    <option value="">All Currencies</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency }}" {{ request('currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-grid gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('reports.payments') }}" class="btn btn-light">Reset All</a>
            </div>
        </form>
    </div>
</div>
@endsection
