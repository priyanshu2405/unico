@extends('layouts.master')

@section('content')

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@php
    $successMsg = session('success') ?: request()->get('success');
@endphp

@if($successMsg)
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: 'Success!',
            text: "{{ $successMsg }}",
            icon: 'success',
            confirmButtonColor: '#23AAE2'
        });
    });
</script>
@endif

<script src="https://js.stripe.com/v3/"></script>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Linked Banks & Wallet</h4>
            <p class="text-muted small mb-0">Manage your linked bank accounts and wallet credit.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                <i class="fas fa-plus-circle me-1"></i> Add Wallet Credit
            </button>
              <!-- <button class="btn btn-outline-info shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#linkBankModal">
                <i class="fas fa-plus me-1"></i> Manual Link
            </button>
            <button id="linkBankBtn" class="btn btn-outline-primary shadow-sm px-3">
                <i class="fas fa-link me-1"></i> Link Bank (Secure)
            </button> -->
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 text-uppercase fw-bold">Wallet Balance</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">${{ auth()->user()->currency }} {{ number_format(auth()->user()->wallet_balance, 2) }}</h3>
                        <span class="ms-auto text-primary fw-bold small"><i class="fas fa-wallet me-1"></i>Available</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 text-uppercase fw-bold">Total Bank Balance</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ auth()->user()->currency }} {{ number_format($totalBalance, 2) }}</h3>
                        <span class="ms-auto text-info fw-bold small"><i class="fas fa-university me-1"></i>{{ $banks->count() }} Accounts</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1 text-uppercase fw-bold">Total Net Worth</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ auth()->user()->currency }} {{ number_format(auth()->user()->wallet_balance + $totalBalance, 2) }}</h3>
                        <span class="ms-auto text-success fw-bold small"><i class="fas fa-chart-pie me-1"></i>Combined</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Banks Grid -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">My Linked Bank Accounts</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($banks as $bank)
                        <div class="col-md-4">
                            <div class="card border shadow-none h-100" style="border-radius: 12px; transition: all 0.2s;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded text-primary fw-bold" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                {{ substr($bank->bank_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $bank->bank_name }}</h6>
                                                <small class="text-muted">{{ $bank->account_type }}</small>
                                            </div>
                                        </div>
                                        <span class="badge {{ $bank->is_verified ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} rounded-pill align-self-start">
                                            {{ $bank->is_verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-muted small">Current Balance</div>
                                        <h5 class="fw-bold mb-0 text-dark">{{ auth()->user()->currency }} {{ number_format($bank->balance, 2) }}</h5>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                        <span class="text-muted small font-monospace">•••• {{ substr($bank->account_number, -4) }}</span>
                                        <span class="text-muted small">{{ $bank->account_holder_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-university fa-3x mb-2 opacity-25"></i>
                                <p>No bank accounts linked yet.</p>
                               <button class="btn btn-sm btn-primary"  data-bs-toggle="modal" data-bs-target="#addMoneyModal">Link Now</button>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Recent Wallet Transactions</h6>
                    <span class="badge bg-primary-subtle text-primary fw-bold">Ledger</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-muted small text-uppercase">
                                <th class="ps-4">Description</th>
                                <th>Transaction ID</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $trx)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-2 rounded bg-light">
                                            @if($trx->type == 'credit')
                                            <i class="fas fa-arrow-alt-circle-down text-success"></i>
                                            @else
                                            <i class="fas fa-arrow-alt-circle-up text-danger"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small">{{ $trx->description }}</div>
                                            <div class="text-muted" style="font-size: 0.7rem;">REF: #WLT-{{ $trx->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="small text-primary fw-bold">{{ $trx->transaction_id ?: 'N/A' }}</code>
                                </td>
                                <td>
                                    @if($trx->type == 'credit')
                                    <span class="badge bg-success-subtle text-success px-2 py-1">Credit</span>
                                    @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1">Debit</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $trx->created_at->format('d M Y') }}
                                    <div class="small opacity-75">{{ $trx->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="text-end pe-4 fw-bold {{ $trx->type == 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $trx->type == 'credit' ? '+' : '-' }}{{ auth()->user()->currency }} {{ number_format($trx->amount, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    No wallet transactions found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                <div class="card-footer bg-white py-3">
                    <div class="d-flex justify-content-center">
                        @include('dashboard.partials.custom-pagination', ['items' => $transactions])
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Money Modal -->
<div class="modal fade" id="addMoneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">

            <!-- ✅ FORM START -->
            <form id="paymentForm" method="POST" action="{{ route('stripe.checkout.session') }}">
                @csrf

                <!-- Header -->
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Add Wallet Credit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">SELECT PAYMENT METHOD</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="method_stripe" value="stripe" checked autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2" for="method_stripe">
                                    <i class="fab fa-stripe fa-2x"></i>
                                    <span class="fw-bold">Stripe</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="method_paypal" value="paypal" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2" for="method_paypal">
                                    <i class="fab fa-paypal fa-2x"></i>
                                    <span class="fw-bold">PayPal</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="method_wise" value="wise" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2" for="method_wise">
                                    <i class="fas fa-university fa-2x"></i>
                                    <span class="fw-bold">Wise</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="method_kuickpay" value="kuickpay" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-2" for="method_kuickpay">
                                    <i class="fas fa-mobile-alt fa-2x"></i>
                                    <span class="fw-bold">KuickPay</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">ENTER AMOUNT</label>

                        <div class="input-group border rounded p-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 fw-bold text-primary">
                                {{ auth()->user()->currency }}
                            </span>

                            <input type="number" name="amount"
                                class="form-control bg-transparent border-0 shadow-none fw-bold"
                                placeholder="0.00" min="1" required>
                        </div>
                    </div>

                    <div class="alert bg-primary-subtle border-0 small text-primary p-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Funds will be credited to your wallet instantly.
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        Process Transfer
                    </button>
                </div>

            </form>
            <!-- ✅ FORM END -->

            <script>
                document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const form = document.getElementById('paymentForm');
                        if (this.value === 'stripe') {
                            form.action = "{{ route('stripe.checkout.session') }}";
                        } else if (this.value === 'paypal') {
                            form.action = "{{ route('paypal.checkout') }}";
                        } else if (this.value === 'wise') {
                            form.action = "{{ route('wise.checkout') }}";
                        } else if (this.value === 'kuickpay') {
                            form.action = "{{ route('kuickpay.checkout') }}";
                        }
                    });
                });
            </script>

        </div>
    </div>
</div>

@endsection