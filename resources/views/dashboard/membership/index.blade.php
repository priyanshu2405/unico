@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())

    {{-- Hero Section --}}
    @if($activeMembership && $activeMembership->isActive())
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #fff;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase">
                        <i class="fas fa-crown me-1"></i> Active {{ ucfirst($activeMembership->plan_type) }} Member
                    </span>
                    <h2 class="fw-bold mb-2 text-white">UniCou Exclusive Membership</h2>
                    <p class="text-white-50 mb-3" style="max-width: 600px;">
                        Enjoy an <strong>immediate extra 1% discount</strong> on all qualifying voucher purchases. Your personal promo code is active and automatically applied at checkout before points redemption.
                    </p>

                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div class="bg-white bg-opacity-10 rounded-3 p-2 px-3 border border-white border-opacity-10 d-flex align-items-center gap-2">
                            <span class="text-white-50 small">Your Personal Code:</span>
                            <span class="fw-bold text-warning font-monospace fs-5">{{ $activeMembership->promo_code }}</span>
                            <button class="btn btn-sm btn-link text-white p-0 ms-1 copy-btn" data-clipboard="{{ $activeMembership->promo_code }}" title="Copy Code">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>

                        <div class="text-white-50 small">
                            <i class="fas fa-calendar-alt text-warning me-1"></i>
                            Valid until <strong>{{ $activeMembership->expires_at->format('d M Y H:i') }} UTC (11:59 PM UK)</strong>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-10 d-inline-block text-start w-100">
                        <span class="text-white-50 small text-uppercase fw-bold d-block mb-1">Total Discount Saved</span>
                        <div class="d-flex align-items-baseline mb-2">
                            <h2 class="display-6 fw-bold mb-0 text-white">Rs. {{ number_format($activeMembership->total_discount_saved, 2) }}</h2>
                        </div>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">
                            <i class="fas fa-shield-alt me-1"></i>1% Extra Discount Active
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase">
                        <i class="fas fa-crown me-1"></i> UniCou VIP Membership
                    </span>
                    <h2 class="fw-bold mb-2 text-white">Upgrade to UniCou Membership</h2>
                    <p class="text-white-50 mb-0" style="max-width: 600px;">
                        Subscribe for Rs. 1,500/month or an annual plan and receive an <strong>immediate extra 1% discount</strong> on all voucher purchases. Unlimited purchases throughout your active period, applied before points redemption!
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <a href="#plansSection" class="btn btn-warning btn-lg rounded-3 px-4 fw-bold shadow">
                        <i class="fas fa-bolt me-1"></i> Explore Plans
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Membership Benefits Overview (Specification Page 5 & 6) --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex p-3 mb-3">
                        <i class="fas fa-percent fa-2x"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Extra 1% Discount</h6>
                    <small class="text-muted">Calculated on voucher subtotal after base pricing and before taxes & charges.</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white">
                <div class="card-body p-4 text-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex p-3 mb-3">
                        <i class="fas fa-infinity fa-2x"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Repeated Use</h6>
                    <small class="text-muted">No minimum purchase requirement, usage-count limit, or discount cap during validity.</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white">
                <div class="card-body p-4 text-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex p-3 mb-3">
                        <i class="fas fa-key fa-2x"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Unique Promo Code</h6>
                    <small class="text-muted">Automatically issued unique account-specific promo code linked directly to your account.</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white">
                <div class="card-body p-4 text-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex p-3 mb-3">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Combine With Points</h6>
                    <small class="text-muted">Points may still be redeemed after membership discount (up to 10% combined invoice cap).</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Membership Plans Section --}}
    <div id="plansSection" class="row g-4 mb-4">
        <!-- Monthly Membership Card -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white border-top border-primary border-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-soft-primary text-primary px-3 py-1 fw-bold">Calendar Month Plan</span>
                        <span class="text-muted small">Standard UK Month Boundaries</span>
                    </div>

                    <h3 class="fw-bold text-dark mb-1">Monthly Membership</h3>
                    <div class="d-flex align-items-baseline mb-4">
                        <h1 class="display-5 fw-bold text-primary mb-0">Rs. 1,500</h1>
                        <span class="text-muted ms-2">/ calendar month</span>
                    </div>

                    <ul class="list-unstyled mb-4 small">
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><strong>1% Immediate Discount</strong> on all voucher purchases</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Valid from activation until <strong>11:59 PM UK time on the last day of the month</strong></li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Full Rs. 1,500 fee applies for mid-month joining (valid for remaining days)</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Unique account-specific promo code generated instantly</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Combinable with reward points redemption at checkout</li>
                    </ul>

                    <button type="button" class="btn btn-primary btn-lg w-100 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmMonthlyModal">
                        <i class="fas fa-shopping-cart me-1"></i> Activate Monthly (Rs. 1,500)
                    </button>
                </div>
            </div>
        </div>

        <!-- Annual Membership Card -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-white border-top border-success border-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-soft-success text-success px-3 py-1 fw-bold">12 Consecutive Months</span>
                        <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-star me-1"></i>Best Value</span>
                    </div>

                    <h3 class="fw-bold text-dark mb-1">Annual Membership</h3>
                    <div class="d-flex align-items-baseline mb-4">
                        <h1 class="display-5 fw-bold text-success mb-0">Rs. 15,000</h1>
                        <span class="text-muted ms-2">/ 12 consecutive months</span>
                    </div>

                    <ul class="list-unstyled mb-4 small">
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i><strong>1% Immediate Discount</strong> on all voucher purchases</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Full <strong>12-month validity</strong> regardless of joining date</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>No renewal required every calendar month</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Continuous account-specific promo code</li>
                        <li class="mb-2 d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i>Maximum long-term voucher cost savings</li>
                    </ul>

                    <button type="button" class="btn btn-success btn-lg w-100 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmAnnualModal">
                        <i class="fas fa-crown me-1"></i> Activate Annual (Rs. 15,000)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Membership History Table --}}
    <div class="card shadow-sm border-0 rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold">Membership History</h5>
            <small class="text-muted">Log of all purchased and expired membership periods.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase text-muted">
                        <tr>
                            <th class="ps-4 py-3">Plan</th>
                            <th class="py-3">Fee Paid</th>
                            <th class="py-3">Promo Code</th>
                            <th class="py-3">Starts At</th>
                            <th class="py-3">Expires At</th>
                            <th class="py-3">Discount Saved</th>
                            <th class="pe-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($membershipHistory as $mem)
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-capitalize">{{ $mem->plan_type }}</td>
                            <td>{{ $mem->currency }} {{ number_format($mem->fee_paid, 2) }}</td>
                            <td><span class="font-monospace text-primary fw-bold">{{ $mem->promo_code }}</span></td>
                            <td class="small">{{ $mem->starts_at->format('d M Y H:i') }}</td>
                            <td class="small">{{ $mem->expires_at->format('d M Y H:i') }}</td>
                            <td class="fw-bold text-success">Rs. {{ number_format($mem->total_discount_saved, 2) }}</td>
                            <td class="pe-4 text-center">
                                @if($mem->isActive())
                                    <span class="badge bg-soft-success text-success px-2 py-1">Active</span>
                                @else
                                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">Expired</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No prior membership records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($membershipHistory->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $membershipHistory->links() }}
        </div>
        @endif
    </div>

    {{-- Confirmation Modal - Monthly --}}
    <div class="modal fade" id="confirmMonthlyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Activate Monthly Membership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('membership.activate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan_type" value="monthly">
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
                            <i class="fas fa-info-circle me-1"></i> Fee: <strong>Rs. 1,500</strong>. Valid until 11:59 PM UK time on the last day of the current calendar month.
                        </div>
                        <p class="small text-muted mb-0">
                            The Rs. 1,500 fee will be deducted directly from your available wallet balance. After activation, you will immediately receive an extra 1% discount on all voucher purchases.
                        </p>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-3 fw-bold">Confirm & Pay Rs. 1,500</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal - Annual --}}
    <div class="modal fade" id="confirmAnnualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Activate Annual Membership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('membership.activate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan_type" value="yearly">
                    <div class="modal-body p-4">
                        <div class="alert alert-success py-2 px-3 small rounded-3 mb-3">
                            <i class="fas fa-crown me-1"></i> Fee: <strong>Rs. 15,000</strong>. Valid for 12 consecutive months from today.
                        </div>
                        <p class="small text-muted mb-0">
                            The Rs. 15,000 fee will be deducted directly from your available wallet balance. You will enjoy 12 full months of uninterrupted 1% discounts.
                        </p>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-3 fw-bold">Confirm & Pay Rs. 15,000</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
</script>
@endpush
@endsection
