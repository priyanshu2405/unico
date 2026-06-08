<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4">User Info</th>
                <th>Location</th>
                <th>Order ID</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($refunds as $refund)
            <tr>
                <td class="ps-4">
                    @if(auth()->user()->isAdmin())
                    <div class="fw-bold text-dark">{{ $refund->user->name ?? 'Unknown' }}</div>
                    <div class="text-muted small">{{ $refund->user->email ?? 'N/A' }}</div>
                    @endif
                    <div class="text-muted small">ID: {{ $refund->user->user_id ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="small fw-bold">{{ $refund->user->country ?? 'N/A' }}</div>
                    <div class="text-muted small">{{ $refund->user->state ?? 'N/A' }}</div>
                </td>
                <td><span class="fw-bold">{{ $refund->order_id }}</span></td>
                <td><span class="fw-bold text-primary">{{ $refund->order->inventoryVoucher->currency ?? 'RS' }} {{ number_format($refund->amount) }}</span></td>
                <td>
                    <div class="text-muted small" title="{{ $refund->reason }}">
                        {{ Str::limit($refund->reason, 20) }}
                    </div>
                </td>
                <td>
                    @if($refund->status == 'approved')
                    <span class="badge bg-success-subtle text-success px-3 py-2">Approved</span>
                    @elseif($refund->status == 'rejected')
                    <span class="badge bg-danger-subtle text-danger px-3 py-2">Rejected</span>
                    @else
                    <span class="badge bg-warning-subtle text-warning px-3 py-2">Pending</span>
                    @endif
                </td>
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-light" title="View Details" data-bs-toggle="modal" data-bs-target="#viewModal{{ $refund->id }}">
                            <i class="fas fa-eye text-primary"></i>
                        </button>

                        @if($refund->status == 'pending')
                        <button class="btn btn-sm btn-light" title="Process Refund" data-bs-toggle="modal" data-bs-target="#processModal{{ $refund->id }}">
                            <i class="fas fa-money-check-alt text-success"></i>
                        </button>

                        <button class="btn btn-sm btn-light" title="Reject" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $refund->id }}">
                            <i class="fas fa-times-circle text-danger"></i>
                        </button>
                        @endif
                    </div>

                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal{{ $refund->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                                <div class="modal-header border-0 pt-4 px-4">
                                    <h5 class="fw-bold">Refund Request Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body px-4 text-start">
                                    <div class="mb-2"><strong>User:</strong> {{ $refund->user->name }} ({{ $refund->user->email }})</div>
                                    <div class="mb-2"><strong>Order ID:</strong> {{ $refund->order_id }}</div>
                                    <div class="mb-2"><strong>Amount:</strong> RS {{ number_format($refund->amount) }}</div>
                                    <div class="mb-2"><strong>Reason:</strong> {{ $refund->reason }}</div>
                                    <div class="mb-2"><strong>Bank Details:</strong> {{ $refund->bank_details }}</div>
                                    <div class="mb-2"><strong>User TRX ID:</strong> {{ $refund->user_transaction_id }}</div>
                                    @if($refund->transaction_slip)
                                    <div class="mb-2">
                                        <strong>Transaction Slip:</strong><br>
                                        <img src="{{ asset('storage/'.$refund->transaction_slip) }}" class="img-fluid rounded mt-2 border" alt="Slip">
                                    </div>
                                    @endif
                                    @if($refund->status == 'approved' && $refund->refund_receipt)
                                    <hr>
                                    <div class="mb-2 text-success"><strong>Refund Processed Successfully</strong></div>
                                    <div class="mb-2"><strong>Admin TRX ID:</strong> {{ $refund->transaction_id }}</div>
                                    <div class="mb-2"><strong>Processed At:</strong> {{ $refund->processed_at }}</div>
                                    <div class="mb-2">
                                        <strong>Refund Receipt:</strong><br>
                                        <img src="{{ asset('storage/'.$refund->refund_receipt) }}" class="img-fluid rounded mt-2 border" alt="Receipt">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($refund->status == 'pending')
                    <!-- Process Refund Modal -->
                    <div class="modal fade" id="processModal{{ $refund->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="{{ route('refunds.process', $refund->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                                    <div class="modal-header border-0 pt-4 px-4 text-start">
                                        <h5 class="fw-bold text-success">Process Refund: {{ $refund->order_id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body px-4 text-start">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Admin Transaction ID</label>
                                            <input type="text" name="transaction_id" class="form-control border-0 bg-light p-3" placeholder="Enter refund transaction ID..." style="border-radius: 10px;" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Processed Date</label>
                                                <input type="date" name="processed_date" class="form-control border-0 bg-light p-3" style="border-radius: 10px;" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Processed Time</label>
                                                <input type="time" name="processed_time" class="form-control border-0 bg-light p-3" style="border-radius: 10px;" value="{{ date('H:i') }}" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Refund Receipt (SS/Slip)</label>
                                            <input type="file" name="refund_receipt" class="form-control border-0 bg-light p-3" style="border-radius: 10px;" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pb-4 px-4">
                                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                                        <button type="submit" class="btn btn-success px-4" style="border-radius: 10px;">Submit Refund Proof</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal{{ $refund->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="{{ route('refunds.reject', $refund->id) }}" method="POST">
                                @csrf
                                <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                                    <div class="modal-header border-0 pt-4 px-4 text-start">
                                        <h5 class="fw-bold text-danger">Reject Refund: {{ $refund->order_id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body px-4 text-start">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Reason for Rejection</label>
                                            <textarea name="note" class="form-control border-0 bg-light p-3" rows="3" placeholder="Explain why the refund is being rejected..." style="border-radius: 10px;" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pb-4 px-4">
                                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                                        <button type="submit" class="btn btn-danger px-4" style="border-radius: 10px;">Reject Refund</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">No refund requests found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 px-4 pb-4">
    @include('dashboard.partials.custom-pagination', ['items' => $refunds])
</div>
