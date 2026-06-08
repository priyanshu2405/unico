<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4 py-3 border-0">S.NO</th>
                <th class="py-3 border-0">ORDER ID</th>
                <th class="py-3 border-0">BRAND</th>
                <th class="py-3 border-0">VOUCHER</th>
                <th class="py-3 border-0 text-start">AMOUNT</th>
                <th class="py-3 border-0 text-center">DATE</th>
                <th class="py-3 border-0 text-center">REFERRAL POINTS</th>
                <th class="py-3 border-0 text-center">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($referralHistory as $index => $order)
            <tr>
                <td class="px-4">{{ $referralHistory->firstItem() + $index }}</td>
                <td class="fw-bold text-primary">{{ $order->order_id }}</td>
                <td>{{ $order->v_brand_name ?? 'N/A' }}</td>
                <td>{{ $order->voucher_type }}</td>
                <td class="text-start fw-bold">RS {{ number_format($order->amount) }}</td>
                <td class="text-center small text-muted">
                    {{ $order->created_at->format('Y-m-d') }}<br>
                    {{ $order->created_at->format('H:i') }}
                </td>
                <td class="text-center">
                    <span class="badge bg-soft-success text-success">+{{ $order->referral_points }}</span>
                </td>
                <td class="text-center text-capitalize">
                    <span class="badge bg-success">{{ $order->status }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">No referral history found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($referralHistory->total() > 0)
<div class="card-footer bg-white border-0 py-3">
    @include('dashboard.partials.custom-pagination', ['items' => $referralHistory])
</div>
@endif
