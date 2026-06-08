<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0 text-center">
        <thead>
            <tr class="bg-light">
                <th rowspan="2">Sr. No.</th>
                <th rowspan="2">SKU ID</th>
                <th rowspan="2">Voucher Type</th>
                <th colspan="3" class="fw-bold" style="background-color: #fce4d6;">Sold</th>
                <th colspan="3" class="fw-bold" style="background-color: #c6e0b4;">Purchases</th>
                <th colspan="3" class="fw-bold text-white" style="background-color: #ff0000;">Lost/Refund</th>
                <th colspan="3" class="fw-bold text-white" style="background-color: #ff0000;">Profit/Loss</th>
            </tr>
            <tr style="font-size: 11px;">
                <th style="background-color: #fff2cc;">Quantity</th>
                <th style="background-color: #fff2cc;">Value(Buying Currency)</th>
                <th style="background-color: #fff2cc;">Taxes Misc</th>
                
                <th style="background-color: #e2efda;">Quantity</th>
                <th style="background-color: #e2efda;">Value(Buying Currency)</th>
                <th style="background-color: #e2efda;">Taxes Misc</th>
                
                <th style="background-color: #ff8080;">Quantity</th>
                <th style="background-color: #ff8080;">Value(Buying Currency)</th>
                <th style="background-color: #ff8080;">Taxes Misc</th>
                
                <th style="background-color: #ff8080;">Quantity</th>
                <th style="background-color: #ff8080;">Value(Buying Currency)</th>
                <th style="background-color: #ff8080;">Taxes Misc</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pl_data as $index => $item)
            <tr>
                <td>{{ $pl_data->firstItem() + $index }}</td>
                <td class="fw-bold text-start">{{ $item->sku_id }}</td>
                <td>{{ $item->voucher_type }}</td>
                
                <td>{{ $item->sold_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->sold_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->sold_taxes, 2) }}</td>
                
                <td>{{ $item->purchase_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->purchase_value_calc, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->purchase_taxes_calc, 2) }}</td>
                
                <td>{{ $item->lost_qty }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->lost_value, 2) }}</td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->lost_taxes, 2) }}</td>
                
                <td>{{ $item->profit_qty }}</td>
                <td class="text-end fw-bold {{ $item->profit_value >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $item->local_currency }} {{ number_format($item->profit_value, 2) }}
                </td>
                <td class="text-end">{{ $item->local_currency }} {{ number_format($item->profit_taxes, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="15" class="py-4 text-muted">No data available in table</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $pl_data])
