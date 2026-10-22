<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Sr. No.</th>
                <th>P.ID.</th>
                <th>Date</th>
                <th>Time</th>
                <th>Brand Name</th>
                <th>Currency</th>
                <th>Country/Region</th>
                <th>Voucher Variant</th>
                <th>Voucher Type</th>
                <th>Purchase Invoice No.</th>
                <th>Purchase Date</th>
                <th>Total Quantity</th>
                <th>Purchase Value (Local)</th>
                <th>Per Unit Price (Local)</th>
                <th>Taxes</th>
                <th>Exchange Rate</th>
                <th>Purchase Value (USD)</th>
                <th>Per Unit Price (USD)</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
                <th>Credit Limit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $purchase)
            @php
            $pCurr = $purchase->local_currency ?: ($purchase->currency ?: 'USD');
            $purchaseRate = \App\Services\ProfitLossService::getStockUploadRate($purchase);
            $purchaseValueUsd = \App\Services\ProfitLossService::getInventoryPurchaseTotalBase($purchase);
            $unitPriceUsd = \App\Services\ProfitLossService::getInventoryUnitCostBase($purchase);
            @endphp
            <tr>
                <td>{{ $purchases->firstItem() + $index }}</td>
                <td>{{ $purchase->sku_id }}</td>
                <td class="local-date" data-utc="{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d\TH:i:s\Z') : '' }}">{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : 'N/A' }}</td>
                <td class="local-time" data-utc="{{ $purchase->created_at->format('Y-m-d\TH:i:s\Z') }}">{{ $purchase->created_at->format('H:i:s') }}</td>
                <td>{{ $purchase->brand_name }}</td>
                <td>{{ $pCurr }}</td>
                <td>{{ $purchase->country_region ? (is_array($purchase->country_region) ? implode(', ', $purchase->country_region) : $purchase->country_region) : 'Global' }}</td>
                <td>{{ $purchase->voucher_variant }}</td>
                <td>{{ $purchase->voucher_type }}</td>
                <td>{{ $purchase->purchase_invoice_no }}</td>
                <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $purchase->purchased_qty }}</td>
                <td>{{ $pCurr }} {{ number_format((float)$purchase->purchase_value, 2) }} @if($pCurr !== 'USD')<span class="small text-muted font-monospace d-block">(${{ number_format($purchaseValueUsd, 2) }} USD)</span>@endif</td>
                <td>{{ $pCurr }} {{ number_format((float)$purchase->purchase_value_per_unit, 2) }} @if($pCurr !== 'USD')<span class="small text-muted font-monospace d-block">(${{ number_format($unitPriceUsd, 2) }} USD)</span>@endif</td>
                <td>{{ $purchase->taxes }}</td>
                <td>{{ number_format($purchaseRate, 4) }}</td>
                <td class="fw-bold text-primary">$ {{ number_format($purchaseValueUsd, 2) }}</td>
                <td class="fw-bold text-primary">$ {{ number_format($unitPriceUsd, 2) }}</td>
                <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
                <td>{{ $purchase->expiry_date ? $purchase->expiry_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ auth()->user()->voucher_limit ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="21" class="text-center py-4">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $purchases])