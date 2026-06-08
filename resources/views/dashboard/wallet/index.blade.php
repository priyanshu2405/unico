@extends('layouts.master')

@section('content')
<div class="container-fluid py-4">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1 text-uppercase">Total System Balance</p>
                        <h2 class="fw-bold mb-0">${{ number_format($stats['total_balance'], 2) }}</h2>
                    </div>
                    <div class="d-flex bg-primary-subtle p-3 rounded-circle">
                        <i class="fas fa-wallet text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px; border-left: 4px solid #198754;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1 text-uppercase">Total Credits</p>
                        <h2 class="fw-bold mb-0">${{ number_format($stats['total_credits'], 2) }}</h2>
                    </div>
                    <div class="d-flex bg-success-subtle p-3 rounded-circle">
                        <i class="fas fa-arrow-up text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 20px; border-left: 4px solid #dc3545;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1 text-uppercase">Total Debits</p>
                        <h2 class="fw-bold mb-0">${{ number_format($stats['total_debits'], 2) }}</h2>
                    </div>
                    <div class="d-flex bg-danger-subtle p-3 rounded-circle">
                        <i class="fas fa-arrow-down text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Analysis Banner -->
    <!-- <div class="row mb-4">
        <div class="col-12">
            <div class="alert border-0 shadow-sm p-4 d-flex align-items-center" style="border-radius: 15px; background-color: #f0f7ff; color: #0052cc;">
                <div class="bg-white rounded-circle p-2 me-3 shadow-sm">
                    <i class="fas fa-magic text-primary"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">AI Ledger Analysis</h6>
                    <p class="mb-0 small">The wallet activity recorded a net {{ $stats['total_credits'] > $stats['total_debits'] ? 'positive' : 'negative' }} cash flow of ${{ number_format(abs($stats['total_credits'] - $stats['total_debits']), 2) }} across all analyzed user accounts recently.</p>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Tabs Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs border-0 gap-4" id="walletTabs">
                <li class="nav-item">
                    <a class="nav-link active fw-bold border-0 px-0 pb-2" data-bs-toggle="tab" href="#users" data-tab="users" style="color: #666; border-bottom: 3px solid transparent !important;">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold border-0 px-0 pb-2" data-bs-toggle="tab" href="#ledger" data-tab="ledger" style="color: #666; border-bottom: 3px solid transparent !important;">Ledger</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold border-0 px-0 pb-2" data-bs-toggle="tab" href="#webhooks" data-tab="webhooks" style="color: #666; border-bottom: 3px solid transparent !important;">Webhooks</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Tab Content -->
        <div class="col-lg-8">
            <div class="tab-content" id="table-container">
                <div class="tab-pane fade show active" id="users">
                    @include('dashboard.wallet.partials.users-table')
                </div>
                <div class="tab-pane fade" id="ledger">
                    @include('dashboard.wallet.partials.ledger-table')
                </div>
                <div class="tab-pane fade" id="webhooks">
                    <div class="card border-0 shadow-sm p-5 text-center" style="border-radius: 15px;">
                        <i class="fas fa-plug fa-3x text-muted mb-3"></i>
                        <h5>Webhook Integration</h5>
                        <p class="text-muted">Auto-credit and API logs will appear here.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Manual Adjustment -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle p-2 me-3" style="background-color: #f0f4ff; color: #4c6ef5; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-crosshairs"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Manual Adjustment</h5>
                </div>

                <form id="adjustmentForm" action="{{ route('wallet.credit') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Select User</label>
                        <select name="user_id" id="user-select" class="form-select border-0 bg-light p-3" style="border-radius: 10px;" required>
                            <option value="">Choose a user...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" data-balance="{{ number_format($user->wallet_balance, 2) }}">
                                {{ $user->name }} (${{ number_format($user->wallet_balance, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control border-0 bg-light p-3" placeholder="0.00" style="border-radius: 10px;" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Internal Note (Optional)</label>
                        <textarea name="note" class="form-control border-0 bg-light p-3" rows="3" placeholder="Reason for adjustment..." style="border-radius: 10px;"></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-success w-100 py-3 fw-bold submit-btn" data-type="credit" style="border-radius: 10px; background-color: #6fc2a6; border: none;">
                                Credit (+)
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-danger w-100 py-3 fw-bold submit-btn" data-type="debit" style="border-radius: 10px; background-color: #ef9a9a; border: none;">
                                Debit (-)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link.active {
        color: #4c6ef5 !important;
        border-bottom: 3px solid #4c6ef5 !important;
        background: transparent !important;
    }

    .bg-primary-subtle {
        background-color: #e7f1ff;
    }

    .bg-success-subtle {
        background-color: #e6f6f1;
    }

    .bg-danger-subtle {
        background-color: #fbe9e9;
    }

    .form-select:focus,
    .form-control:focus {
        box-shadow: none;
        background-color: #f1f3f5;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function() {
        // Tab switching with AJAX (optional, but implemented for completeness)
        $('#walletTabs a').on('click', function(e) {
            e.preventDefault();
            let tab = $(this).data('tab');
            let target = $(this).attr('href');

            $.ajax({
                url: "{{ route('wallet.index') }}",
                data: {
                    tab: tab
                },
                success: function(data) {
                    $(target).html(data);
                }
            });
        });

        // Adjust Balance button in table
        $(document).on('click', '.adjust-balance-btn', function() {
            let userId = $(this).data('user-id');
            $('#user-select').val(userId).trigger('change');
            $('html, body').animate({
                scrollTop: $("#adjustmentForm").offset().top - 100
            }, 500);
        });

        // Manual Adjustment Form submission
        $('.submit-btn').on('click', function() {
            let type = $(this).data('type');
            let form = $('#adjustmentForm');
            let url = type === 'credit' ? "{{ route('wallet.credit') }}" : "{{ route('wallet.debit') }}";

            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            let formData = form.serialize();

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.success);
                        // Refresh current active tab
                        let activeTab = $('#walletTabs a.active').data('tab');
                        refreshData(activeTab);
                        form[0].reset();
                    }
                },
                error: function(xhr) {
                    let errorMsg = xhr.responseJSON?.error || 'Something went wrong.';
                    toastr.error(errorMsg);
                }
            });
        });

        function refreshData(tab) {
            $.ajax({
                url: "{{ route('wallet.index') }}",
                data: {
                    tab: tab
                },
                success: function(data) {
                    $('#' + tab).html(data);
                    // Also update stats if we had an endpoint for that, but for now we can just reload the page or partial stats
                    // To keep it simple, we refresh the table content.
                }
            });
        }
    });
</script>
@endpush
@endsection