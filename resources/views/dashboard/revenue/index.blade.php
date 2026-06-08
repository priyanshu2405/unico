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
                    <h3 class="fw-bold mb-0">€{{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Available Balance</div>
                    <h3 class="fw-bold mb-0">€{{ number_format($stats['available_balance'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning text-dark">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Pending Payments</div>
                    <h3 class="fw-bold mb-0">€{{ number_format($stats['pending_payments'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="opacity-75 small mb-1">Refund Amount</div>
                    <h3 class="fw-bold mb-0">€{{ number_format($stats['refund_amount'], 2) }}</h3>
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
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe (Agent)</td>
                            <td>#TRX-98542</td>
                            <td class="fw-bold">€250.00</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>22 Jan 2026</td>
                        </tr>
                        <tr>
                            <td>Jane Smith (Student)</td>
                            <td>#TRX-98541</td>
                            <td class="fw-bold">€50.00</td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td>21 Jan 2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection