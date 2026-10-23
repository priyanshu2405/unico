@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())

    {{-- Offcanvas Filter --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="filterOffcanvasLabel">
                <i class="fas fa-filter me-2 text-primary"></i>Filter Bonus Points
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="filter-form" action="{{ route('bonus') }}" method="GET">
                @if($isAdmin && !empty($usersList))
                <div class="mb-4">
                    <label class="form-label fw-bold">Filter By User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users (System-wide)</option>
                        @foreach($usersList as $u)
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
                    <label class="form-label fw-bold">Bonus Category</label>
                    <select name="point_type" class="form-select">
                        <option value="">All Categories</option>
                        <option value="agent_monthly_bonus" {{ request('point_type') == 'agent_monthly_bonus' ? 'selected' : '' }}>Monthly Bonus</option>
                        <option value="agent_quarterly_bonus" {{ request('point_type') == 'agent_quarterly_bonus' ? 'selected' : '' }}>Quarterly Bonus</option>
                        <option value="agent_yearly_bonus" {{ request('point_type') == 'agent_yearly_bonus' ? 'selected' : '' }}>Yearly Bonus</option>
                        <option value="student_purchase_bonus" {{ request('point_type') == 'student_purchase_bonus' ? 'selected' : '' }}>Student Purchase Bonus</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available (Spendable)</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Release</option>
                        <option value="held" {{ request('status') == 'held' ? 'selected' : '' }}>Held (Disputed / Frozen by Admin)</option>
                        <option value="redeemed" {{ request('status') == 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
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
                    <a href="{{ route('bonus') }}" class="btn btn-light">Reset All</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Hero Summary Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #fff;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase">
                        <i class="fas fa-calendar-check me-1"></i> {{ $isAdmin ? 'Admin Bonus Point Monitoring' : 'Bonus Point System' }}
                    </span>
                    <h2 class="fw-bold mb-2 text-white">{{ $isAdmin ? 'Bonus Points Administration' : 'Monthly, Quarterly & Yearly Bonus Points' }}</h2>
                    <p class="text-white-50 mb-0" style="max-width: 600px;">
                        @if($isAdmin)
                        Audit bonus accruals across all reseller agents, agents, and students. Monitor monthly, quarterly, and annual qualifying voucher thresholds, put disputed bonuses on hold, or approve early release.
                        @else
                        Bonus points are calculated as <strong>Qualifying Voucher Units &times; Published Points per Unit</strong>. Purchases must be fully paid and delivered within the earning period. Points become available on release dates and are valid for purchase adjustments.
                        @endif
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-10 d-inline-block text-start w-100">
                        <span class="text-white-50 small text-uppercase fw-bold d-block mb-1">{{ $isAdmin ? 'Total Available (Spendable)' : 'Spendable Balance' }}</span>
                        <div class="d-flex align-items-baseline mb-2">
                            <h2 class="display-6 fw-bold mb-0 text-white">{{ number_format($pointsBalance['net_available_points']) }}</h2>
                            <span class="ms-2 text-warning fw-semibold">Points</span>
                        </div>
                        <div class="row g-2 mt-2 pt-2 border-top border-white border-opacity-10 small text-white-50">
                            <div class="col-6">
                                <span class="d-block text-white fw-bold">{{ number_format($pointsBalance['pending_points']) }}</span>
                                Pending Release
                            </div>
                            <div class="col-6">
                                <span class="d-block text-danger fw-bold">{{ number_format($bonusStats['total_held'] ?? 0) }}</span>
                                Flagged on Hold
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Three Earning Stream Cards (Specification Page 1 & 10-12) --}}
    <div class="row g-3 mb-4">
        <!-- Monthly Bonus Card -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-soft-primary text-primary px-2 py-1 mb-1">1 Calendar Month</span>
                            <h5 class="fw-bold mb-0 text-dark">Monthly Bonus</h5>
                        </div>
                        <i class="fas fa-calendar-alt text-primary fa-2x opacity-25"></i>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">{{ $isAdmin ? 'Total Current Month Units:' : 'Current Month Qualifying Units:' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($bonusStats['month_units']) }} units</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Accrued Pending Points:</span>
                            <span class="fw-bold text-success">+{{ number_format($bonusStats['accrued_monthly']) }} pts</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Next Release Date:</span>
                            <span class="fw-bold text-primary">{{ $bonusStats['next_monthly_release']->format('07 M Y') }}</span>
                        </div>
                    </div>
                    <small class="text-muted d-block"><i class="fas fa-info-circle me-1"></i>Matures on the 7th of next month (30-day validity).</small>
                </div>
            </div>
        </div>

        <!-- Quarterly Bonus Card -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white border-start border-info border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-soft-info text-info px-2 py-1 mb-1">Jan–Mar, Apr–Jun, Jul–Sep, Oct–Dec</span>
                            <h5 class="fw-bold mb-0 text-dark">Quarterly Bonus</h5>
                        </div>
                        <i class="fas fa-chart-pie text-info fa-2x opacity-25"></i>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">{{ $isAdmin ? 'Total Current Quarter Units:' : 'Current Quarter Qualifying Units:' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($bonusStats['quarter_units']) }} units</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Accrued Pending Points:</span>
                            <span class="fw-bold text-success">+{{ number_format($bonusStats['accrued_quarterly']) }} pts</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Next Release Date:</span>
                            <span class="fw-bold text-info">{{ $bonusStats['next_quarterly_release']->format('07 M Y') }}</span>
                        </div>
                    </div>
                    <small class="text-muted d-block"><i class="fas fa-info-circle me-1"></i>Matures on 7th of quarter-following month (90-day validity).</small>
                </div>
            </div>
        </div>

        <!-- Yearly Bonus Card -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-soft-success text-success px-2 py-1 mb-1">1 Jan – 31 Dec</span>
                            <h5 class="fw-bold mb-0 text-dark">Yearly Bonus</h5>
                        </div>
                        <i class="fas fa-trophy text-success fa-2x opacity-25"></i>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">{{ $isAdmin ? 'Total Current Year Units:' : 'Current Year Qualifying Units:' }}</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($bonusStats['year_units']) }} units</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Accrued Pending Points:</span>
                            <span class="fw-bold text-success">+{{ number_format($bonusStats['accrued_yearly']) }} pts</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Next Release Date:</span>
                            <span class="fw-bold text-success">{{ $bonusStats['next_yearly_release']->format('07 Jan Y') }}</span>
                        </div>
                    </div>
                    <small class="text-muted d-block"><i class="fas fa-info-circle me-1"></i>Matures on 7th of January next year (365-day validity).</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Bonus Points History --}}
    <div class="card shadow-sm border-0 rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Bonus Points Ledger History</h5>
                <small class="text-muted">Comprehensive history of bonus earnings and maturation schedules.</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('bonus.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm rounded-3">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <button class="btn btn-outline-primary btn-sm px-3 shadow-sm rounded-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body p-0" id="table-container">
            @include('dashboard.bonus-point.bonus-table')
        </div>
    </div>

    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Admin Point Action Handler (Approve / Hold / Release / Cancel)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.admin-point-btn');
            if (!btn) return;
            e.preventDefault();

            const id = btn.dataset.id;
            const action = btn.dataset.action;
            const actionLabels = {
                'approve': 'Approve & Release Bonus Points Immediately',
                'release': 'Release Bonus Points Immediately',
                'hold': 'Place Bonus Points on Hold (Review / Dispute)',
                'unhold': 'Lift Hold on Bonus Points',
                'cancel': 'Cancel & Revoke Bonus Points'
            };
            const label = actionLabels[action] || action;

            let promptHtml = `Are you sure you want to <strong>${label}</strong>?`;
            if (action === 'hold' || action === 'cancel') {
                promptHtml += `<div class="mt-3 text-start"><label class="form-label small fw-bold">Reason / Memo (Optional):</label><input type="text" id="admin_point_notes" class="form-control" placeholder="e.g. Under order audit or fraud investigation"></div>`;
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
                        text: 'Updating bonus ledger record...',
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