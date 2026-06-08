<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="text-center align-middle">
            <tr>
                <th rowspan="2" class="bg-light">Sr. No.</th>
                <th rowspan="2" class="bg-light">SKU ID</th>
                <th rowspan="2" class="bg-light">Voucher Type</th>
                <th colspan="3" style="background-color: #ffff00;">Opening Stock</th>
                <th colspan="3" style="background-color: #92d050;">Purchases (Additions)</th>
                <th colspan="3" style="background-color: #00b0f0;">Stock Available</th>
                <th colspan="3" style="background-color: #ffc000;">Sold (Delivered)</th>
                <th colspan="3" style="background-color: #e82020;">Loss/Refund</th>
                <th colspan="3" style="background-color: #f8cbad;">Closing Stock (Expired)</th>
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

                <th style="background-color: #f57b70;">Qty</th>
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
                <td class="text-center">{{ $stock_data->firstItem() + $index }}</td>
                <td class="fw-bold">{{ $item->sku_id }}</td>
                <td>{{ $item->voucher_type }}</td>

                {{-- Opening --}}
                <td class="text-center">{{ $item->opening_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->opening_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->opening_taxes, 2) }}</td>


                {{-- Purchases --}}
                <td class="text-center">{{ $item->purchase_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->purchase_value_calc, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->purchase_taxes, 2) }}</td>

                {{-- Available --}}
                <td class="text-center">{{ $item->in_stock_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->in_stock_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->in_stock_taxes, 2) }}</td>

                {{-- Sold --}}
                <td class="text-center">{{ $item->sold_qty }}</td>
                <td class="text-end">{{ $item->currency }} {{ number_format($item->sold_value, 2) }}</td>
                <td class="text-end">{{ $item->currency }} {{ number_format($item->sold_taxes, 2) }}</td>

                {{-- Loss/Refund --}}
                <td class="text-center">{{ $item->lost_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->lost_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->lost_taxes, 2) }}</td>

                {{-- Closing --}}
                <td class="text-center">{{ $item->closing_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->closing_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->closing_taxes, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="18" class="text-center py-5 text-muted">No stock records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $stock_data])