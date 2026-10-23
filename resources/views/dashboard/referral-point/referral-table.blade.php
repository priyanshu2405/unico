@php
    $isAdmin = $isAdmin ?? (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isManager()));
@endphp
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light small text-uppercase text-muted">
            <tr>
                <th class="ps-4 py-3 border-0">S.NO</th>
                <th class="py-3 border-0">Date</th>
                @if($isAdmin)
                <th class="py-3 border-0">User Account</th>
                @endif
                <th class="py-3 border-0">Category</th>
                <th class="py-3 border-0">Details / Order</th>
                <th class="py-3 border-0 text-center">Points</th>
                <th class="py-3 border-0 text-center">Unspent</th>
                <th class="py-3 border-0 text-center">Release Date</th>
                <th class="py-3 border-0 text-center">Expiry Date</th>
                <th class="py-3 border-0 text-center">Status</th>
                @if($isAdmin)
                <th class="pe-4 py-3 border-0 text-center">Admin Controls</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($referralHistory as $index => $item)
            @php
                $statusColors = [
                    'available' => 'bg-soft-success text-success border border-success border-opacity-25',
                    'pending' => 'bg-soft-warning text-warning border border-warning border-opacity-25',
                    'held' => 'bg-soft-danger text-danger border border-danger border-opacity-25',
                    'redeemed' => 'bg-soft-primary text-primary border border-primary border-opacity-25',
                    'expired' => 'bg-soft-secondary text-secondary border border-secondary border-opacity-25',
                    'reversed' => 'bg-soft-dark text-dark border',
                    'cancelled' => 'bg-soft-danger text-danger border',
                ];
                $color = $statusColors[$item->status] ?? 'bg-soft-secondary text-secondary';
            @endphp
            <tr>
                <td class="ps-4">{{ $referralHistory->firstItem() + $index }}</td>
                <td class="small text-muted text-nowrap">
                    {{ $item->created_at ? $item->created_at->format('d M Y') : 'N/A' }}<br>
                    <span class="extra-small">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</span>
                </td>
                @if($isAdmin)
                <td>
                    <div class="fw-bold text-dark small">{{ $item->user->name ?? 'User #' . $item->user_id }}</div>
                    <div class="extra-small text-muted">{{ $item->user->email ?? '' }}</div>
                    <span class="badge bg-light text-primary border extra-small px-2 py-0 mt-1">
                        {{ ucfirst(str_replace('_', ' ', $item->user->account_type ?? 'Agent')) }}
                    </span>
                </td>
                @endif
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
                    @if(!empty($item->metadata['admin_notes']))
                        <div class="extra-small text-danger mt-1">
                            <i class="fas fa-sticky-note me-1"></i>Admin: {{ $item->metadata['admin_notes'] }}
                        </div>
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
                    @if($item->status === 'available')
                        <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Available</span>
                    @elseif($item->status === 'held')
                        <span class="text-danger fw-semibold"><i class="fas fa-pause-circle me-1"></i>On Hold</span>
                    @elseif($item->available_at)
                        @if(now()->gte($item->available_at))
                            <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Matured</span>
                        @else
                            <span class="text-warning fw-semibold"><i class="fas fa-clock me-1"></i>{{ $item->available_at->format('d M Y') }}</span>
                            <div class="extra-small text-muted">{{ now()->diffForHumans($item->available_at, ['syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW, 'parts' => 1]) }}</div>
                        @endif
                    @else
                        <span class="text-muted">Immediate</span>
                    @endif
                </td>
                <td class="text-center small text-nowrap">
                    @if($item->expires_at)
                        @php
                            $validityDays = $item->available_at ? (int) round($item->available_at->diffInDays($item->expires_at)) : 0;
                            $validityLabel = match(true) {
                                $validityDays >= 360 => '1 Year Validity',
                                $validityDays >= 170 => '180 Days Validity',
                                $validityDays >= 85 => '90 Days Validity',
                                $validityDays >= 55 => '60 Days Validity',
                                $validityDays >= 28 => '30 Days Validity',
                                default => $validityDays . ' Days Validity'
                            };
                        @endphp
                        @if(now()->gt($item->expires_at))
                            <span class="text-muted text-decoration-line-through">{{ $item->expires_at->format('d M Y') }}</span>
                            <div class="extra-small text-danger fw-semibold">Expired</div>
                        @else
                            <span class="text-dark fw-bold">{{ $item->expires_at->format('d M Y') }}</span>
                            <div class="extra-small text-muted"><i class="fas fa-hourglass-half me-1"></i>{{ $validityLabel }}</div>
                        @endif
                    @else
                        <span class="text-muted">None</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge {{ $color }} px-2 py-1 text-capitalize">
                        {{ $item->status }}
                    </span>
                </td>
                @if($isAdmin)
                <td class="pe-4 text-center text-nowrap">
                    @if($item->status === 'pending')
                        <button type="button" class="btn btn-sm btn-success px-2 py-1 admin-point-btn" data-id="{{ $item->id }}" data-action="release" title="Approve & Release Points Immediately">
                            <i class="fas fa-check-circle me-1"></i>Approve
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1 ms-1 admin-point-btn" data-id="{{ $item->id }}" data-action="hold" title="Place on Hold (Review / Dispute)">
                            <i class="fas fa-pause-circle me-1"></i>Hold
                        </button>
                    @elseif($item->status === 'available')
                        <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1 admin-point-btn" data-id="{{ $item->id }}" data-action="hold" title="Place Points on Hold">
                            <i class="fas fa-pause-circle me-1"></i>Hold
                        </button>
                    @elseif($item->status === 'held')
                        <button type="button" class="btn btn-sm btn-success px-2 py-1 admin-point-btn" data-id="{{ $item->id }}" data-action="release" title="Release / Unhold Points">
                            <i class="fas fa-play-circle me-1"></i>Release
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 ms-1 admin-point-btn" data-id="{{ $item->id }}" data-action="cancel" title="Cancel Points">
                            <i class="fas fa-ban me-1"></i>Cancel
                        </button>
                    @else
                        <span class="text-muted small">&mdash;</span>
                    @endif
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $isAdmin ? 11 : 9 }}" class="text-center py-5 text-muted">
                    <div class="py-4">
                        <i class="fas fa-gift fa-3x text-muted opacity-25 mb-3"></i>
                        <p class="mb-0 fw-semibold">No referral points ledger records found.</p>
                        <small class="text-muted">Points will appear once referred users complete eligible voucher purchases.</small>
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
