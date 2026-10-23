@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2 text-primary"></i>Filter Referral Points
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('referral') }}" method="GET">
                @if($isAdmin && !empty($summary['users_list']))
                <div class="mb-4">
                    <label class="form-label fw-bold">Filter By User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users (System-wide)</option>
                        @foreach($summary['users_list'] as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->user_id ?? $u->email }}) &bull; {{ ucfirst(str_replace('_', ' ', $u->account_type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search order ID, user, or description..." value="{{ request('search') }}">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Reward Category</label>
                    <select name="point_type" class="form-select">
                        <option value="">All Categories</option>
                        <option value="b2b_referral" {{ request('point_type') == 'b2b_referral' ? 'selected' : '' }}>B2B Referral (1%)</option>
                        <option value="student_referral" {{ request('point_type') == 'student_referral' ? 'selected' : '' }}>Student Referral</option>
                        <option value="reversal" {{ request('point_type') == 'reversal' ? 'selected' : '' }}>Reversals</option>
                        <option value="negative_offset" {{ request('point_type') == 'negative_offset' ? 'selected' : '' }}>Negative Offsets</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available (Spendable)</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending (Maturity / Holding)</option>
                        <option value="held" {{ request('status') == 'held' ? 'selected' : '' }}>Held (Disputed / Frozen by Admin)</option>
                        <option value="redeemed" {{ request('status') == 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Date Range</label>
                    <div class="d-flex flex-column gap-2">
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                </div>

                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary fw-bold">Apply Filters</button>
                    <a href="{{ route('referral') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    @if($isAdmin)
    {{-- Admin Oversight Banner --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="fas fa-shield-alt me-1"></i> Admin Referral Oversight & Controls
                    </span>
                    <h2 class="fw-bold mb-2 text-white">Referral Points Administration</h2>
                    <p class="text-white-50 mb-4" style="max-width: 580px;">
                        Manage, review, hold, or release referral points across all agents and students. Oversee B2B qualification progress (Rs. 100,000 in 3 months) and statutory release maturities.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-light rounded-3 px-3 fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                            <i class="fas fa-filter me-1"></i> Filter Ledgers
                        </button>
                        <a href="{{ route('referral.export', request()->all()) }}" class="btn btn-success rounded-3 px-3 fw-bold">
                            <i class="fas fa-file-csv me-1"></i> Export Full CSV
                        </a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-white-50 small text-uppercase fw-bold">System Total Spendable</span>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">PKR 1 = 1 Point</span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <h1 class="display-6 fw-bold mb-0 text-white">{{ number_format($summary['points']['net_available_points']) }}</h1>
                            <span class="ms-2 text-warning fw-semibold">Points Active</span>
                        </div>
                        <div class="row g-2 mt-2 pt-2 border-top border-white border-opacity-10 small text-white-50">
                            <div class="col-6">
                                <span class="d-block text-white fw-bold">{{ number_format($summary['points']['pending_points']) }}</span>
                                Pending Maturity
                            </div>
                            <div class="col-6">
                                <span class="d-block text-danger fw-bold">{{ number_format($summary['points']['held_points'] ?? 0) }}</span>
                                Flagged on Hold
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- User Hero Share & Referral Code Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="fas fa-gift me-1"></i> UniCou Referral Program
                    </span>
                    <h2 class="fw-bold mb-2 text-white">Share Your Link & Earn Rewards</h2>
                    <p class="text-white-50 mb-4" style="max-width: 580px;">
                        Invite colleagues, agents, institutes, or students. Earn <strong>1% on eligible B2B purchases</strong> after qualification or per-unit student referral bonuses. Points are promotional purchase credit (1 Point = PKR 1).
                    </p>

                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="bg-white bg-opacity-10 rounded-3 p-2 px-3 border border-white border-opacity-10 d-flex align-items-center gap-2">
                            <span class="text-white-50 small">Referral Code:</span>
                            <span class="fw-bold text-warning font-monospace fs-5" id="refCodeText">{{ $summary['referral_code'] }}</span>
                            <button class="btn btn-sm btn-link text-white text-decoration-none p-0 ms-1 copy-btn" data-clipboard="{{ $summary['referral_code'] }}" title="Copy Code">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary rounded-3 px-3 fw-bold copy-btn" data-clipboard="{{ $summary['referral_link'] }}">
                                <i class="fas fa-link me-1"></i> Copy Referral Link
                            </button>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode('Join UniCou Voucher System using my referral link: ' . $summary['referral_link']) }}" target="_blank" class="btn btn-success rounded-3 px-3 fw-bold">
                                <i class="fab fa-whatsapp me-1"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-white-50 small text-uppercase fw-bold">Spendable Rewards</span>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">PKR 1 = 1 Point</span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <h1 class="display-6 fw-bold mb-0 text-white">{{ number_format($summary['points']['net_available_points']) }}</h1>
                            <span class="ms-2 text-warning fw-semibold">Points Available</span>
                        </div>
                        <p class="text-white-50 small mb-3">
                            Redeem up to 10% of eligible voucher purchases during checkout (min 100 points).
                        </p>
                        @if($summary['points']['next_expiring_points'] > 0)
                        <div class="alert alert-warning py-2 px-3 mb-0 small rounded-3 bg-warning bg-opacity-25 border-0 text-warning d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>{{ number_format($summary['points']['next_expiring_points']) }} points</strong> expire on {{ $summary['points']['next_expiring_date']->format('d M Y') }}.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Bind Box if user doesn't have a referrer --}}
    @if(!$user->referred_by)
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-primary border-4 bg-white">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-user-plus text-primary me-2"></i>Have a Referral Code?</h6>
                <small class="text-muted">Link your account to your referrer before making your first voucher purchase.</small>
            </div>
            <form action="{{ route('referral.bind') }}" method="POST" class="d-flex gap-2">
                @csrf
                <input type="text" name="referral_code" class="form-control" placeholder="Enter Referral Code" style="min-width: 220px; text-transform: uppercase;" required>
                <button type="submit" class="btn btn-outline-primary px-3 fw-bold text-nowrap">Link Referrer</button>
            </form>
        </div>
    </div>
    @endif
    @endif

    {{-- Metric Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 border-start border-success border-4 h-100 bg-white">
                <div class="card-body p-3 p-lg-4">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Available Points</span>
                    <h3 class="fw-bold mb-1 text-success">{{ number_format($summary['points']['net_available_points']) }}</h3>
                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Ready for redemption</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 border-start border-warning border-4 h-100 bg-white">
                <div class="card-body p-3 p-lg-4">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Pending Points</span>
                    <h3 class="fw-bold mb-1 text-warning">{{ number_format($summary['points']['pending_points']) }}</h3>
                    <small class="text-muted"><i class="fas fa-clock text-warning me-1"></i>Awaiting maturity/target</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            @if($isAdmin)
            <div class="card shadow-sm border-0 rounded-4 border-start border-danger border-4 h-100 bg-white">
                <div class="card-body p-3 p-lg-4">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Points On Hold</span>
                    <h3 class="fw-bold mb-1 text-danger">{{ number_format($summary['points']['held_points'] ?? 0) }}</h3>
                    <small class="text-muted"><i class="fas fa-pause-circle text-danger me-1"></i>Dispute / Admin review</small>
                </div>
            </div>
            @else
            <div class="card shadow-sm border-0 rounded-4 border-start border-primary border-4 h-100 bg-white">
                <div class="card-body p-3 p-lg-4">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Redeemed</span>
                    <h3 class="fw-bold mb-1 text-primary">{{ number_format($summary['points']['redeemed_points']) }}</h3>
                    <small class="text-muted"><i class="fas fa-shopping-bag text-primary me-1"></i>Saved on purchases</small>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 border-start border-secondary border-4 h-100 bg-white">
                <div class="card-body p-3 p-lg-4">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Referrals</span>
                    <h3 class="fw-bold mb-1 text-dark">{{ number_format($summary['total_referrals']) }}</h3>
                    <small class="text-muted"><i class="fas fa-users text-secondary me-1"></i>Linked accounts</small>
                </div>
            </div>
        </div>
    </div>

    {{-- B2B Referral Qualification Section (Specification Page 2 & 8) --}}
    @if($summary['b2b_qualifications']->count() > 0)
    <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-briefcase text-primary me-2"></i>B2B Referral Qualifications</h5>
                <small class="text-muted">Referred businesses must purchase at least Rs. 100,000 within their first 3 calendar months to unlock 1% introductory rewards.</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            @if($isAdmin)
                            <th class="ps-4">Referrer</th>
                            @endif
                            <th class="{{ $isAdmin ? '' : 'ps-4' }}">Referred Business</th>
                            <th>Registration Date</th>
                            <th>3-Month Deadline</th>
                            <th>Progress (Target: Rs. 100,000)</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Reward Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['b2b_qualifications'] as $qual)
                        @php
                            $isExpired = now()->gt($qual->qualifying_deadline) && !$qual->is_qualified;
                        @endphp
                        <tr>
                            @if($isAdmin)
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $qual->referrer->name ?? 'User #' . $qual->referrer_id }}</div>
                                <small class="text-muted">{{ $qual->referrer->email ?? '' }}</small>
                            </td>
                            @endif
                            <td class="{{ $isAdmin ? '' : 'ps-4' }}">
                                <div class="fw-bold text-dark">{{ $qual->referred->name ?? 'User #' . $qual->referred_id }}</div>
                                <small class="text-muted">{{ $qual->referred->user_id ?? '' }} &bull; {{ ucfirst(str_replace('_', ' ', $qual->referred->account_type ?? 'Agent')) }}</small>
                            </td>
                            <td>{{ $qual->qualifying_start_date ? $qual->qualifying_start_date->format('d M Y') : 'N/A' }}</td>
                            <td>
                                <span class="fw-semibold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                    {{ $qual->qualifying_deadline ? $qual->qualifying_deadline->format('d M Y') : 'N/A' }}
                                </span>
                                @if(!$qual->is_qualified && !$isExpired)
                                    <div class="extra-small text-muted">{{ now()->diffInDays($qual->qualifying_deadline) }} day(s) remaining</div>
                                @endif
                            </td>
                            <td style="min-width: 200px;">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold">Rs. {{ number_format($qual->total_eligible_purchases, 0) }}</span>
                                    <span class="text-muted">{{ $qual->progress_percent }}%</span>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 4px;">
                                    <div class="progress-bar {{ $qual->is_qualified ? 'bg-success' : ($isExpired ? 'bg-secondary' : 'bg-primary') }}" 
                                         role="progressbar" 
                                         style="width: {{ $qual->progress_percent }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if($qual->is_qualified)
                                    <span class="badge bg-soft-success text-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>Qualified</span>
                                @elseif($isExpired)
                                    <span class="badge bg-soft-secondary text-secondary px-2 py-1"><i class="fas fa-times-circle me-1"></i>Target Expired</span>
                                @else
                                    <span class="badge bg-soft-warning text-warning px-2 py-1"><i class="fas fa-spinner fa-spin me-1"></i>In Progress</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <span class="fw-bold text-success">1% of purchases</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Referral Points History Card --}}
    <div class="card shadow-sm border-0 rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Referral Points History</h5>
                <small class="text-muted">Itemized audit ledger of all referral earnings, release holds, and expirations.</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('referral.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm rounded-3">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm rounded-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.referral-point.referral-table')
        </div>
    </div>

    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Copy to clipboard helper
        document.querySelectorAll('.copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var text = this.getAttribute('data-clipboard');
                navigator.clipboard.writeText(text).then(function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Copied to clipboard!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            });
        });

        // Admin Point Action Handler (Approve / Hold / Release / Cancel)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.admin-point-btn');
            if (!btn) return;
            e.preventDefault();

            const id = btn.dataset.id;
            const action = btn.dataset.action;
            const actionLabels = {
                'approve': 'Approve & Release Points Immediately',
                'release': 'Release Points Immediately',
                'hold': 'Place Points on Hold (Review / Dispute)',
                'unhold': 'Lift Hold on Points',
                'cancel': 'Cancel & Revoke Points'
            };
            const label = actionLabels[action] || action;

            let promptHtml = `Are you sure you want to <strong>${label}</strong>?`;
            if (action === 'hold' || action === 'cancel') {
                promptHtml += `<div class="mt-3 text-start"><label class="form-label small fw-bold">Reason / Memo (Optional):</label><input type="text" id="admin_point_notes" class="form-control" placeholder="e.g. Under transaction dispute or suspicious activity"></div>`;
            }

            Swal.fire({
                title: label,
                html: promptHtml,
                icon: (action === 'cancel' || action === 'hold') ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: (action === 'cancel') ? '#d33' : ((action === 'hold') ? '#f59e0b' : '#10b981'),
                confirmButtonText: 'Yes, Proceed',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const notesInput = document.getElementById('admin_point_notes');
                    return {
                        notes: notesInput ? notesInput.value : ''
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Updating point ledger record...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(`/dashboard/points/${id}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            action: action,
                            notes: result.value ? result.value.notes : ''
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Operation failed.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'An unexpected network error occurred.', 'error');
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection
