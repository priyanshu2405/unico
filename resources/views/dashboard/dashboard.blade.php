@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Main Dashboard</h4>
        <div class="system-status">
            <span class="badge bg-success-subtle text-success p-2">
                <i class="fas fa-circle me-1 small"></i> System Running
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Users</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['total_users'] }}</h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-arrow-up me-1"></i>12%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('approvals.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 border-start border-warning border-4 h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Pending Approvals</div>
                        <div class="d-flex align-items-center">
                            <h3 class="fw-bold mb-0">{{ $stats['pending_approvals'] }}</h3>
                            <span class="ms-auto text-warning small fw-bold"><i class="fas fa-clock me-1"></i>Action Required</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Active Agents</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['agents'] }}</h3>
                        <span class="ms-auto text-success small fw-bold"><i class="fas fa-user-tie me-1"></i>B2B</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Active Students</div>
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold mb-0">{{ $stats['students'] }}</h3>
                        <span class="ms-auto text-info small fw-bold"><i class="fas fa-user-graduate me-1"></i>B2C</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Alerts -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Voucher & Revenue Trends</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active">Weekly</button>
                        <button class="btn btn-outline-secondary">Monthly</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-placeholder bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px; border: 2px dashed #ddd;">
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>Revenue & Voucher Graph Will Appear Here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Low Stock Alerts -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-circle me-1"></i> Low Stock Alerts</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small">University Voucher (UK)</div>
                                    <small class="text-muted">Only 5 remaining</small>
                                </div>
                                <span class="badge bg-danger">Critical</span>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small">IELTS Booking Code</div>
                                    <small class="text-muted">Only 12 remaining</small>
                                </div>
                                <span class="badge bg-warning text-dark">Warning</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-center py-2">
                    <a href="{{ route('stock.alerts') }}" class="small text-decoration-none text-primary fw-bold">View All Alerts</a>
                </div>
            </div>

            <!-- System Stats -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Revenue Overview</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Daily Revenue</span>
                        <span class="fw-bold small">€1,240.00</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Monthly Revenue</span>
                        <span class="fw-bold small">€28,450.00</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 60%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection