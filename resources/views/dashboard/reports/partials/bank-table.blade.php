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
                <td class="fw-bold text-start">{{ $record->bank_name ?? 'BNK-'.($index+100) }}</td>
                <td>{{ $record->currency ?? 'USD' }}</td>
                <td>{{ $record->country_region ?? 'Global' }}</td>

                <td>{{ $record->quantity ?? 10 }}</td>
                <td class="text-end">{{ number_format($record->amount_transferred ?? 500, 2) }}</td>
                <td class="text-end text-success fw-bold">{{ number_format(($record->amount_transferred ?? 500) * 1.1, 2) }}</td>
                <td class="text-end text-danger">{{ number_format(0, 2) }}</td>
                <td class="text-end">{{ number_format(0, 2) }}</td>
                <td class="text-end">1.00</td>
                <td class="text-end">{{ number_format($record->amount_transferred ?? 500, 2) }}</td>
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