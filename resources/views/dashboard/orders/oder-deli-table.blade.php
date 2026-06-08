<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4">Purchase Details</th>
                <th>Customer</th>
                <th>Payment</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">{{ $order->order_id }}</div>
                    <div class="text-muted small">{{ $order->created_at->format('d M Y H:i') }}</div>
                    <div class="text-muted small">SKU: <span class="fw-bold text-primary">{{ $order->voucher_id }}</span></div>
                    <div class="text-muted small">Country: <span class="badge bg-light text-dark border">{{ $order->user->country ?? 'N/A' }}</span></div>
                    <div class="text-muted small">{{ $order->voucher->name ?? $order->voucher_type }} (Qty: {{ $order->quantity }})</div>
                </td>
                <td class="px-4 py-4">
                    @if(auth()->check() && auth()->user()->account_type === 'admin')
                    <div class="fw-bold text-dark">{{ $order->user->name ?? 'Unknown' }}</div>
                    <div class="text-muted small">{{ $order->user->email ?? 'N/A' }}</div>
                    @endif
                    <div class="text-muted small">
                        {{ $order->user->user_id ??'Unknown' }}
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border px-2 mt-1">{{ ucfirst($order->user_role ?? 'user') }}</span>
                </td>

                <td>
                    <div class="small">
                        <span class="text-muted d-block">Method: <span class="text-dark fw-bold text-capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span></span>
                        <span class="text-muted d-block">Bank: <span class="text-dark">{{ $order->bank_name ?: 'N/A' }}</span></span>
                        @if($order->payment_receipt)
                        <a href="{{ asset('storage/'.$order->payment_receipt) }}" target="_blank" class="text-primary fw-bold small mt-1 d-inline-block">
                            <i class="fas fa-file-invoice me-1"></i> View Receipt
                        </a>
                        @endif
                    </div>
                </td>
                <td>
                    <span class="fw-bold text-primary">RS {{ number_format($order->amount) }}</span>
                </td>
                <td>
                    @if($order->status == 'delivered')
                    <span class="badge bg-success-subtle text-success px-3 py-2">Delivered</span>
                    @elseif($order->status == 'cancelled')
                    <span class="badge bg-danger-subtle text-danger px-3 py-2">Cancelled</span>
                    @elseif($order->status == 'pending')
                    <span class="badge bg-warning-subtle text-warning px-3 py-2">Pending</span>
                    @else
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ ucfirst($order->status) }}</span>
                    @endif

                    @if($order->status == 'completed')
                        @php
                            $availableCount = 0;
                            $stockReason = '';
                            if($order->inventoryVoucher) {
                                if($order->inventoryVoucher->is_expired || ($order->inventoryVoucher->expiry_date && \Carbon\Carbon::parse($order->inventoryVoucher->expiry_date)->isPast())) {
                                    $stockReason = 'Voucher Expired';
                                } elseif ($order->inventoryVoucher->upload_vouchers) {
                                    $availableCount = count(array_diff($order->inventoryVoucher->upload_vouchers, $deliveredCodes));
                                    if ($availableCount < $order->quantity) {
                                        $stockReason = "Stock Low: {$availableCount} left";
                                    }
                                } else {
                                    $stockReason = 'No Codes in Inventory';
                                }
                            } else {
                                $stockReason = 'Inventory Record Deleted';
                            }
                        @endphp
                        @if($stockReason)
                            <div class="mt-2">
                                <span class="badge bg-danger-subtle text-danger border-0 small" style="font-size: 0.7rem;">
                                    <i class="fas fa-exclamation-triangle me-1"></i> {{ $stockReason }}
                                </span>
                            </div>
                        @endif
                    @endif
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-1">
                        @if($order->status == 'pending')
                        <form action="{{ route('orders.approve', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-light" title="Approve" onclick="return confirm('Approve this order?')">
                                <i class="fas fa-check-circle text-success"></i>
                            </button>
                        </form>
                        @endif

                        @if($order->status == 'completed')
                        @php
                            $autoCodes = [];
                            $deliveryReason = '';
                            if($order->inventoryVoucher) {
                                if($order->inventoryVoucher->is_expired || ($order->inventoryVoucher->expiry_date && \Carbon\Carbon::parse($order->inventoryVoucher->expiry_date)->isPast())) {
                                    $deliveryReason = 'Voucher Expired';
                                } elseif ($order->inventoryVoucher->upload_vouchers) {
                                    $available = array_diff($order->inventoryVoucher->upload_vouchers, $deliveredCodes);
                                    if (count($available) < $order->quantity) {
                                        $deliveryReason = 'Insufficient inventory (' . count($available) . ' available)';
                                    } else {
                                        $autoCodes = array_slice($available, 0, $order->quantity);
                                    }
                                } else {
                                    $deliveryReason = 'No codes in inventory';
                                }
                            } else {
                                $deliveryReason = 'Inventory Record Deleted';
                            }
                            $codesString = implode("\n", $autoCodes);
                        @endphp
                        <form action="{{ route('orders.deliver', $order->id) }}" method="POST" class="d-inline" id="auto-deliver-{{ $order->id }}">
                            @csrf
                            <input type="hidden" name="codes" value="{{ $codesString }}">
                            <button type="button" class="btn btn-sm btn-light" title="{{ $deliveryReason ?: 'Quick Deliver (Auto Pick)' }}"
                                onclick="if(confirm('Auto-pick and deliver {{ $order->quantity }} codes?')) document.getElementById('auto-deliver-{{ $order->id }}').submit();"
                                @if($deliveryReason) disabled @endif>
                                <i class="fas fa-magic {{ $deliveryReason ? 'text-muted' : 'text-primary' }}"></i>
                            </button>
                        </form>
                        @endif

                        @if($order->status != 'cancelled' && $order->status != 'delivered')
                        <button class="btn btn-sm btn-light" title="Cancel" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $order->id }}">
                            <i class="fas fa-times-circle text-danger"></i>
                        </button>
                        @endif

                        @if($order->status == 'delivered')
                        <button class="btn btn-sm btn-light" title="View Codes" data-bs-toggle="modal" data-bs-target="#viewCodesModal{{ $order->id }}">
                            <i class="fas fa-key text-info"></i>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>

            {{-- Modals --}}
            @if($order->status != 'cancelled' && $order->status != 'delivered')
            <div class="modal fade" id="cancelModal{{ $order->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
                        @csrf
                        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                            <div class="modal-header border-0 pt-4 px-4">
                                <h5 class="fw-bold text-danger">Cancel Order: {{ $order->order_id }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body px-4">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Reason for Cancellation</label>
                                    <textarea name="reason" class="form-control border-0 bg-light p-3" rows="3" placeholder="Explain why the order is being cancelled..." style="border-radius: 10px;" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pb-4 px-4">
                                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 10px;">Keep Order</button>
                                <button type="submit" class="btn btn-danger px-4" style="border-radius: 10px;">Confirm Cancellation</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if($order->status == 'delivered')
            <div class="modal fade" id="viewCodesModal{{ $order->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                        <div class="modal-header border-0 pt-4 px-4">
                            <h5 class="fw-bold">Delivered Codes: {{ $order->order_id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body px-4 pb-4">
                            <div class="bg-light p-3 rounded-4">
                                <div class="d-flex flex-column gap-2">
                                    @php
                                    $codes = explode("\n", str_replace("\r", "", $order->delivery_details));
                                    @endphp
                                    @if(auth()->user()->account_type === 'admin' || (auth()->user()->account_type === 'manager' && auth()->user()->can_view_voucher_code))
                                    @foreach($codes as $code)
                                    @if(trim($code))
                                    <div class="d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded border shadow-sm">
                                        <code class="text-primary fw-bold" style="font-size: 1.1rem;">{{ trim($code) }}</code>
                                        <button class="btn btn-sm btn-link text-muted p-0" onclick="copyToClipboard('{{ trim($code) }}')" title="Copy Code">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                    @endif
                                    @endforeach
                                    @else
                                    <div class="text-center py-3">
                                        <i class="fas fa-lock text-muted mb-2 d-block" style="font-size: 2rem;"></i>
                                        <div class="fw-bold text-muted">Permission Restricted</div>
                                        <small class="text-muted">You do not have permission to view voucher codes.</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 small text-muted text-center">
                                <i class="fas fa-info-circle me-1"></i> These codes were delivered on {{ $order->updated_at->format('d M Y H:i') }}
                            </div>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4">
                            <button type="button" class="btn btn-primary w-100 py-2" data-bs-dismiss="modal" style="border-radius: 10px;">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">No purchases found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-4 pb-4 mt-3">
    @include('dashboard.partials.custom-pagination', ['items' => $orders])
</div>