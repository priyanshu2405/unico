<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0 text-center">
        <thead class="bg-light align-middle">
            <tr>
                <th rowspan="2">Sr. No.</th>
                <th rowspan="2">Bank ID</th>
                <th rowspan="2">Currency</th>
                <th rowspan="2">Country/Region</th>
                <th colspan="8" class="fw-bold bg-white">Banks Reports</th>
            </tr>
            <tr style="font-size: 11px;">
                <th class="bg-light">Vouchers Sold</th>
                <th class="bg-light">Sale Value in Local Curr</th>
                <th class="bg-light">Total Credits</th>
                <th class="bg-light">Refunds</th>
                <th class="bg-light">Disputes</th>
                <th class="bg-light">Currency Conversion</th>
                <th class="bg-light">Sale Value in Buying Currency</th>
                <th class="bg-light">FX Gain/Loss</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bank_data as $index => $record)
            <tr>
                <td>{{ $bank_data->firstItem() + $index }}</td>
                <td class="fw-bold text-start">{{ $record->bank_name }}</td>
                <td>{{ $record->currency ?: ($record->inventoryVoucher->currency ?? 'USD') }}</td>
                <td>
                    @php
                        $cr = $record->inventoryVoucher->country_region ?? 'Global';
                        echo is_array($cr) ? implode(', ', $cr) : $cr;
                    @endphp
                </td>

                <td>{{ $record->quantity ?? 0 }}</td>
                <td class="text-end">{{ number_format($record->amount ?? 0, 2) }}</td>
                <td class="text-end text-success fw-bold">{{ number_format($record->amount ?? 0, 2) }}</td>
                <td class="text-end text-danger">{{ number_format($refunds[$record->order_id] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format(0, 2) }}</td>
                <td class="text-end">
                    @php
                        $rate = (float)($record->exchange_rate_applied ?? ($record->inventoryVoucher->currency_conversion_rate ?? 1.0));
                        if ($rate <= 0) $rate = 1.0;
                        $buyingVal = $record->amount_in_base_currency !== null 
                            ? (float)$record->amount_in_base_currency 
                            : ($record->amount ?? 0) * $rate;
                    @endphp
                    {{ number_format($rate, 4) }}
                </td>
                <td class="text-end fw-bold font-monospace">${{ number_format($buyingVal, 2) }}</td>
                <td class="text-end text-success">+{{ number_format(0, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="py-4 text-muted">No bank report data available</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $bank_data])