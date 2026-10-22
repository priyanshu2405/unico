<div class="table-responsive">
    <table class="table table-hover align-middle" style="white-space: nowrap;">
        <thead class="table-light">
            <tr>
                <th>Sr. No.</th>
                <th>Sale ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Buyer ID</th>
                <th>Buyer Name</th>
                <th>Buyer Type</th>
                <th>Buyer Currency</th>
                <th>Local Currency</th>
                <th>Country/Region</th>
                <th>City</th>
                <th>Voucher Variant</th>
                <th>Voucher Type</th>
                <th>Sales Invoice No.</th>
                <th>Quantity</th>
                <th>Sales Value (Local)</th>
                <th>Sale Value per Unit</th>
                <th>Admin Rate (@ USD)</th>
                <th>Quoted Value (USD)</th>
                <th>Settled Value (USD)</th>
                <th>Total Cost (USD)</th>
                <th>Gross Profit (USD)</th>
                <th>Gateway FX Variance (USD)</th>
                <th>Net Realized Profit (USD)</th>
                <th>Taxes</th>
                <th>Bank</th>
                <th>Referral Points Earned</th>
                <th>Referral Points Redeemed</th>
                <th>Customer Bonus Points</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>{{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}</td>
                <td class="fw-bold">{{ $sale->order_id }}</td>
                <td>{{ $sale->created_at->format('d M Y') }}</td>
                <td>{{ $sale->created_at->format('H:i A') }}</td>
                <td class="font-monospace text-muted">{{ $sale->user->user_id ?? 'N/A' }}</td>
                <td>{{ trim(($sale->user->first_name ?? '') . ' ' . ($sale->user->last_name ?? '')) ?: 'N/A' }}</td>
                <td>
                    <span class="badge rounded-pill bg-light text-dark border px-3">
                        {{ ucfirst(str_replace('_', ' ', $sale->user_role)) }}
                    </span>
                </td>
                <td>
                    <span class="badge bg-primary-soft text-primary fw-bold px-2 py-1">
                        {{ $sale->user ? $sale->user->currency : 'N/A' }}
                    </span>
                </td>
                <td>{{ $sale->currency ?: ($sale->inventoryVoucher->currency ?? 'USD') }}</td>
                <td>
                    @if(isset($sale->inventoryVoucher->country_region) && is_array($sale->inventoryVoucher->country_region))
                        {{ in_array('all', $sale->inventoryVoucher->country_region) ? 'GLB' : (count($sale->inventoryVoucher->country_region) > 1 ? 'MULTY' : $sale->inventoryVoucher->country_region[0]) }}
                    @else
                        {{ $sale->inventoryVoucher->country_region ?? 'N/A' }}
                    @endif
                </td>
                <td>{{ optional($sale->inventoryVoucher)->city ? (is_array($sale->inventoryVoucher->city) ? implode(', ', $sale->inventoryVoucher->city) : $sale->inventoryVoucher->city) : 'N/A' }}</td>
                <td>{{ $sale->inventoryVoucher->voucher_variant ?? $sale->voucher_type ?? 'N/A' }}</td>
                <td>{{ $sale->voucher_type }}</td>
                <td>{{ $sale->order_id }}</td> {{-- Using Order ID as Invoice No --}}
                <td>{{ $sale->quantity }}</td>
                <td>
                    @php
                    $currencyCode = $sale->currency ?: ($sale->inventoryVoucher->currency ?? 'USD');
                    $saleValue = (float) $sale->amount;
                    $quantity = max(1, (int)$sale->quantity);
                    $unitValue = $saleValue / $quantity;

                    // Admin Rate & Quoted Value in USD
                    $adminRate = (float)($sale->exchange_rate_applied ?: \App\Services\CurrencyService::getRate($currencyCode));
                    $quotedSalesUsd = (float)($saleValue * $adminRate);

                    // Settled Value from Gateway / Bank / Base Currency
                    if ($sale->amount_in_base_currency !== null && (float)$sale->amount_in_base_currency > 0) {
                        $settledSalesUsd = (float)$sale->amount_in_base_currency;
                    } else {
                        $settledSalesUsd = $quotedSalesUsd;
                    }

                    // Total Purchase Cost in USD
                    $unitCostUsd = 0.0;
                    $totalPurchaseCostUsd = 0.0;
                    if ($sale->inventoryVoucher) {
                        $unitCostUsd = (float) \App\Services\ProfitLossService::getInventoryUnitCostBase($sale->inventoryVoucher);
                        $totalPurchaseCostUsd = $unitCostUsd * $quantity;
                    }

                    // Decoupled Metrics
                    $grossProfitUsd = $quotedSalesUsd - $totalPurchaseCostUsd;
                    $gatewayFxVarianceUsd = $settledSalesUsd - $quotedSalesUsd;
                    $netRealizedProfitUsd = $grossProfitUsd + $gatewayFxVarianceUsd;
                    @endphp
                    {{ $currencyCode }} {{ number_format($saleValue, 2) }}
                </td>
                <td>
                    {{ $currencyCode }} {{ number_format($unitValue, 2) }}
                </td>
                <td>{{ number_format($adminRate, 6) }}</td>
                <td class="text-secondary">$ {{ number_format($quotedSalesUsd, 2) }}</td>
                <td class="fw-bold text-primary">$ {{ number_format($settledSalesUsd, 2) }}</td>
                <td>$ {{ number_format($totalPurchaseCostUsd, 2) }}</td>
                <td class="{{ $grossProfitUsd >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                    $ {{ number_format($grossProfitUsd, 2) }}
                </td>
                <td class="{{ $gatewayFxVarianceUsd >= 0 ? 'text-info' : 'text-warning' }} fw-bold" title="Settled USD ({{ number_format($settledSalesUsd, 2) }}) - Quoted USD ({{ number_format($quotedSalesUsd, 2) }})">
                    $ {{ number_format($gatewayFxVarianceUsd, 2) }}
                </td>
                <td class="{{ $netRealizedProfitUsd >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                    $ {{ number_format($netRealizedProfitUsd, 2) }}
                </td>
                <td>{{ number_format($sale->tax_amount ?? 0, 2) }}</td>
                <td>{{ $sale->bank_name ?: ($sale->payment_method ? ucfirst($sale->payment_method) : 'N/A') }}</td>
                <td class="text-success fw-bold">{{ $sale->referral_points ?? 0 }}</td>
                <td class="text-danger fw-bold">{{ $sale->points_redeemed ?? 0 }}</td>
                <td class="text-primary fw-bold">{{ number_format($sale->bonus_amount ?? 0, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="29" class="text-center py-5 text-muted">No sales found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $sales])