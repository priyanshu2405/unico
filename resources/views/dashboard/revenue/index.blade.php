@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Revenue & Balances</h4>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Total Revenue</div>
                    <h3 class="fw-bold mb-0">${{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Available Balance</div>
                    <h3 class="fw-bold mb-0">${{ number_format($stats['available_balance'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning text-dark">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Pending Payments</div>
                    <h3 class="fw-bold mb-0">${{ number_format($stats['pending_payments'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Refund Amount</div>
                    <h3 class="fw-bold mb-0">${{ number_format($stats['refund_amount'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold">Wallet / Credit Summary</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Transaction ID</th>
                            <th>Type</th>
                            <th>Amount (USD)</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgers as $ledger)
                        <tr>
                            <td>{{ $ledger->user ? $ledger->user->name . ' (' . ucfirst(str_replace('_', ' ', $ledger->user->account_type)) . ')' : 'System / Anonymous' }}</td>
                            <td><code>{{ $ledger->transaction_id }}</code></td>
                            <td>
                                <span class="badge {{ $ledger->type === 'credit' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($ledger->type) }}
                                </span>
                            </td>
                            <td class="fw-bold {{ $ledger->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $ledger->type === 'credit' ? '+' : '-' }}${{ number_format($ledger->amount, 2) }}
                            </td>
                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $ledger->source)) }}</span></td>
                            <td>{{ $ledger->created_at ? $ledger->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No wallet transactions recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection