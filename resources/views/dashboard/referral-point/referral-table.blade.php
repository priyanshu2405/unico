<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light small text-uppercase text-muted">
            <tr>
                <th class="ps-4 py-3 border-0">S.NO</th>
                <th class="py-3 border-0">Date</th>
                <th class="py-3 border-0">Category</th>
                <th class="py-3 border-0">Details / Order</th>
                <th class="py-3 border-0 text-center">Points</th>
                <th class="py-3 border-0 text-center">Unspent</th>
                <th class="py-3 border-0 text-center">Release Date</th>
                <th class="py-3 border-0 text-center">Expiry Date</th>
                <th class="pe-4 py-3 border-0 text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($referralHistory as $index => $item)
            @php
                $statusColors = [
                    'available' => 'bg-soft-success text-success',
                    'pending' => 'bg-soft-warning text-warning',
                    'redeemed' => 'bg-soft-primary text-primary',
                    'expired' => 'bg-soft-secondary text-secondary',
                    'reversed' => 'bg-soft-danger text-danger',
                    'cancelled' => 'bg-soft-dark text-dark',
                ];
                $color = $statusColors[$item->status] ?? 'bg-soft-secondary text-secondary';
            @endphp
            <tr>
                <td class="ps-4">{{ $referralHistory->firstItem() + $index }}</td>
                <td class="small text-muted text-nowrap">
                    {{ $item->created_at ? $item->created_at->format('d M Y') : 'N/A' }}<br>
                    <span class="extra-small">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</span>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        {{ ucfirst(str_replace('_', ' ', $item->point_type)) }}
                    </span>
                </td>
                <td>
                    <div class="fw-bold text-dark small">{{ $item->description }}</div>
                    @if($item->order)
                        <small class="text-muted">
                            Order #{{ $item->order->order_id }} 
                            @if($item->order->inventoryVoucher)
                                &bull; {{ $item->order->inventoryVoucher->brand_name }}
                            @endif
                        </small>
                    @endif
                </td>
                <td class="text-center">
                    @if($item->points > 0)
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">+{{ number_format($item->points) }}</span>
                    @else
                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1">{{ number_format($item->points) }}</span>
                    @endif
                </td>
                <td class="text-center font-monospace fw-bold text-dark">
                    {{ number_format($item->points_remaining) }}
                </td>
                <td class="text-center small text-nowrap">
                    @if($item->available_at)
                        @if(now()->gte($item->available_at))
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Released</span>
                        @else
                            <span class="text-warning"><i class="fas fa-clock me-1"></i>{{ $item->available_at->format('d M Y') }}</span>
                        @endif
                    @else
                        <span class="text-muted">Immediate</span>
                    @endif
                </td>
                <td class="text-center small text-nowrap">
                    @if($item->expires_at)
                        @if(now()->gt($item->expires_at))
                            <span class="text-muted text-decoration-line-through">{{ $item->expires_at->format('d M Y') }}</span>
                        @else
                            <span class="text-dark">{{ $item->expires_at->format('d M Y') }}</span>
                        @endif
                    @else
                        <span class="text-muted">None</span>
                    @endif
                </td>
                <td class="pe-4 text-center">
                    <span class="badge {{ $color }} px-2 py-1 text-capitalize">
                        {{ $item->status }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <div class="py-4">
                        <i class="fas fa-gift fa-3x text-muted opacity-25 mb-3"></i>
                        <p class="mb-0 fw-semibold">No referral points ledger records found.</p>
                        <small class="text-muted">Share your referral link with agents or students to start earning rewards.</small>
                    </div>
                </td>
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
