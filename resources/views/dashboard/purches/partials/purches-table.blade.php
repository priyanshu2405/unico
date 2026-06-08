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
                <th>Purchase Value</th>
                <th>Taxes</th>
                <th>Per Unit Price</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
                <th>Credit Limit</th>

            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $purchase)
            <tr>
                <td>{{ $purchases->firstItem() + $index }}</td>
                <td>{{ $purchase->sku_id }}</td>
                <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $purchase->created_at->format('H:i:s') }}</td>
                <td>{{ $purchase->brand_name }}</td>
                <td>{{ $purchase->currency }}</td>
                <td>{{ $purchase->country_region }}</td>
                <td>{{ $purchase->voucher_variant }}</td>
                <td>{{ $purchase->voucher_type }}</td>
                <td>{{ $purchase->purchase_invoice_no }}</td>
                <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $purchase->quantity }}</td>
                <td>{{ $purchase->purchase_value }}</td>
                <td>{{ $purchase->taxes }}</td>
                <td>{{ $purchase->purchase_value_per_unit }}</td>
                <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
                <td>{{ $purchase->expiry_date ? $purchase->expiry_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ auth()->user()->voucher_limit ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="18" class="text-center py-4">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('dashboard.partials.custom-pagination', ['items' => $purchases])