@if(isset($mgmtSummary))
<!-- Standardized Profit & Loss Statement (Matching Exact Spreadsheet Sample) -->
<div class="card border shadow-sm mb-4" style="max-width: 950px; background-color: #ffffff; border-radius: 8px;">
    <div class="card-body p-4">
        <div class="mb-3">
            <h4 class="fw-bold mb-0" style="color: #1b365d; letter-spacing: -0.3px;">Voucher Reselling Business</h4>
            <div class="text-secondary fst-italic" style="font-size: 14px;">Profit and Loss Statement (Standardized Layout)</div>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0" style="font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #1b365d;">
                        <th style="width: 44%; padding: 8px 6px; color: #1b365d;" class="fw-bold">P&amp;L Line Item</th>
                        <th style="width: 18%; padding: 8px 6px; color: #1b365d;" class="text-end fw-bold">Amount ($)</th>
                        <th style="width: 38%; padding: 8px 16px; color: #1b365d;" class="fw-bold">Standard Accounting Formulas / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Revenue (Sale) -->
                    <tr>
                        <td class="fw-bold py-2 px-2" style="color: #1b365d;">Revenue (Sale)</td>
                        <td class="text-end fw-bold font-monospace py-2 px-2" style="color: #1b365d;">
                            ${{ number_format($mgmtSummary['revenue'], 2) }}
                        </td>
                        <td class="text-muted py-2 px-3 small">Total revenue generated from selling vouchers</td>
                    </tr>

                    <!-- Spacer -->
                    <tr><td colspan="3" class="py-1"></td></tr>

                    <!-- COGS Breakdown -->
                    <tr>
                        <td class="py-1 px-4 text-dark">Opening Stock</td>
                        <td class="text-end font-monospace py-1 px-2 text-dark">
                            ${{ number_format($mgmtSummary['opening_stock'], 2) }}
                        </td>
                        <td class="text-muted py-1 px-3 small">Value of unsold vouchers at start of period</td>
                    </tr>
                    <tr>
                        <td class="py-1 px-4 text-dark">Purchases</td>
                        <td class="text-end font-monospace py-1 px-2 text-dark">
                            ${{ number_format($mgmtSummary['purchases'], 2) }}
                        </td>
                        <td class="text-muted py-1 px-3 small">Total cost spent buying new vouchers</td>
                    </tr>
                    <tr>
                        <td class="py-1 px-4 text-dark">Less: Closing Stock</td>
                        <td class="text-end font-monospace py-1 px-2 text-dark">
                            ${{ number_format($mgmtSummary['closing_stock'], 2) }}
                        </td>
                        <td class="text-muted py-1 px-3 small">Value of unsold vouchers at end of period (entered as positive, subtracted in formula)</td>
                    </tr>

                    <!-- Total COGS -->
                    <tr style="border-top: 1px solid #333333;">
                        <td class="fw-bold py-2 px-2 text-dark">Total Cost of Goods Sold (COGS)</td>
                        <td class="text-end fw-bold font-monospace py-2 px-2 text-dark">
                            ${{ number_format($mgmtSummary['cogs'], 2) }}
                        </td>
                        <td class="fw-semibold text-secondary py-2 px-3 small">= Opening Stock + Purchases - Closing Stock</td>
                    </tr>

                    <!-- Gross Profit -->
                    <tr style="border-top: 1px solid #333333; border-bottom: 1px solid #333333; background-color: #f8fbfd;">
                        <td class="fw-bold py-2 px-2 text-dark">Gross Profit</td>
                        <td class="text-end fw-bold font-monospace py-2 px-2 {{ $mgmtSummary['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($mgmtSummary['gross_profit'], 2) }}
                        </td>
                        <td class="fw-semibold text-secondary py-2 px-3 small">= Revenue - Total COGS</td>
                    </tr>

                    <!-- Spacer -->
                    <tr><td colspan="3" class="py-2"></td></tr>

                    <!-- Operating Expenses Header -->
                    <tr>
                        <td colspan="3" class="fw-bold py-1 px-2" style="color: #1b365d;">
                            Operating Expenses &amp; Non-Operating Items
                        </td>
                    </tr>
                    <tr>
                        <td class="py-1 px-4 text-dark">Less: Voucher Lost Expense</td>
                        <td class="text-end font-monospace py-1 px-2 text-dark">
                            ${{ number_format($mgmtSummary['voucher_lost_expense'], 2) }}
                        </td>
                        <td class="text-muted py-1 px-3 small">Cost of abnormal waste (lost/expired vouchers)</td>
                    </tr>
                    <!-- Net Profit -->
                    <tr style="border-top: 1px solid #333333; border-bottom: 3px double #333333; background-color: #f8fbfd;">
                        <td class="fw-bold py-2 px-2 fs-6 text-dark">Net Profit</td>
                        <td class="text-end fw-bold font-monospace py-2 px-2 fs-6 {{ $mgmtSummary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($mgmtSummary['net_profit'], 2) }}
                        </td>
                        <td class="fw-semibold text-secondary py-2 px-3 small">= Gross Profit - Voucher Lost Expense</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="fw-bold text-secondary mb-0">Detailed SKU Breakdown</h6>
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0 text-center" style="font-size: 12px;">
        <thead>
            <tr class="bg-light align-middle">
                <th>Sr. No.</th>
                <th>SKU ID</th>
                <th>Voucher Type</th>
                <th style="background-color: #e8f0fe;">Opening Stock ($)</th>
                <th style="background-color: #e2efda;">Purchases ($)</th>
                <th style="background-color: #e8f0fe;">Closing Stock ($)</th>
                <th style="background-color: #fff2cc;">Total COGS ($)</th>
                <th style="background-color: #d9e1f2;">Revenue (Sale) ($)</th>
                <th style="background-color: #e2efda;" class="fw-bold">Gross Profit ($)</th>
                <th style="background-color: #fce4d6;">Lost Expense ($)</th>
                <th style="background-color: #e8f0fe;">FX Gain/(Loss) ($)</th>
                <th style="background-color: #c6e0b4;" class="fw-bold">Net Profit ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pl_data as $index => $item)
            <tr>
                <td>{{ $pl_data->firstItem() + $index }}</td>
                <td class="fw-bold text-start">{{ $item->sku_id }}</td>
                <td>{{ $item->voucher_type }}</td>
                <td class="text-end">$ {{ number_format($item->opening_val_usd ?? 0, 2) }}</td>
                <td class="text-end">$ {{ number_format($item->purchase_val_usd ?? 0, 2) }}</td>
                <td class="text-end">$ {{ number_format($item->closing_val_usd ?? 0, 2) }}</td>
                <td class="text-end fw-semibold">$ {{ number_format($item->cogs ?? 0, 2) }}</td>
                <td class="text-end fw-semibold text-primary">$ {{ number_format($item->revenue ?? 0, 2) }}</td>
                <td class="text-end fw-bold {{ ($item->gross_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    $ {{ number_format($item->gross_profit ?? 0, 2) }}
                </td>
                <td class="text-end text-danger">$ {{ number_format($item->lost_expense ?? 0, 2) }}</td>
                <td class="text-end font-monospace {{ ($item->fx_gain ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    @if(($item->fx_gain ?? 0) < 0)
                        ({{ number_format(abs($item->fx_gain ?? 0), 2) }})
                    @else
                        {{ number_format($item->fx_gain ?? 0, 2) }}
                    @endif
                </td>
                <td class="text-end fw-bold {{ ($item->net_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    $ {{ number_format($item->net_profit ?? 0, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="py-4 text-muted">No data available in table</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('dashboard.partials.custom-pagination', ['items' => $pl_data])
