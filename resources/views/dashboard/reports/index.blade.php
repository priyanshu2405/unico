@extends('layouts.master')
@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">Business Reports & Analytics</h2>
            <p class="text-muted">Overview of sales, inventory, and user performance.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Total Sales</h6>
                    <h2 class="mb-0 fw-bold">${{ number_format($stats['total_sales'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Completed Orders</h6>
                    <h2 class="mb-0 fw-bold">{{ $stats['total_orders'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Pending Orders</h6>
                    <h2 class="mb-0 fw-bold">{{ $stats['pending_orders'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Active Vouchers</h6>
                    <h2 class="mb-0 fw-bold">{{ $stats['total_vouchers'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h4 class="fw-bold mb-3">Available Reports</h4>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft p-3 rounded-3 me-3">
                            <i class="fas fa-file-invoice-dollar text-success fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Purches Report</h5>
                    </div>
                    <p class="text-muted small">View detailed reports of purchased vouchers and current stock levels by PID and voucher type.</p>
                    <a href="{{ route('pricing.index') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft p-3 rounded-3 me-3">
                            <i class="fas fa-chart-line text-primary fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Sales Report</h5>
                    </div>
                    <p class="text-muted small">View detailed reports of sales transactions, revenue, and voucher performance by PID and voucher type.</p>
                    <a href="{{ route('sales.index') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft p-3 rounded-3 me-3">
                            <i class="fas fa-boxes text-warning fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Stock Report</h5>
                    </div>
                    <p class="text-muted small">View detailed stock reports, including available voucher quantities and inventory levels by PID and voucher type.</p>
                    <a href="{{ route('reports.stock') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft p-3 rounded-3 me-3">
                            <i class="fas fa-credit-card text-info fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Payments Report</h5>
                    </div>
                    <p class="text-muted small">Access comprehensive payment insights with transaction summaries, processing status, and method-wise breakdown.</p>
                    <a href="{{ route('reports.payments') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft p-3 rounded-3 me-3">
                            <i class="fas fa-ticket-alt text-danger fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Vouchers Report</h5>
                    </div>
                    <p class="text-muted small">View detailed voucher reports, including generated, used, and remaining vouchers by PID and voucher type.</p>
                    <a href="{{ route('inventory.index') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft p-3 rounded-3 me-3">
                            <i class="fas fa-university text-secondary fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Banks Reports</h5>
                    </div>
                    <p class="text-muted small">View detailed bank reports, including transaction summaries, deposits, withdrawals, and balance records.</p>
                    <a href="{{ route('banks.bank-table') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft p-3 rounded-3 me-3">
                            <i class="fas fa-balance-scale text-dark fa-2x"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Gross Profit & Loss</h5>
                    </div>
                    <p class="text-muted small">Detailed profit and loss summary with revenue and expense overview.</p>
                    <a href="{{ route('reports.profit-loss') }}" class="btn btn-primary w-100">View Report</a>
                </div>
            </div>
        </div>
        

    </div>

    <style>
        .bg-primary-soft {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-success-soft {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-info-soft {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .bg-dark-soft {
            background-color: rgba(33, 37, 41, 0.1);
        }

        .transition {
            transition: all 0.3s ease;
        }

        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }
    </style>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Recent Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Voucher</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_orders as $order)
                                <tr>
                                    <td><span class="fw-bold">#{{ $order->order_id }}</span></td>
                                    <td>
                                        <div class="small fw-bold">{{ $order->user->name ?? 'N/A' }}</div>
                                        <div class="small text-muted">{{ $order->user->email ?? '' }}</div>
                                    </td>
                                    <td>{{ $order->voucher->name ?? 'N/A' }}</td>
                                    <td>${{ number_format($order->total_amount, 2) }}</td>
                                    <td>
                                        @php
                                        $badgeClass = match($order->status) {
                                        'delivered' => 'bg-success',
                                        'pending' => 'bg-warning text-dark',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                        };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No recent orders found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 text-center">
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View All Orders</a>
                </div>
            </div>
        </div>

        <!-- System Summary -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">System Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Total Users</span>
                            <span class="badge bg-primary rounded-pill">{{ $stats['total_users'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Inventory Health</span>
                            <span class="text-success fw-bold">Optimal</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>System Status</span>
                            <span class="badge bg-success">Operational</span>
                        </li>
                    </ul>

                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('vouchers.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-download me-2"></i> Export Voucher Inventory
                            </a>
                            <a href="{{ route('users.pdf') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-file-pdf me-2"></i> Download User Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection