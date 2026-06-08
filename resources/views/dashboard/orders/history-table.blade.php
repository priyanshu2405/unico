<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="text-nowrap ps-4">Sr. No.</th>
                <th class="text-nowrap">Purchase ID</th>
                <th class="text-nowrap">Date</th>
                <th class="text-nowrap">Time</th>
                <th class="text-nowrap">Brand Name</th>
                <th class="text-nowrap">Currency</th>
                <th class="text-nowrap">Country/Region</th>
                <th class="text-nowrap">Voucher Variant</th>
                <th class="text-nowrap">Voucher Type</th>
                <th class="text-nowrap">Purchase Invoice No.</th>
                <th class="text-nowrap">Purchase Date</th>
                <th class="text-nowrap">Total Quantity</th>
                <th class="text-nowrap text-end">Purchase Value</th>
                <th class="text-nowrap">Taxes</th>
                <th class="text-nowrap text-end">Per Unit Price</th>
                <th class="text-nowrap">Issue Date</th>
                <th class="text-nowrap">Expiry Date</th>
                <th class="text-nowrap">Credit Limit</th>
                <th class="text-nowrap text-center">Status</th>
                <th class="text-nowrap text-end pe-4">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td class="ps-4">{{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                <td class="fw-bold text-primary">{{ $order->order_id }}</td>
                <td class="text-nowrap">{{ $order->created_at->format('d-m-Y') }}</td>
                <td class="text-nowrap">{{ $order->created_at->format('H:i:s') }}</td>
                <td>{{ $order->inventoryVoucher->brand_name ?? $order->v_brand_name ?? 'N/A' }}</td>
                <td>{{ $order->inventoryVoucher->currency ?? $order->v_currency ?? 'N/A' }}</td>
                <td>
                    @if(isset($order->inventoryVoucher->country_region))
                        @php
                            $cr = $order->inventoryVoucher->country_region;
                            $cr = is_array($cr) ? $cr : [$cr];
                        @endphp
                        {{ in_array('all', $cr) || in_array('GLB', $cr) ? 'GLB' : (count($cr) > 1 ? 'MULTY' : ($cr[0] ?? 'N/A')) }}
                    @else
                        {{ $order->v_country_region ?? 'N/A' }}
                    @endif
                </td>
                <td>{{ $order->inventoryVoucher->voucher_variant ?? $order->v_voucher_variant ?? 'N/A' }}</td>
                <td>{{ $order->inventoryVoucher->voucher_type ?? $order->v_voucher_type ?? $order->voucher_type ?? 'N/A' }}</td>
                <td>{{ $order->order_id }}</td>
                <td class="text-nowrap">{{ $order->created_at->format('d-m-Y') }}</td>
                <td class="text-center">{{ $order->quantity }}</td>
                <td class="text-end fw-bold text-dark">
                    {{ $order->inventoryVoucher->currency ?? $order->v_currency ?? 'N/A' }} 
                    {{ number_format($order->amount, 2) }}
                </td>
                <td>{{ $order->inventoryVoucher->taxes ?? $order->v_taxes ?? 'N/A' }}</td>
                <td class="text-end">
                    {{ $order->inventoryVoucher->currency ?? $order->v_currency ?? 'N/A' }} 
                    {{ number_format($order->amount / max(1, $order->quantity), 2) }}
                </td>
                <td class="text-nowrap">{{ $order->created_at->format('d-m-Y') }}</td>
                <td class="text-nowrap">
                    @php
                        $expiry = $order->inventoryVoucher->expiry_date ?? $order->v_expiry_date;
                    @endphp
                    {{ $expiry ? \Carbon\Carbon::parse($expiry)->format('d-m-Y') : 'N/A' }}
                </td>
                <td>{{ auth()->user()->voucher_limit ?? 'N/A' }}</td>
                <td class="text-center">
                    @if($order->status == 'delivered')
                    <span class="badge bg-success">Delivered</span>
                    @elseif($order->status == 'completed')
                    <span class="badge bg-info">Completed</span>
                    @elseif($order->status == 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                    @else
                    <span class="badge bg-danger">{{ ucfirst($order->status) }}</span>
                    @endif
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('purches.invoice', $order->order_id) }}" class="btn btn-sm btn-light border" title="Invoice">
                            <i class="fas fa-file-invoice text-info"></i>
                        </a>
                        <button class="btn btn-sm btn-light border view-order-btn"
                            data-order="{{ json_encode($order->load('inventoryVoucher')) }}"
                            title="View Details">
                            <i class="fas fa-eye text-primary"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="20" class="text-center py-5 text-muted">No purchases found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 px-4 pb-4">
    @include('dashboard.partials.custom-pagination', ['items' => $orders])
</div>
