<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="text-center align-middle">
            <tr>
                <th rowspan="2" class="bg-light">Sr. No.</th>
                <th rowspan="2" class="bg-light">SKU ID</th>
                <th rowspan="2" class="bg-light">Voucher Type</th>
                <th rowspan="2" class="bg-light">City</th>
                <th colspan="3" style="background-color: #ffff00;">Opening Stock</th>
                <th colspan="3" style="background-color: #92d050;">Purchases (Additions)</th>
                <th colspan="3" style="background-color: #00b0f0;">Total Available (Opening + Purchases)</th>
                <th colspan="3" style="background-color: #ffc000;">Sold (Delivered)</th>
                <th colspan="3" style="background-color: #e82020;">Refund Amount</th>
                <th colspan="3" style="background-color: #f8cbad;">Closing Stock (Available on Hand)</th>
            </tr>
            <tr>
                <th style="background-color: #ffff99;">Qty</th>
                <th style="background-color: #ffff99;">Value</th>
                <th style="background-color: #ffff99;">Taxes</th>

                <th style="background-color: #c6e0b4;">Qty</th>
                <th style="background-color: #c6e0b4;">Value</th>
                <th style="background-color: #c6e0b4;">Taxes</th>

                <th style="background-color: #9bc2e6;">Qty</th>
                <th style="background-color: #9bc2e6;">Value</th>
                <th style="background-color: #9bc2e6;">Taxes</th>

                <th style="background-color: #fdb913;">Qty</th>
                <th style="background-color: #fdb913;">Value</th>
                <th style="background-color: #fdb913;">Taxes</th>

                <th style="background-color: #f57b70;">Returned Qty</th>
                <th style="background-color: #f57b70;">Value</th>
                <th style="background-color: #f57b70;">Taxes</th>

                <th style="background-color: #fce4d6;">Qty</th>
                <th style="background-color: #fce4d6;">Value</th>
                <th style="background-color: #fce4d6;">Taxes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stock_data as $index => $item)
            <tr>
                @php
                $purchCurr = $item->purchase_currency ?? ($item->local_currency ?: ($item->currency ?: 'USD'));
                $saleCurr = $item->sale_currency ?? ($item->currency ?: 'USD');

                $openUsd = $item->opening_val_usd ?? \App\Services\CurrencyService::convertToBase($item->opening_value, $purchCurr)['base_amount'];
                $purchUsd = $item->purchase_val_usd ?? \App\Services\CurrencyService::convertToBase($item->purchase_value_calc, $purchCurr)['base_amount'];
                $availUsd = $item->in_stock_val_usd ?? \App\Services\CurrencyService::convertToBase($item->in_stock_value, $purchCurr)['base_amount'];
                $soldUsd = $item->sold_val_usd ?? \App\Services\CurrencyService::convertToBase($item->sold_value, $saleCurr)['base_amount'];
                $lostUsd = $item->lost_val_usd ?? \App\Services\CurrencyService::convertToBase($item->lost_value, $saleCurr)['base_amount'];
                $closeUsd = $item->closing_val_usd ?? \App\Services\CurrencyService::convertToBase($item->closing_value, $purchCurr)['base_amount'];
                @endphp

                <td class="text-center">{{ $stock_data->firstItem() + $index }}</td>
                <td class="fw-bold">
                    <div>{{ $item->sku_id }}</div>
                    <div class="mt-1 d-flex gap-1 flex-wrap">
                        <span class="badge bg-secondary-subtle text-secondary border" title="Purchase Currency">Buy: {{ $purchCurr }}</span>
                        <span class="badge bg-primary-subtle text-primary border" title="Sale Currency">Sell: {{ $saleCurr }}</span>
                    </div>
                </td>
                <td>{{ $item->voucher_type }}</td>
                <td>{{ $item->city ? (is_array($item->city) ? implode(', ', $item->city) : $item->city) : 'N/A' }}</td>

                {{-- Opening --}}
                <td class="text-center">{{ $item->opening_qty }}</td>
                <td class="text-end">
                    <div>{{ $purchCurr }} {{ number_format($item->opening_value, 2) }}</div>
                    <div class="small text-primary fw-bold">$ {{ number_format($openUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $purchCurr }} {{ number_format($item->opening_taxes, 2) }}</td>

                {{-- Purchases --}}
                <td class="text-center">{{ $item->purchase_qty }}</td>
                <td class="text-end">
                    <div>{{ $purchCurr }} {{ number_format($item->purchase_value_calc, 2) }}</div>
                    <div class="small text-primary fw-bold">$ {{ number_format($purchUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $purchCurr }} {{ number_format($item->purchase_taxes, 2) }}</td>

                {{-- Available --}}
                <td class="text-center">{{ $item->in_stock_qty }}</td>
                <td class="text-end">
                    <div>{{ $purchCurr }} {{ number_format($item->in_stock_value, 2) }}</div>
                    <div class="small text-primary fw-bold">$ {{ number_format($availUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $purchCurr }} {{ number_format($item->in_stock_taxes, 2) }}</td>

                {{-- Sold --}}
                <td class="text-center">{{ $item->sold_qty }}</td>
                <td class="text-end">
                    <div>{{ $saleCurr }} {{ number_format($item->sold_value, 2) }}</div>
                    <div class="small text-primary fw-bold">$ {{ number_format($soldUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $saleCurr }} {{ number_format($item->sold_taxes, 2) }}</td>

                {{-- Loss/Refund --}}
                <td class="text-center">{{ $item->lost_qty }}</td>
                <td class="text-end">
                    <div>{{ $saleCurr }} {{ number_format($item->lost_value, 2) }}</div>
                    <div class="small text-danger fw-bold">$ {{ number_format($lostUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $saleCurr }} {{ number_format($item->lost_taxes, 2) }}</td>

                {{-- Closing --}}
                <td class="text-center">{{ $item->closing_qty }}</td>
                <td class="text-end">
                    <div>{{ $purchCurr }} {{ number_format($item->closing_value, 2) }}</div>
                    <div class="small text-primary fw-bold">$ {{ number_format($closeUsd, 2) }}</div>
                </td>
                <td class="text-end">{{ $purchCurr }} {{ number_format($item->closing_taxes, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="19" class="text-center py-5 text-muted">No stock records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $stock_data])